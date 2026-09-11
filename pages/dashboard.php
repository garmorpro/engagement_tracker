<?php
require_once '../auth/session_check.php';
require_once '../path.php';
require_once '../includes/functions.php';

$showArchived = isset($_GET['view']) && $_GET['view'] === 'archived';

// Get all engagements data
$allEngagements = getAllEngagements($conn);

// Get all timelines
$allTimelineData = getAllTimelineData($conn);
$timelineLookup = [];
foreach ($allTimelineData as $row) {
    $timelineLookup[$row['engagement_idno']] = $row;
}

// Get all team rows, grouped by engagement — used to flag engagements that
// still need a team and/or DOL assigned (see getSetupInfo() below), same
// "fetch everything once, look it up per engagement" pattern as the
// timeline lookup above.
$allTeamData = getAllTeamData($conn);
$teamLookup = [];
foreach ($allTeamData as $row) {
    $teamLookup[$row['engagement_idno']][] = $row;
}

$activeEngagements = array_filter($allEngagements, fn($e) => $e['eng_status'] !== 'archived');
$archivedEngagements = array_filter($allEngagements, fn($e) => $e['eng_status'] === 'archived');
$activeCount = count($activeEngagements);
$archivedCount = count($archivedEngagements);

$engagements = $showArchived ? $archivedEngagements : $activeEngagements;

$statusMeta = [
    'in-progress' => ['label' => 'In Progress', 'var' => '--ink'],
    'in-review'   => ['label' => 'In Review',   'var' => '--ink-soft'],
    'planning'    => ['label' => 'Planning',    'var' => '--caution'],
    'complete'    => ['label' => 'Complete',    'var' => '--good'],
];
$sectionOrder = ['in-progress', 'in-review', 'planning', 'complete'];

// Status mix for the active-portfolio distribution card — only statuses that
// actually have an active engagement show up, in $sectionOrder's order, so a
// new status (or the first "Complete" engagement) appears on its own with no
// code change needed.
$statusCounts = [];
foreach ($sectionOrder as $status) {
    $count = count(array_filter($activeEngagements, fn($e) => $e['eng_status'] === $status));
    if ($count > 0) {
        $statusCounts[$status] = $count;
    }
}

// Due date state for an engagement: [dateObj|null, 'overdue'|'soon'|'ok'|'archive_ready'|'none']
function getDueInfo($engIdno, $timelineLookup) {
    if (!isset($timelineLookup[$engIdno])) {
        return [null, 'none'];
    }
    $tl = $timelineLookup[$engIdno];

    // Final report is done and the engagement hasn't been archived yet —
    // there's nothing left to be "late" on, the old final-report due date
    // is no longer meaningful. Surface it as ready-to-archive instead of
    // red/overdue against a date that's already been hit.
    if (!empty($tl['final_report_completed_at']) && empty($tl['archive_completed_at'])) {
        return [null, 'archive_ready'];
    }

    if (empty($tl['final_report_date'])) {
        return [null, 'none'];
    }
    $due = new DateTime($tl['final_report_date']);
    $today = new DateTime('today');
    $diffDays = (int) $today->diff($due)->format('%r%a');
    if ($diffDays < 0) return [$due, 'overdue'];
    if ($diffDays <= 5) return [$due, 'soon'];
    return [$due, 'ok'];
}

// Audit types that actually have a DOL column on engagement_team — mirrors
// DOL_AUDIT_TYPES in this page's JS. PCI and ISO have no DOL column at all,
// so an engagement whose only audit type is one of those can never be
// flagged for "missing DOL" — there's nothing to assign.
const DOL_AUDIT_TYPE_COLUMNS = [
    'SOC 1' => 'emp_soc1_dol',
    'SOC 2' => 'emp_soc2_dol',
    'HIPAA' => 'emp_hipaa_dol',
    'HITRUST' => 'emp_hitrust_dol',
    'FISMA' => 'emp_fisma_dol',
];

// engagement_timeline columns that hold an actual date (not a completion
// timestamp) — presence of any one of these means the timeline has been
// filled out at all, mirrors TIMELINE_STEPS in this page's JS.
const TIMELINE_DATE_COLUMNS = [
    'internal_planning_call_date', 'planning_memo_date', 'irl_due_date', 'client_planning_call_date',
    'fieldwork_client_calls_start_date', 'fieldwork_client_calls_end_date',
    'fieldwork_documentation_start_date', 'fieldwork_documentation_end_date',
    'leadsheet_date', 'conclusion_memo_date', 'draft_report_due_date', 'final_report_date', 'archive_date',
];

// Onboarding completeness for one engagement: team assigned, DOL assigned
// for whatever audit types actually need it (see DOL_AUDIT_TYPE_COLUMNS —
// PCI/ISO have no DOL column at all, so those are never flagged for
// missing DOL), a timeline that's actually been filled out (no row at all,
// or a row with every date still blank, both count as not filled out —
// create-engagement.php doesn't create one automatically), and a planning
// doc uploaded (eng_planning_doc). Returns every missing piece, in the
// rough order they'd naturally get done, so callers can show either the
// first one (a compact row flag) or the full list (the drawer banner).
//
// PCI-only engagements (audit type is PCI and nothing else) are tracked
// less formally in practice — per Garrett, some are tracked with a filled
// timeline, others with just a planning doc — so neither is independently
// required for them; only having NEITHER counts as missing. This mirrors
// how DOL is already never required for PCI. Scoped to PCI specifically
// (not extended to ISO, even though ISO shares PCI's no-DOL-column
// treatment above) since that's what was actually asked for.
function getSetupInfo($eng, $teamLookup, $timelineLookup) {
    $engIdno = $eng['eng_idno'];
    $members = $teamLookup[$engIdno] ?? [];
    $hasTeam = count($members) > 0;

    $auditTypes = array_values(array_filter(array_map('trim', explode(',', (string) ($eng['eng_audit_type'] ?? '')))));
    $isPciOnly = count($auditTypes) === 1 && $auditTypes[0] === 'PCI';
    $applicableTypes = array_intersect($auditTypes, array_keys(DOL_AUDIT_TYPE_COLUMNS));

    $dolComplete = true;
    foreach ($applicableTypes as $type) {
        $col = DOL_AUDIT_TYPE_COLUMNS[$type];
        $covered = false;
        foreach ($members as $member) {
            if (!empty($member[$col])) { $covered = true; break; }
        }
        if (!$covered) { $dolComplete = false; break; }
    }
    // Nothing to assign DOL against yet without a team, but that's already
    // covered by $hasTeam — don't double-flag by also failing DOL.
    if (!$hasTeam) $dolComplete = true;

    $timeline = $timelineLookup[$engIdno] ?? null;
    $hasTimeline = false;
    if ($timeline) {
        foreach (TIMELINE_DATE_COLUMNS as $col) {
            if (!empty($timeline[$col])) { $hasTimeline = true; break; }
        }
    }

    $hasPlanningDoc = !empty($eng['eng_planning_doc']);

    $missing = [];
    if (!$hasTeam) $missing[] = 'No team';
    if ($isPciOnly) {
        if (!$hasTimeline && !$hasPlanningDoc) $missing[] = 'No timeline or planning doc';
    } else {
        if (!$hasTimeline) $missing[] = 'No timeline';
        if (!$hasPlanningDoc) $missing[] = 'No planning doc';
    }
    if ($hasTeam && !$dolComplete) $missing[] = 'DOL incomplete';

    return [
        'has_team' => $hasTeam,
        'dol_complete' => $dolComplete,
        'has_timeline' => $hasTimeline,
        'has_planning_doc' => $hasPlanningDoc,
        'missing' => $missing,
        'needs_setup' => !empty($missing),
    ];
}

// Renders the comma-separated eng_audit_type string as compact badges
// instead of a raw string that truncates mid-word in a fixed-width column.
function renderTypeBadges($rawTypes) {
    $types = array_filter(array_map('trim', explode(',', (string) $rawTypes)));
    if (empty($types)) {
        return '<span class="type-none">No audit type</span>';
    }
    $shown = array_slice($types, 0, 2);
    $remaining = count($types) - count($shown);
    $html = '<span class="type-badge" title="' . htmlspecialchars(implode(', ', $types)) . '">' . htmlspecialchars($shown[0]) . '</span>';
    if (isset($shown[1])) {
        $html .= '<span class="type-badge" title="' . htmlspecialchars(implode(', ', $types)) . '">' . htmlspecialchars($shown[1]) . '</span>';
    }
    if ($remaining > 0) {
        $html .= '<span class="type-more" title="' . htmlspecialchars(implode(', ', $types)) . '">+' . $remaining . '</span>';
    }
    return $html;
}

// "Due Soon" — date-driven (overdue / due within 5 days / ready to
// archive). This used to be called "Needs Attention"; that label now means
// setup completeness instead (below), so this is split out on its own.
$dueSoonCount = 0;
foreach ($activeEngagements as $e) {
    [, $state] = getDueInfo($e['eng_idno'], $timelineLookup);
    if (in_array($state, ['overdue', 'soon', 'archive_ready'], true)) $dueSoonCount++;
}

// "Needs Attention" — onboarding completeness: team, DOL, timeline, and
// planning doc. See getSetupInfo() above for exactly what each means.
$attentionCount = 0;
foreach ($activeEngagements as $e) {
    $setup = getSetupInfo($e, $teamLookup, $timelineLookup);
    if ($setup['needs_setup']) $attentionCount++;
}

// One-shot: true only on the page load immediately after a successful
// login (set by auth/login.php), so the "what's due" popup fires exactly
// once per login instead of on every reload/navigation back to this page.
$showDuePopupOnLoad = !empty($_SESSION['show_due_popup']);
unset($_SESSION['show_due_popup']);

$initials = '';
if (!empty($_SESSION['name'])) {
    foreach (explode(' ', trim($_SESSION['name'])) as $part) {
        if (!empty($part)) $initials .= strtoupper($part[0]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $showArchived ? 'Archived' : 'Engagements'; ?> - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles/main.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --ink: #1B3A5C;
            --ink-soft: #4A6483;
            --paper: #F4F6F8;
            --card: #FFFFFF;
            --line: #DCE1E7;
            --line-strong: #C2CAD3;
            --text: #16202B;
            --text-muted: #5B6B7C;
            --critical: #B3261E;
            --critical-tint: rgba(179, 38, 30, 0.07);
            --critical-tint-strong: rgba(179, 38, 30, 0.13);
            --caution: #A66A00;
            --good: #1F7A54;

            /* Role colors + legacy-name aliases for the engagement drawer,
               ported from engagement-details.php's Team/DOL section. */
            --manager: var(--ink);
            --staff: var(--good);
            --intern: var(--caution);
            --senior: #7A4FB0;
            --text-primary: var(--text);
            --text-secondary: var(--text-muted);
            --primary-blue: var(--ink);
            --danger-red: var(--critical);
            --success-green: var(--good);
        }
        body.dark-mode {
            --ink: #6E9FCB;
            --ink-soft: #7C93AA;
            --paper: #10161D;
            --card: #171F28;
            --line: #2A343E;
            --line-strong: #3C4854;
            --text: #E7ECF1;
            --text-muted: #93A1AF;
            --critical: #E5766F;
            --critical-tint: rgba(229, 118, 111, 0.1);
            --critical-tint-strong: rgba(229, 118, 111, 0.18);
            --caution: #D3A44E;
            --good: #5FB98A;
            --senior: #B79AE0;
        }

        * { box-sizing: border-box; }

        body {
            background: var(--paper);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-variant-numeric: tabular-nums;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; }

        /* ---------- header ---------- */
        .top-header { background: var(--card); border-bottom: 1px solid var(--line); padding: 0 1.75rem; position: sticky; top: 0; z-index: 100; }
        .header-inner { max-width: 1080px; margin: 0 auto; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 2.5rem; }
        .brand { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; text-decoration: none; }
        .brand-icon { width: 26px; height: 26px; border-radius: 7px; background: var(--ink); color: var(--card); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .brand-mark { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; color: var(--text); }

        .main-nav { display: flex; gap: 1.6rem; }
        .main-nav a { font-size: 13px; font-weight: 600; color: var(--text-muted); text-decoration: none; padding: 4px 0; border-bottom: 2px solid transparent; }
        .main-nav a.active { color: var(--text); border-bottom-color: var(--ink); }
        .main-nav a:hover { color: var(--text); }

        .header-right { display: flex; align-items: center; gap: 0.65rem; margin-left: auto; }
        .icon-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; font-size: 15px; }
        .icon-btn:hover { background: color-mix(in srgb, var(--ink) 8%, var(--paper)); color: var(--text); }

        /* ---------- "what's due" popup (replaces the old notification bell) ---------- */
        .due-popup-scrim {
            position: fixed; inset: 0; background: rgba(10, 14, 20, 0.5); z-index: 300;
            display: flex; align-items: center; justify-content: center; padding: 1.5rem;
            opacity: 0; pointer-events: none; transition: opacity 0.15s ease;
        }
        .due-popup-scrim.open { opacity: 1; pointer-events: auto; }
        .due-popup {
            background: var(--card); border: 1px solid var(--line); border-radius: 14px;
            width: 100%; max-width: 560px; max-height: 82vh; display: flex; flex-direction: column;
            box-shadow: 0 24px 64px rgba(0,0,0,0.28); transform: translateY(8px); transition: transform 0.15s ease;
        }
        .due-popup-scrim.open .due-popup { transform: translateY(0); }
        .due-popup-header { display: flex; align-items: flex-start; justify-content: space-between; padding: 1.25rem 1.4rem 1rem; border-bottom: 1px solid var(--line); }
        .due-popup-header h3 { font-size: 16px; font-weight: 700; margin: 0; }
        .due-popup-sub { font-size: 12px; color: var(--text-muted); margin-top: 3px; }
        .due-popup-close { border: none; background: transparent; color: var(--text-muted); cursor: pointer; font-size: 18px; padding: 2px; line-height: 1; }
        .due-popup-close:hover { color: var(--text); }
        .due-popup-body { overflow-y: auto; padding: 0.5rem 0; }
        .due-popup-section-label { font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); padding: 0.85rem 1.4rem 0.4rem; }
        .due-popup-item { display: flex; align-items: center; gap: 10px; padding: 0.65rem 1.4rem; cursor: pointer; border-bottom: 1px solid var(--line); }
        .due-popup-item:hover { background: var(--paper); }
        .due-popup-item:last-child { border-bottom: none; }
        .due-popup-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; background: var(--caution); }
        .due-popup-dot.overdue { background: var(--critical); }
        .due-popup-info { min-width: 0; flex: 1; }
        .due-popup-name { font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .due-popup-item-title { font-size: 11.5px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .due-popup-when { font-size: 11.5px; font-weight: 700; text-align: right; flex-shrink: 0; white-space: nowrap; }
        .due-popup-when.overdue { color: var(--critical); }
        .due-popup-when.upcoming { color: var(--caution); }
        .due-popup-empty { padding: 2.5rem 1.4rem; text-align: center; color: var(--text-muted); font-size: 13px; }
        .due-popup-footer { padding: 0.9rem 1.4rem; border-top: 1px solid var(--line); text-align: right; }

        /* ---------- new-engagement wizard: same card/scrim conventions as
           the due-popup above, but a 3-step flow instead of one long
           scrolling SweetAlert2 form ---------- */
        .eng-wizard-scrim {
            position: fixed; inset: 0; background: rgba(10, 14, 20, 0.5); z-index: 400;
            display: flex; align-items: center; justify-content: center; padding: 1.5rem;
            opacity: 0; pointer-events: none; transition: opacity 0.15s ease;
        }
        .eng-wizard-scrim.open { opacity: 1; pointer-events: auto; }
        .eng-wizard {
            background: var(--card); border: 1px solid var(--line); border-radius: 14px;
            width: 100%; max-width: 640px; max-height: 88vh; display: flex; flex-direction: column;
            box-shadow: 0 24px 64px rgba(0,0,0,0.28); transform: translateY(8px); transition: transform 0.15s ease;
        }
        .eng-wizard-scrim.open .eng-wizard { transform: translateY(0); }
        .eng-wizard-header { padding: 1.25rem 1.4rem 1.1rem; border-bottom: 1px solid var(--line); flex-shrink: 0; }
        .eng-wizard-header-top { display: flex; align-items: flex-start; justify-content: space-between; }
        .eng-wizard-header h3 { font-size: 16px; font-weight: 700; margin: 0; }
        .eng-wizard-close { border: none; background: transparent; color: var(--text-muted); cursor: pointer; font-size: 18px; padding: 2px; line-height: 1; }
        .eng-wizard-close:hover { color: var(--text); }

        .eng-wizard-steps { display: flex; gap: 0.6rem; margin-top: 1.1rem; }
        .eng-wizard-step { flex: 1; display: flex; flex-direction: column; gap: 6px; background: none; border: none; padding: 0; cursor: default; text-align: left; }
        .eng-wizard-step.clickable { cursor: pointer; }
        .eng-wizard-step-bar { height: 3px; border-radius: 2px; background: var(--line); }
        .eng-wizard-step.active .eng-wizard-step-bar, .eng-wizard-step.done .eng-wizard-step-bar { background: var(--ink); }
        .eng-wizard-step-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); }
        .eng-wizard-step.active .eng-wizard-step-label { color: var(--text); }

        .eng-wizard-body { overflow-y: auto; padding: 1.4rem; flex: 1; }
        .eng-wizard-panel { display: none; }
        .eng-wizard-panel.active { display: block; }

        .eng-field { margin-bottom: 1.15rem; }
        .eng-field label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; }
        .eng-field label .req { color: var(--critical); }
        .eng-input, .eng-textarea, .eng-date {
            width: 100%; padding: 0.65rem 0.75rem; border: 1px solid var(--line); border-radius: 8px;
            background: var(--paper); color: var(--text); font-size: 13.5px; font-family: inherit;
        }
        .eng-input:focus, .eng-textarea:focus, .eng-date:focus { outline: none; border-color: var(--ink); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 14%, transparent); }
        .eng-textarea { resize: vertical; min-height: 80px; }
        .eng-field-hint { font-size: 11px; color: var(--text-muted); margin-top: 0.5rem; }

        .eng-team-row { display: flex; align-items: center; gap: 0.65rem; padding: 0.6rem 0.7rem; background: var(--paper); border: 1px solid var(--line); border-radius: 8px; margin-top: 0.6rem; }
        .eng-team-row .avatar { width: 28px; height: 28px; border-radius: 7px; color: var(--card); font-weight: 700; font-size: 10.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .eng-team-row .info { flex: 1; min-width: 0; }
        .eng-team-row .name { font-size: 12.5px; font-weight: 700; color: var(--text); }
        .eng-team-row .role { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); }
        .eng-team-row .hours-input { width: 60px; padding: 0.4rem 0.5rem; border: 1px solid var(--line); border-radius: 7px; background: var(--card); color: var(--text); font-size: 13px; text-align: center; flex-shrink: 0; }
        .eng-team-row .hours-input:focus { outline: none; border-color: var(--ink); }
        .eng-team-row .hours-suffix { font-size: 11px; color: var(--text-muted); font-weight: 600; flex-shrink: 0; }
        .eng-team-row .remove-btn { border: none; background: transparent; color: var(--text-muted); cursor: pointer; font-size: 16px; padding: 0 2px; line-height: 1; flex-shrink: 0; }
        .eng-team-row .remove-btn:hover { color: var(--critical); }

        .eng-segmented { display: flex; background: var(--paper); border-radius: 8px; padding: 3px; gap: 2px; border: 1px solid var(--line); }
        .eng-segmented button { flex: 1; border: none; background: transparent; padding: 8px 6px; border-radius: 6px; font-size: 12px; font-weight: 600; color: var(--text-muted); cursor: pointer; }
        .eng-segmented button.active { background: var(--card); color: var(--text); box-shadow: 0 1px 2px rgba(0,0,0,0.08); }

        .eng-chip-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem; }
        .eng-chip { display: flex; align-items: center; gap: 0.6rem; padding: 0.65rem 0.8rem; border: 1px solid var(--line); border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--text); background: var(--paper); }
        .eng-chip.checked { border-color: var(--ink); background: color-mix(in srgb, var(--ink) 8%, var(--paper)); color: var(--ink); }
        .eng-chip input { width: 15px; height: 15px; accent-color: var(--ink); flex-shrink: 0; }

        .eng-switch-row { display: flex; align-items: center; justify-content: space-between; padding: 0.9rem 1rem; border: 1px solid var(--line); border-radius: 8px; background: var(--paper); }
        .eng-switch-label { font-size: 13px; font-weight: 600; color: var(--text); }
        .eng-switch-hint { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
        .eng-switch { position: relative; width: 38px; height: 22px; flex-shrink: 0; display: inline-block; cursor: pointer; }
        .eng-switch input { position: absolute; opacity: 0; width: 100%; height: 100%; margin: 0; cursor: pointer; }
        .eng-switch-track { position: absolute; inset: 0; background: var(--line-strong); border-radius: 20px; transition: background 0.15s; pointer-events: none; }
        .eng-switch input:checked + .eng-switch-track { background: var(--ink); }
        .eng-switch-thumb { position: absolute; top: 2px; left: 2px; width: 18px; height: 18px; border-radius: 50%; background: #fff; transition: transform 0.15s; pointer-events: none; }
        .eng-switch input:checked ~ .eng-switch-thumb { transform: translateX(16px); }

        .eng-soc-box { margin-top: 0.9rem; padding: 1rem 1.1rem; background: color-mix(in srgb, var(--ink) 8%, transparent); border-radius: 8px; border-left: 3px solid var(--ink); }

        .eng-manager-selected { display: flex; align-items: center; gap: 0.6rem; margin-top: 0.6rem; padding: 0.5rem 0.7rem; background: color-mix(in srgb, var(--manager) 8%, transparent); border: 1px solid color-mix(in srgb, var(--manager) 25%, var(--line)); border-radius: 8px; }
        .eng-manager-selected .avatar { width: 26px; height: 26px; border-radius: 7px; background: var(--manager); color: var(--card); font-size: 10.5px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .eng-manager-selected .name { font-size: 12.5px; font-weight: 700; color: var(--text); flex: 1; }
        .eng-manager-selected .clear-btn { border: none; background: transparent; color: var(--text-muted); cursor: pointer; font-size: 16px; padding: 0 2px; line-height: 1; }
        .eng-manager-selected .clear-btn:hover { color: var(--critical); }

        .eng-review-summary { background: var(--paper); border: 1px solid var(--line); border-radius: 8px; padding: 1rem 1.1rem; margin: 0 0 1.15rem; display: flex; flex-direction: column; gap: 0.6rem; }
        .eng-review-row { display: flex; justify-content: space-between; gap: 1rem; font-size: 12.5px; margin: 0; }
        .eng-review-row dt { color: var(--text-muted); font-weight: 600; }
        .eng-review-row dd { color: var(--text); font-weight: 600; text-align: right; margin: 0; max-width: 60%; }

        .eng-wizard-footer { display: flex; align-items: center; justify-content: space-between; padding: 1rem 1.4rem; border-top: 1px solid var(--line); gap: 0.75rem; flex-shrink: 0; }
        .eng-wizard-footer-right { display: flex; gap: 0.6rem; }
        .eng-btn { padding: 8px 16px; border-radius: 7px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: 1px solid var(--line); background: var(--card); color: var(--text); }
        .eng-btn:hover { border-color: var(--line-strong); }
        .eng-btn-primary { background: var(--ink); border-color: var(--ink); color: var(--card); }
        .eng-btn-primary:hover { opacity: 0.92; }
        .eng-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        @media (max-width: 640px) {
            .eng-wizard-steps { gap: 0.4rem; }
            .eng-wizard-step-label { font-size: 9.5px; }
            .eng-chip-grid { grid-template-columns: 1fr; }
        }

        .profile-section { position: relative; margin-left: 0.4rem; padding-left: 0.75rem; border-left: 1px solid var(--line); }
        .profile-wrapper { display: flex; align-items: center; gap: 6px; cursor: pointer; }
        .profile-btn { width: 30px; height: 30px; border-radius: 50%; background: var(--ink); color: var(--card); border: none; font-weight: 700; font-size: 11.5px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .profile-dropdown-toggle { border: none; background: transparent; color: var(--text-muted); cursor: pointer; padding: 2px; }
        .profile-dropdown {
            position: absolute; top: calc(100% + 8px); right: 0; width: 240px;
            background: var(--card); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,0.14);
            display: none; z-index: 60;
        }
        .profile-dropdown.active { display: block; }
        .profile-dropdown-header { display: flex; gap: 10px; align-items: center; padding: 0.9rem 1rem; border-bottom: 1px solid var(--line); }
        .profile-dropdown-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: var(--card); font-weight: 700; font-size: 12.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .profile-dropdown-name { font-size: 13px; font-weight: 700; }
        .profile-dropdown-email { font-size: 11.5px; color: var(--text-muted); }
        .profile-dropdown-menu { padding: 0.4rem; }
        .profile-dropdown-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 12.5px; color: var(--text); text-decoration: none; }
        .profile-dropdown-item:hover { background: var(--paper); }
        .profile-dropdown-item.logout { color: var(--critical); }

        /* ---------- page body ---------- */
        .main-container { max-width: 1080px; margin: 0 auto; padding: 2.25rem 1.75rem 4rem; }

        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; margin-bottom: 0.35rem; flex-wrap: wrap; }
        .page-head h1 { font-size: 24px; margin: 0; font-weight: 700; letter-spacing: -0.015em; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin: 4px 0 0; }

        .head-actions { display: flex; align-items: center; gap: 1.5rem; }
        .tab-toggle { display: flex; gap: 1.4rem; }
        .tab-toggle a { border: none; background: transparent; padding: 4px 0; font-size: 13px; font-weight: 600; color: var(--text-muted); cursor: pointer; border-bottom: 2px solid transparent; text-decoration: none; }
        .tab-toggle a.active { color: var(--text); border-bottom-color: var(--ink); }
        .tab-toggle .count { font-weight: 400; color: var(--text-muted); }

        .btn-new-engagement { background: transparent; color: var(--ink); border: 1px solid var(--ink); padding: 7px 14px; border-radius: 4px; font-size: 12.5px; font-weight: 600; display: flex; align-items: center; gap: 6px; cursor: pointer; }
        .btn-new-engagement:hover { background: var(--ink); color: var(--card); }

        hr.rule { border: none; border-top: 1px solid var(--line); margin: 1.1rem 0 1.5rem; }

        /* ---------- toolbar ---------- */
        .toolbar { display: flex; gap: 1.5rem; align-items: center; margin-bottom: 1.75rem; }
        .search-wrap { position: relative; width: 260px; flex-shrink: 0; }
        .search-wrap i { position: absolute; left: 0; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: 13px; }
        .search-input { width: 100%; padding: 6px 6px 6px 22px; border: none; border-bottom: 1px solid var(--line); background: transparent; color: var(--text); font-size: 13px; }
        .search-input:focus { outline: none; border-bottom-color: var(--ink); }

        .attention-link { background: none; border: none; padding: 0; font-size: 13px; font-weight: 600; color: var(--critical); cursor: pointer; display: flex; align-items: center; gap: 6px; }
        .attention-link .swatch { width: 8px; height: 8px; border-radius: 1px; background: var(--critical); }
        .attention-link.active { text-decoration: underline; text-underline-offset: 3px; }
        .attention-link--setup { color: var(--caution); }
        .attention-link--setup .swatch { background: var(--caution); }
        .toolbar-flags { display: flex; align-items: center; gap: 1.5rem; }

        .result-note { margin-left: auto; font-size: 12px; color: var(--text-muted); }

        /* ---------- register list ---------- */
        .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: var(--text-muted); margin: 1.6rem 0 0.5rem; display: flex; align-items: center; gap: 8px; }
        .section-label:first-child { margin-top: 0; }
        .section-label .swatch { width: 7px; height: 7px; border-radius: 1px; flex-shrink: 0; }
        .section-label .n { color: var(--text-muted); font-weight: 400; }

        .register { border-top: 1px solid var(--line); }
        .reg-row { display: flex; align-items: center; gap: 1.25rem; padding: 13px 8px 13px 4px; border-bottom: 1px solid var(--line); cursor: pointer; position: relative; transition: background-color 0.1s ease; }
        .reg-row.is-critical { background: var(--critical-tint); }
        .reg-row.is-needs-setup:not(.is-critical) { background: color-mix(in srgb, var(--caution) 7%, transparent); }
        .reg-row.is-archived { opacity: 0.62; }
        /* :hover rules gated to actual mouse/trackpad devices — on a touch
           screen, an element with :hover styling makes iOS/Android browsers
           spend the first tap entering the hover state (revealing
           row-actions here) instead of firing click, so opening a row took
           two taps. Touch devices never match this query, so :hover simply
           never engages and the very first tap opens the drawer. */
        @media (hover: hover) and (pointer: fine) {
            .reg-row:hover { background: color-mix(in srgb, var(--ink) 6%, var(--paper)); }
            .reg-row:hover .row-actions { opacity: 1; }
            .reg-row.is-critical:hover { background: var(--critical-tint-strong); }
            .reg-row.is-needs-setup:not(.is-critical):hover { background: color-mix(in srgb, var(--caution) 12%, transparent); }
        }

        .reg-tick { width: 3px; align-self: stretch; border-radius: 2px; flex-shrink: 0; }
        .reg-id { font-size: 11.5px; color: var(--text-muted); width: 90px; flex-shrink: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reg-main { flex: 0 1 420px; min-width: 0; }
        .reg-name { font-weight: 600; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reg-sub-row { display: flex; align-items: center; gap: 6px; min-width: 0; margin-top: 1px; }
        .reg-sub { font-size: 12px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reg-setup-flag { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: var(--caution); background: color-mix(in srgb, var(--caution) 14%, transparent); padding: 1px 6px; border-radius: 4px; white-space: nowrap; flex-shrink: 0; display: inline-flex; align-items: center; gap: 3px; }
        .reg-setup-flag i { font-size: 9px; }
        .reg-type { display: flex; align-items: center; gap: 4px; width: 170px; flex-shrink: 0; overflow: hidden; }
        .type-badge { font-size: 10.5px; font-weight: 700; color: var(--ink); background: color-mix(in srgb, var(--ink) 12%, transparent); padding: 2px 7px; border-radius: 5px; white-space: nowrap; flex-shrink: 0; }
        .type-more { font-size: 11px; color: var(--text-muted); font-weight: 600; flex-shrink: 0; }
        .type-none { font-size: 12.5px; color: var(--text-muted); }

        .reg-due { font-size: 12.5px; font-weight: 600; width: 96px; flex-shrink: 0; text-align: right; }
        .reg-due .tag { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .reg-due.overdue, .reg-due.overdue .tag { color: var(--critical); }
        .reg-due.soon .tag { color: var(--caution); }
        .reg-due.archive-ready { color: var(--good); font-size: 11.5px; }

        .row-actions { display: flex; gap: 2px; opacity: 0; transition: opacity 0.12s ease; width: 56px; justify-content: flex-end; flex-shrink: 0; }
        .row-actions button { width: 26px; height: 26px; border: none; background: transparent; color: var(--text-muted); border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 13px; }
        .row-actions button:hover { background: var(--line); color: var(--text); }
        .row-actions button.danger:hover { background: var(--critical-tint-strong); color: var(--critical); }

        .list-head { display: flex; align-items: center; gap: 1.25rem; padding: 0 4px 6px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--text-muted); }
        .list-head .lh-tick { width: 3px; flex-shrink: 0; }
        .list-head .lh-id { width: 90px; flex-shrink: 0; }
        .list-head .lh-main { flex: 0 1 420px; }
        .list-head .lh-type { width: 170px; flex-shrink: 0; }
        .list-head .lh-due { width: 96px; flex-shrink: 0; text-align: right; }
        .list-head .lh-actions { width: 56px; flex-shrink: 0; }

        .empty-state { padding: 2.5rem 1rem; text-align: center; color: var(--text-muted); font-size: 13px; }

        /* ---------- stat row ---------- */
        .stat-row { display: flex; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .stat-card { background: var(--card); border: 1px solid var(--line); border-radius: 11px; padding: 0.85rem 1.1rem; flex: 0 0 auto; display: flex; align-items: center; gap: 0.7rem; min-width: 160px; }
        .stat-card .value { font-size: 22px; font-weight: 800; letter-spacing: -0.02em; line-height: 1; }
        .stat-card .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--text-muted); margin-top: 3px; }
        .stat-card--attention { background: var(--critical-tint); border-color: color-mix(in srgb, var(--critical) 30%, var(--line)); }
        .stat-card--attention .value, .stat-card--attention .label { color: var(--critical); }
        .stat-card--setup { background: color-mix(in srgb, var(--caution) 8%, transparent); border-color: color-mix(in srgb, var(--caution) 30%, var(--line)); }
        .stat-card--setup .value, .stat-card--setup .label { color: var(--caution); }

        .dist-card { background: var(--card); border: 1px solid var(--line); border-radius: 11px; padding: 0.85rem 1.1rem; flex: 1; min-width: 260px; display: flex; flex-direction: column; justify-content: center; gap: 0.5rem; }
        .dist-bar { display: flex; height: 9px; border-radius: 5px; overflow: hidden; background: var(--line); }
        .dist-seg { height: 100%; }
        .dist-legend { display: flex; gap: 1.1rem; flex-wrap: wrap; }
        .dist-legend-item { display: flex; align-items: center; gap: 0.4rem; font-size: 11.5px; color: var(--text-muted); }
        .dist-legend-item .dot { width: 7px; height: 7px; border-radius: 2px; flex-shrink: 0; }
        .dist-legend-item b { color: var(--text); font-weight: 700; }

        /* ---------- toast ---------- */
        .custom-toast {
            position: fixed; left: 50%; bottom: 28px; transform: translateX(-50%) translateY(16px);
            background: var(--text); color: var(--card); padding: 10px 16px; border-radius: 8px;
            font-size: 13px; display: flex; align-items: center; gap: 10px;
            box-shadow: 0 10px 28px rgba(0,0,0,0.2); z-index: 200;
            opacity: 0; transition: opacity 0.18s ease, transform 0.18s ease;
        }
        .custom-toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .custom-toast.hide { opacity: 0; }

        /* ========== MANAGE TEAM MEMBERS MODAL (ported from engagement-details.php) ========== */
        .team2-manage-body { display: flex; gap: 1.5rem; height: 100%; min-height: 500px; }
        .team2-manage-left { flex: 1.3; display: flex; flex-direction: column; overflow: hidden; min-width: 300px; }
        .team2-manage-left h3 { font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .team2-manage-right { width: 280px; display: flex; flex-direction: column; gap: 1.25rem; padding-left: 1.5rem; border-left: 1px solid var(--line); flex-shrink: 0; }
        .team2-manage-right h4 { font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .team2-list-scroll { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.6rem; padding-right: 0.4rem; }
        .team2-card-row { padding: 0.8rem; background: var(--paper); border: 1px solid var(--line); border-radius: 10px; }
        .team2-card-row-top { display: flex; align-items: flex-start; gap: 0.7rem; }
        .team2-card-row .team2-icon-btns { margin-left: auto; display: flex; gap: 4px; flex-shrink: 0; }
        .team2-card-row .team2-icon-btns button { width: 28px; height: 28px; border: 1px solid var(--line); background: var(--card); color: var(--text-secondary); border-radius: 6px; cursor: pointer; }
        .team2-card-row .team2-icon-btns button:hover { border-color: var(--ink); color: var(--ink); }
        .team2-card-row .team2-icon-btns button.danger:hover { border-color: var(--critical); color: var(--critical); }
        .team2-avatar { width: 34px; height: 34px; border-radius: 8px; color: #fff; font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .team2-name { font-weight: 600; font-size: 13.5px; color: var(--text-primary); }
        .team2-role-label { font-size: 11px; font-weight: 700; text-transform: capitalize; color: var(--text-secondary); }
        .team2-stat-mini { padding: 0.7rem 0.8rem; border-left: 3px solid var(--ink); border-radius: 6px; background: color-mix(in srgb, var(--ink) 7%, var(--card)); margin-bottom: 0.6rem; }
        .team2-stat-mini .n { font-size: 18px; font-weight: 800; color: var(--text-primary); }
        .team2-stat-mini .l { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); }
        .team2-dol-line { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .team2-dol-type-tag { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-secondary); width: 44px; flex-shrink: 0; }
        .team2-chip-row { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
        .team2-chip { font-size: 10.5px; font-weight: 700; color: var(--ink); background: color-mix(in srgb, var(--ink) 12%, transparent); padding: 3px 8px 3px 6px; border-radius: 5px; border-left: 2px solid var(--ink); }
        .team2-chip.t-soc2 { color: var(--senior); background: color-mix(in srgb, var(--senior) 12%, transparent); border-left-color: var(--senior); }
        .team2-no-dol { font-size: 11px; color: var(--critical); font-weight: 600; }

        .team2-field { margin-bottom: 0.6rem; }
        .team2-field input[type="text"] { width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 7px; background: var(--card); color: var(--text-primary); font-size: 13px; }
        .team2-field input:focus { outline: none; border-color: var(--ink); }

        .team2-ac-wrap { position: relative; }
        .team2-ac-list {
            position: absolute; top: calc(100% + 4px); left: 0; right: 0; background: var(--card); border: 1px solid var(--line);
            border-radius: 8px; box-shadow: 0 8px 22px rgba(0,0,0,0.16); max-height: 220px; overflow-y: auto; z-index: 10;
        }
        .team2-ac-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; cursor: pointer; font-size: 13px; color: var(--text-primary); }
        .team2-ac-item:hover { background: var(--paper); }
        .team2-ac-item .role { margin-left: auto; font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--text-secondary); }
        .team2-ac-empty { padding: 10px; font-size: 12.5px; color: var(--text-secondary); }
        .team2-ac-newbtn { display: flex; align-items: center; gap: 6px; padding: 9px 10px; font-size: 12.5px; font-weight: 600; color: var(--ink); cursor: pointer; border-top: 1px solid var(--line); }
        .team2-ac-newbtn:hover { background: var(--paper); }
        .team2-new-emp-picker { padding: 10px; }
        .team2-new-emp-actions { display: flex; gap: 8px; margin-top: 10px; }
        .team2-btn { padding: 9px; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; text-align: center; border: none; }
        .team2-btn-primary { background: var(--ink); color: var(--card); }
        .team2-btn-primary:hover { background: color-mix(in srgb, var(--ink) 85%, black); }
        .team2-btn-secondary { background: var(--card); color: var(--text-secondary); border: 1px solid var(--line); }
        .team2-btn-secondary:hover { border-color: var(--line-strong); color: var(--text-primary); }
        .team2-new-emp-actions .team2-btn { flex: 1; width: auto; }
        .role-pick-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
        .role-pick-btn { padding: 8px 4px; border-radius: 7px; border: 1px solid var(--line); background: var(--paper); color: var(--text-secondary); font-size: 11.5px; font-weight: 700; cursor: pointer; text-align: center; }
        .role-pick-btn:hover { border-color: var(--line-strong); }
        .role-pick-btn.active { border-color: var(--ink); background: color-mix(in srgb, var(--ink) 12%, transparent); color: var(--ink); }

        /* ========== EDIT TEAM MEMBER MODAL (ported from engagement-details.php) ========== */
        .team2-edit-header { display: flex; align-items: center; gap: 0.9rem; padding: 0 0 1.1rem; margin-bottom: 1.1rem; border-bottom: 1px solid var(--line); }
        .team2-avatar-lg { width: 42px; height: 42px; border-radius: 10px; color: #fff; font-weight: 700; font-size: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .team2-edit-name { font-weight: 700; font-size: 15px; color: var(--text-primary); }
        .team2-edit-role-badge { display: inline-block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 3px; padding: 2px 7px; border-radius: 5px; }
        .team2-edit-role-badge.manager { color: var(--manager); background: color-mix(in srgb, var(--manager) 14%, transparent); }
        .team2-edit-role-badge.senior { color: var(--senior); background: color-mix(in srgb, var(--senior) 14%, transparent); }
        .team2-edit-role-badge.staff { color: var(--staff); background: color-mix(in srgb, var(--staff) 14%, transparent); }
        .team2-edit-role-badge.intern { color: var(--intern); background: color-mix(in srgb, var(--intern) 14%, transparent); }

        .team2-segmented { display: flex; background: var(--paper); border-radius: 8px; padding: 3px; gap: 2px; }
        .team2-segmented button { flex: 1; border: none; background: transparent; padding: 7px 4px; border-radius: 6px; font-size: 11.5px; font-weight: 600; color: var(--text-secondary); cursor: pointer; }
        .team2-segmented button.active { background: var(--card); color: var(--text-primary); box-shadow: 0 1px 2px rgba(0,0,0,0.08); }

        .team2-edit-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); margin-bottom: 6px; display: block; }
        .team2-dol-columns { display: flex; flex-direction: column; gap: 0.8rem; }
        .team2-dol-col-label { font-size: 10px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 4px; }
        .team2-tag-input-box {
            display: flex; flex-wrap: wrap; gap: 5px; align-items: center; padding: 6px 8px;
            border: 1px solid var(--line); border-radius: 7px; background: var(--paper); min-height: 40px; cursor: text;
        }
        .team2-tag-input-box:focus-within { border-color: var(--ink); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 12%, transparent); }
        .team2-tag-input-box .tags { display: flex; flex-wrap: wrap; gap: 5px; }
        .team2-tag-chip { display: inline-flex; align-items: center; gap: 5px; background: color-mix(in srgb, var(--ink) 13%, transparent); color: var(--ink); font-size: 11.5px; font-weight: 700; padding: 3px 6px 3px 9px; border-radius: 5px; }
        .team2-tag-chip button { border: none; background: none; color: inherit; cursor: pointer; display: flex; padding: 0; opacity: 0.65; }
        .team2-tag-chip button:hover { opacity: 1; }
        .team2-tag-input-box input { border: none; background: none; outline: none; font-size: 13px; color: var(--text-primary); flex: 1; min-width: 90px; padding: 3px 2px; }
        .team2-tag-hint { font-size: 11px; color: var(--text-secondary); margin-top: 5px; }

        /* ========== MANAGE TEAM — SPLIT WORKSPACE (roster + detail panel) ========== */
        .team3-modal-popup {
            max-height: 640px !important;
            height: 640px !important;
            display: flex !important;
            flex-direction: column !important;
            width: 980px !important;
            max-width: 980px !important;
            padding: 0 !important;
        }
        .team3-modal-popup .swal2-title { padding: 1.1rem 1.3rem 0.9rem; margin: 0; border-bottom: 1px solid var(--line); font-size: 16px; }
        .team3-modal-popup .swal2-html-container {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        .team3-body { display: flex; flex: 1; overflow: hidden; text-align: left; }
        .team3-left { width: 300px; flex-shrink: 0; display: flex; flex-direction: column; border-right: 1px solid var(--line); background: var(--paper); }
        .team3-left-head { padding: 0.9rem 0.9rem 0.7rem; display: flex; flex-direction: column; gap: 0.6rem; }
        .team3-left-head input[type="text"] { width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 7px; background: var(--card); color: var(--text-primary); font-size: 12.5px; }
        .team3-left-head input:focus { outline: none; border-color: var(--ink); }
        .team3-add-btn {
            display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%;
            padding: 8px; border-radius: 7px; border: 1px dashed var(--line-strong); background: transparent;
            color: var(--ink); font-size: 12px; font-weight: 700; cursor: pointer;
        }
        .team3-add-btn:hover { background: color-mix(in srgb, var(--ink) 8%, transparent); }
        .team3-list { flex: 1; overflow-y: auto; padding: 0 0.5rem 0.75rem; }
        .team3-item { display: flex; align-items: flex-start; gap: 9px; padding: 8px; border-radius: 8px; cursor: pointer; }
        .team3-item:hover { background: var(--card); }
        .team3-item.selected { background: color-mix(in srgb, var(--ink) 10%, var(--card)); }
        .team3-item-info { flex: 1; min-width: 0; }
        .team3-item-name { font-weight: 700; font-size: 12.5px; color: var(--text-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .team3-item-sub { font-size: 10.5px; color: var(--text-secondary); margin-top: 1px; }
        .team3-item-dols { display: flex; flex-wrap: wrap; gap: 3px; margin-top: 5px; }
        .team3-item-dols .team2-chip { font-size: 9px; padding: 1px 5px; border-left-width: 2px; }
        .team3-empty-list { padding: 2rem 1rem; text-align: center; color: var(--text-secondary); font-size: 12.5px; }

        .team3-right { flex: 1; overflow-y: auto; padding: 1.4rem 1.6rem; }
        .team3-right-empty { height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; color: var(--text-secondary); gap: 10px; }
        .team3-right-empty i { font-size: 34px; opacity: 0.35; }

        .team3-detail-head { display: flex; align-items: center; gap: 0.9rem; margin-bottom: 1.3rem; }
        .team3-field { margin-bottom: 1.1rem; max-width: 420px; }
        .team3-dol-columns { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px,1fr)); gap: 0.9rem; max-width: 640px; }
        .team3-detail-actions { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 1.6rem; padding-top: 1.2rem; border-top: 1px solid var(--line); max-width: 640px; }
        .team3-remove-confirm { display: flex; align-items: center; gap: 10px; font-size: 12px; color: var(--text-primary); background: var(--critical-tint); border-radius: 7px; padding: 8px 10px; }
        .team3-remove-confirm b { color: var(--critical); }

        .team3-add-panel { max-width: 420px; }
        .team3-search-results { border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
        .team3-saved-flash { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; color: var(--good); opacity: 0; transition: opacity 0.2s; }
        .team3-saved-flash.show { opacity: 1; }

        .milestone-modal-popup {
            max-height: 600px !important;
            height: 600px !important;
            display: flex !important;
            flex-direction: column !important;
            width: 800px !important;
            max-width: 800px !important;
        }
        .milestone-modal-popup .swal2-html-container {
            flex: 1 !important;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
            padding: 0 !important;
            width: 100% !important;
        }

        @media (max-width: 720px) {
            .main-nav { display: none; }
            .search-wrap { width: 100%; }
            .toolbar { flex-wrap: wrap; }
            .reg-type, .list-head .lh-type { display: none; }
        }

        /* Phone-width header: the full wordmark plus the dark-mode/profile
           icons don't fit in a ~375-430px viewport at the desktop
           padding/gap, which was pushing header-right off the right edge
           entirely (invisible without scrolling). Drop to icon-only
           branding and tighten spacing so everything stays on screen. */
        @media (max-width: 480px) {
            .top-header { padding: 0 1rem; }
            .header-inner { gap: 0.6rem; }
            .brand-mark { display: none; }
            .header-right { gap: 0.35rem; }
            .profile-section { padding-left: 0.5rem; margin-left: 0.2rem; }

            /* Each row was cramming a fixed 90px ID column, a fixed 96px
               due-date column, and a 56px-wide (invisible but still
               occupying space) actions column onto one line — leaving the
               client name almost no room, so it collapsed to a single
               letter before the ellipsis kicked in. Reflow into a 2-line
               card instead: client name + manager get the full width on
               their own line, ID and due date wrap to a second line below. */
            .list-head { display: none; }
            .reg-row { flex-wrap: wrap; row-gap: 4px; column-gap: 0.6rem; padding: 12px 6px; }
            .reg-tick { order: 1; }
            .reg-main { order: 2; flex: 1 1 calc(100% - 14px); min-width: 0; }
            .reg-id { order: 3; width: auto; }
            .reg-due { order: 4; width: auto; margin-left: auto; }
            .row-actions { display: none; }
        }

        /* ========== SWEETALERT2 STYLING ==========
           Hardcoded per-mode (not var()-indirected) on purpose: SweetAlert2 renders
           its popup as a direct child of <body>, appended dynamically after load,
           and custom-property indirection through that context was not resolving
           reliably in practice — text stayed dark-on-dark even with !important.
           Matches the same light/dark hex pairs swalColors() already uses in JS. */
        .swal2-container { z-index: 2000; }
        .swal2-popup {
            background: #FFFFFF !important;
            border: 1px solid #DCE1E7;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            max-width: 550px;
            width: calc(100vw - 2rem);
            overflow-x: hidden;
        }
        .swal2-title { color: #16202B !important; font-size: 22px; font-weight: 700; margin-bottom: 1.5rem; line-height: 1.3; padding: 0; }
        .swal2-html-container, .swal2-html-container * { color: #16202B !important; }
        .swal2-input, select.swal2-input, textarea.swal2-input {
            background: #F4F6F8 !important;
            border: 1px solid #DCE1E7 !important;
            color: #16202B !important;
            border-radius: 6px;
            padding: 0.5rem 0.6rem !important;
            font-size: 13px !important;
            transition: all 0.2s;
            width: 100% !important;
            box-sizing: border-box;
            margin: 0 !important;
        }
        .swal2-input:focus { border-color: #1B3A5C !important; box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.1); outline: none; }
        .swal2-input::placeholder { color: #5B6B7C !important; opacity: 1; }
        .swal2-actions { gap: 0.75rem; margin-top: 1.5rem; display: flex; justify-content: center; padding: 0; margin-left: 0; margin-right: 0; margin-bottom: 0; }
        .swal2-confirm, .swal2-cancel {
            flex: 1; max-width: 200px; margin: 0 !important; padding: 0.7rem 1.5rem !important;
            border-radius: 8px; font-weight: 600; font-size: 13px; transition: all 0.2s; min-width: 0; height: auto;
        }
        .swal2-confirm { background: #1B3A5C !important; color: #fff !important; border: none; }
        .swal2-confirm:hover { background: #14283F !important; box-shadow: 0 4px 12px rgba(27, 58, 92, 0.3); }
        .swal2-confirm:focus { outline: none; box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.2); }
        .swal2-cancel { background: #DCE1E7 !important; color: #16202B !important; border: 1px solid #DCE1E7; }
        .swal2-cancel:hover { background: rgba(27, 58, 92, 0.05) !important; border-color: #1B3A5C; color: #1B3A5C !important; }
        .swal2-cancel:focus { outline: none; box-shadow: 0 0 0 3px rgba(27, 58, 92, 0.1); }

        body.dark-mode .swal2-popup { background: #171F28 !important; border-color: #2A343E; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4); }
        body.dark-mode .swal2-title { color: #E7ECF1 !important; }
        body.dark-mode .swal2-html-container, body.dark-mode .swal2-html-container * { color: #E7ECF1 !important; }
        body.dark-mode .swal2-input, body.dark-mode select.swal2-input, body.dark-mode textarea.swal2-input {
            background: #10161D !important; border-color: #2A343E !important; color: #E7ECF1 !important;
        }
        body.dark-mode .swal2-input:focus { border-color: #6E9FCB !important; box-shadow: 0 0 0 3px rgba(110, 159, 203, 0.15); }
        body.dark-mode .swal2-input::placeholder { color: #93A1AF !important; }
        body.dark-mode .swal2-confirm { background: #6E9FCB !important; color: #10161D !important; }
        body.dark-mode .swal2-confirm:hover { background: #8CB4D8 !important; box-shadow: 0 4px 12px rgba(110, 159, 203, 0.3); }
        body.dark-mode .swal2-confirm:focus { box-shadow: 0 0 0 3px rgba(110, 159, 203, 0.25); }
        body.dark-mode .swal2-cancel { background: #2A343E !important; color: #E7ECF1 !important; border-color: #2A343E; }
        body.dark-mode .swal2-cancel:hover { background: rgba(110, 159, 203, 0.12) !important; border-color: #6E9FCB; color: #6E9FCB !important; }

        /* ========== ENGAGEMENT DRAWER ========== */
        .drawer-scrim {
            position: fixed; inset: 0; background: rgba(15, 23, 34, 0.44);
            opacity: 1; transition: opacity 0.25s ease; z-index: 140;
        }
        body.dark-mode .drawer-scrim { background: rgba(4, 7, 11, 0.6); }
        .drawer-scrim.hidden { opacity: 0; pointer-events: none; }

        .drawer {
            position: fixed; top: 0; right: 0; bottom: 0; width: min(560px, 100vw);
            background: var(--paper); border-left: 1px solid var(--line);
            box-shadow: -12px 0 40px rgba(20, 30, 45, 0.18);
            z-index: 141; display: flex; flex-direction: column;
            transform: translateX(0); transition: transform 0.3s cubic-bezier(0.22, 1, 0.36, 1);
        }
        body.dark-mode .drawer { box-shadow: -12px 0 40px rgba(0, 0, 0, 0.5); }
        .drawer.closed { transform: translateX(100%); }

        .drawer-header { flex-shrink: 0; background: var(--card); border-bottom: 1px solid var(--line); padding: 1.25rem 1.5rem 1.1rem; }
        .drawer-header-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.75rem; }
        .drawer-eng-id { font-size: 11.5px; font-weight: 700; letter-spacing: 0.05em; color: var(--text-muted); font-variant-numeric: tabular-nums; margin-bottom: 0.35rem; }
        .drawer-client-name { font-size: 20px; font-weight: 700; letter-spacing: -0.01em; line-height: 1.25; margin: 0; overflow-wrap: anywhere; }
        .drawer-close-btn { flex-shrink: 0; width: 34px; height: 34px; border-radius: 8px; border: 1px solid var(--line); background: var(--card); color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 16px; }
        .drawer-close-btn:hover { border-color: var(--line-strong); color: var(--text); background: var(--paper); }

        .drawer-badge-row { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.6rem; align-items: center; }
        .drawer-badge { font-size: 11px; font-weight: 700; padding: 0.3rem 0.6rem; border-radius: 6px; letter-spacing: 0.01em; }
        .drawer-badge.b-repeat { background: color-mix(in srgb, var(--ink) 12%, transparent); color: var(--ink); }

        .drawer-status-wrap { position: relative; display: inline-flex; }
        .drawer-status-badge { border: none; cursor: pointer; font-family: inherit; display: inline-flex; align-items: center; gap: 5px; }
        .drawer-status-badge:hover { filter: brightness(1.1); }
        .drawer-status-badge .bi-chevron-down { font-size: 9px; opacity: 0.75; }
        .drawer-status-popover {
            position: absolute; top: calc(100% + 6px); left: 0; background: var(--card); border: 1px solid var(--line);
            border-radius: 10px; box-shadow: 0 10px 28px rgba(0,0,0,0.18); padding: 0.35rem; width: 168px; z-index: 20;
            display: none;
        }
        .drawer-status-popover.open { display: block; }
        .drawer-status-popover-item { display: flex; align-items: center; gap: 0.55rem; padding: 0.5rem 0.6rem; border-radius: 7px; font-size: 12.5px; font-weight: 600; cursor: pointer; color: var(--text); }
        .drawer-status-popover-item:hover { background: var(--paper); }
        .drawer-status-popover-item.current { background: color-mix(in srgb, var(--ink) 8%, transparent); }
        .drawer-status-popover-item .dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .drawer-status-popover-item .check { margin-left: auto; font-size: 13px; color: var(--ink); opacity: 0; }
        .drawer-status-popover-item.current .check { opacity: 1; }

        .drawer-header-actions { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .drawer-btn { font-size: 12.5px; font-weight: 600; padding: 0.5rem 0.85rem; border-radius: 7px; border: 1px solid var(--line); background: var(--card); color: var(--text-muted); cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; }
        .drawer-btn:hover { border-color: var(--line-strong); color: var(--text); }
        .drawer-btn.drawer-btn-primary { background: var(--ink); border-color: var(--ink); color: #fff; }
        .drawer-btn.drawer-btn-primary:hover { background: color-mix(in srgb, var(--ink) 85%, black); }
        .drawer-btn.drawer-btn-danger:hover { border-color: var(--critical); color: var(--critical); }

        .drawer-body { flex: 1; overflow-y: auto; padding: 1.35rem 1.5rem 2.5rem; }

        .drawer-section { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 1.1rem 1.25rem; margin-bottom: 0.85rem; }
        .drawer-section-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.85rem; }
        .drawer-section-title { font-size: 11.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); display: flex; align-items: center; gap: 0.45rem; }
        .drawer-section-title .dot { width: 6px; height: 6px; border-radius: 50%; }
        .drawer-setup-banner { display: flex; align-items: flex-start; gap: 0.6rem; padding: 0.75rem 0.9rem; margin-bottom: 1.1rem; background: color-mix(in srgb, var(--caution) 10%, transparent); border: 1px solid color-mix(in srgb, var(--caution) 30%, transparent); border-radius: 10px; font-size: 12.5px; color: var(--text); }
        .drawer-setup-banner i { color: var(--caution); font-size: 15px; margin-top: 1px; flex-shrink: 0; }
        .drawer-setup-banner b { font-weight: 700; }
        .drawer-link-btn { font-size: 12px; font-weight: 600; color: var(--ink); background: none; border: none; cursor: pointer; padding: 0; }
        .drawer-link-btn:hover { text-decoration: underline; }
        .drawer-link-btn-danger { color: var(--critical); }
        .drawer-planning-doc-row { display: flex; gap: 0.9rem; margin-bottom: 0.9rem; }

        .doc-lightbox-scrim {
            position: fixed; inset: 0; background: rgba(10, 15, 22, 0.85); z-index: 300;
            display: none; align-items: center; justify-content: center; padding: 3rem 2rem;
        }
        .doc-lightbox-scrim.open { display: flex; }
        .doc-lightbox-img { max-width: 100%; max-height: 100%; border-radius: 8px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); }
        .doc-lightbox-close {
            position: fixed; top: 1.5rem; right: 1.5rem; width: 40px; height: 40px; border-radius: 50%;
            border: none; background: var(--card); color: var(--text); font-size: 17px; cursor: pointer;
            display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 16px rgba(0,0,0,0.3);
        }
        .doc-lightbox-close:hover { background: var(--line); }

        .drawer-info-grid, .drawer-details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem 1.25rem; }
        .drawer-info-item { min-width: 0; }
        .drawer-info-label { font-size: 10.5px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.2rem; }
        .drawer-info-value { font-size: 13.5px; font-weight: 600; color: var(--text); overflow-wrap: anywhere; }
        .drawer-info-value.muted { font-weight: 500; color: var(--text-muted); }
        .drawer-detail-scope { grid-column: 1 / -1; }
        .drawer-detail-scope .drawer-info-value { line-height: 1.5; font-weight: 500; font-size: 13px; color: var(--text-muted); white-space: pre-line; }

        /* ---------- Notes & Meetings: a running log fed by 4 buttons,
           replacing the old single eng_notes textarea + the old per-person
           independence popup. Ported from the mockup Garrett approved. ---------- */
        .mtg-actions { display: flex; flex-wrap: nowrap; gap: 0.4rem; margin-bottom: 1rem; overflow-x: auto; padding-bottom: 2px; }
        .mtg-action-btn { display: flex; align-items: center; gap: 5px; padding: 6px 9px; border-radius: 20px; border: 1px solid var(--line); background: var(--paper); color: var(--text); font-size: 11px; font-weight: 600; cursor: pointer; font-family: inherit; white-space: nowrap; flex-shrink: 0; }
        .mtg-action-btn:hover { border-color: var(--line-strong); }
        .mtg-action-btn.t-planning { color: var(--manager); }
        .mtg-action-btn.t-client { color: var(--senior); }
        .mtg-action-btn.t-weekly { color: var(--staff); }
        .mtg-action-btn.t-general { color: var(--text-muted); }

        .note-log { display: flex; flex-direction: column; }
        .note-entry { display: flex; gap: 0.75rem; padding: 0.85rem 0; border-bottom: 1px solid var(--line); }
        .note-entry:last-child { border-bottom: none; padding-bottom: 0; }
        .note-entry:first-child { padding-top: 0; }
        .note-type-rail { width: 3px; align-self: stretch; border-radius: 2px; flex-shrink: 0; }
        .note-entry.t-planning .note-type-rail { background: var(--manager); }
        .note-entry.t-client .note-type-rail { background: var(--senior); }
        .note-entry.t-weekly .note-type-rail { background: var(--staff); }
        .note-entry.t-general .note-type-rail { background: var(--line-strong); }
        .note-entry-body { flex: 1; min-width: 0; }
        .note-entry-head { display: flex; align-items: baseline; gap: 0.5rem; margin-bottom: 0.3rem; flex-wrap: wrap; }
        .note-type-label { font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; }
        .note-entry.t-planning .note-type-label { color: var(--manager); }
        .note-entry.t-client .note-type-label { color: var(--senior); }
        .note-entry.t-weekly .note-type-label { color: var(--staff); }
        .note-entry.t-general .note-type-label { color: var(--text-muted); }
        .note-meta { font-size: 11px; color: var(--text-muted); }
        .note-text { font-size: 13px; line-height: 1.55; color: var(--text); white-space: pre-line; }
        .note-indep-summary { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.55rem; }
        .note-indep-chip { display: inline-flex; align-items: center; gap: 4px; font-size: 10.5px; font-weight: 600; padding: 2px 7px; border-radius: 20px; background: var(--paper); border: 1px solid var(--line); color: var(--text-muted); }
        .note-indep-chip.yes { color: var(--good); border-color: color-mix(in srgb, var(--good) 35%, var(--line)); }
        .note-indep-chip.no { color: var(--critical); border-color: color-mix(in srgb, var(--critical) 35%, var(--line)); }
        .note-empty { text-align: center; padding: 1.4rem 1rem; color: var(--text-muted); font-size: 12.5px; }

        /* ---------- meeting-note modals: same scrim/card conventions as
           the new-engagement wizard, namespaced separately since these
           live inside the drawer rather than on the page body ---------- */
        .mtg-modal-scrim { position: fixed; inset: 0; background: rgba(10,14,20,0.5); z-index: 500; display: flex; align-items: center; justify-content: center; padding: 1.5rem; opacity: 0; pointer-events: none; transition: opacity 0.15s ease; }
        .mtg-modal-scrim.open { opacity: 1; pointer-events: auto; }
        .mtg-modal-box { background: var(--card); border: 1px solid var(--line); border-radius: 14px; width: 100%; max-width: 520px; max-height: 86vh; display: flex; flex-direction: column; box-shadow: 0 24px 64px rgba(0,0,0,0.28); transform: translateY(8px); transition: transform 0.15s ease; }
        .mtg-modal-scrim.open .mtg-modal-box { transform: translateY(0); }
        .mtg-modal-header { display: flex; align-items: flex-start; gap: 0.75rem; padding: 1.2rem 1.4rem 1rem; border-bottom: 1px solid var(--line); }
        .mtg-modal-header-icon { width: 36px; height: 36px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
        .mtg-modal-header-text h3 { font-size: 15px; font-weight: 700; margin: 0; }
        .mtg-modal-header-text p { font-size: 11.5px; color: var(--text-muted); margin: 2px 0 0; }
        .mtg-modal-close { margin-left: auto; border: none; background: transparent; color: var(--text-muted); cursor: pointer; font-size: 18px; padding: 2px; line-height: 1; flex-shrink: 0; }
        .mtg-modal-close:hover { color: var(--text); }
        .mtg-modal-body { overflow-y: auto; padding: 1.2rem 1.4rem; }
        .mtg-modal-footer { display: flex; justify-content: flex-end; gap: 0.6rem; padding: 1rem 1.4rem; border-top: 1px solid var(--line); }
        .mtg-textarea { width: 100%; min-height: 110px; padding: 0.7rem 0.8rem; border: 1px solid var(--line); border-radius: 8px; background: var(--paper); color: var(--text); font-size: 13.5px; font-family: inherit; resize: vertical; }
        .mtg-textarea:focus { outline: none; border-color: var(--ink); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 14%, transparent); }
        .mtg-field-label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px; }
        .mtg-indep-section { margin-top: 1.3rem; }
        .mtg-indep-list { display: flex; flex-direction: column; gap: 0.5rem; }
        .mtg-indep-row { display: flex; align-items: center; gap: 0.7rem; padding: 0.55rem 0.6rem; border: 1px solid var(--line); border-radius: 9px; }
        .mtg-indep-avatar { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: var(--card); flex-shrink: 0; }
        .mtg-indep-name-wrap { flex: 1; min-width: 0; }
        .mtg-indep-name { font-size: 12.5px; font-weight: 700; }
        .mtg-indep-role { font-size: 10px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
        .mtg-indep-segmented { display: flex; background: var(--paper); border-radius: 7px; padding: 2px; gap: 2px; border: 1px solid var(--line); flex-shrink: 0; }
        .mtg-indep-segmented button { border: none; background: transparent; padding: 5px 9px; border-radius: 5px; font-size: 10.5px; font-weight: 700; color: var(--text-muted); cursor: pointer; font-family: inherit; }
        .mtg-indep-segmented button.active.yes { background: color-mix(in srgb, var(--good) 16%, transparent); color: var(--good); }
        .mtg-indep-segmented button.active.no { background: color-mix(in srgb, var(--critical) 16%, transparent); color: var(--critical); }
        .mtg-indep-segmented button.active.unset { background: var(--card); color: var(--text); }
        .mtg-btn { padding: 8px 16px; border-radius: 7px; font-size: 12.5px; font-weight: 600; cursor: pointer; border: 1px solid var(--line); background: var(--card); color: var(--text); font-family: inherit; }
        .mtg-btn:hover { border-color: var(--line-strong); }
        .mtg-btn-primary { background: var(--ink); border-color: var(--ink); color: var(--card); }
        .mtg-btn-primary:hover { opacity: 0.92; }
        .mtg-btn-primary:disabled { opacity: 0.5; cursor: not-allowed; }

        .drawer-team-lead-row { display: flex; align-items: center; gap: 0.65rem; padding: 0.5rem 0.6rem; background: color-mix(in srgb, var(--manager) 7%, transparent); border-radius: 8px; margin-bottom: 0.6rem; }
        .drawer-avatar { width: 30px; height: 30px; border-radius: 8px; color: #fff; font-weight: 700; font-size: 11.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .drawer-lead-name { font-size: 13.5px; font-weight: 700; }
        .drawer-lead-role { font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--manager); }
        .drawer-hours-badge { font-size: 10px; font-weight: 700; color: var(--text-muted); background: var(--paper); border: 1px solid var(--line); padding: 2px 7px; border-radius: 20px; white-space: nowrap; }

        .drawer-role-group-label { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); margin: 0.5rem 0 0.35rem; }
        .drawer-member-row { display: flex; align-items: flex-start; gap: 0.6rem; padding: 0.4rem 0.2rem; border-top: 1px solid var(--line); }
        .drawer-role-group:first-child .drawer-member-row:first-child { border-top: none; }
        .drawer-member-row .drawer-avatar { width: 26px; height: 26px; font-size: 10.5px; }
        .drawer-member-info { flex: 1; min-width: 0; }
        .drawer-independence-badge { margin-left: auto; flex-shrink: 0; display: flex; font-size: 17px; line-height: 1; color: var(--line-strong); }
        .drawer-independence-badge.yes { color: var(--good); }
        .drawer-independence-badge.no { color: var(--critical); }
        .drawer-member-name { font-size: 13px; font-weight: 600; }
        .drawer-dol-lines { margin-top: 0.25rem; }
        .drawer-dol-line { display: flex; align-items: baseline; gap: 0.4rem; font-size: 11.5px; margin-bottom: 0.15rem; flex-wrap: wrap; }
        .drawer-dol-line .drawer-dol-audit-label { font-weight: 700; color: var(--text-muted); width: 46px; flex-shrink: 0; }
        .drawer-dol-chip { display: inline-flex; background: color-mix(in srgb, var(--ink) 10%, transparent); color: var(--ink); font-weight: 700; padding: 0.1rem 0.4rem; border-radius: 4px; font-size: 10.5px; }
        .drawer-dol-chip.t-soc2 { background: color-mix(in srgb, var(--senior) 12%, transparent); color: var(--senior); }
        .drawer-dol-chips-wrap { display: flex; flex-wrap: wrap; gap: 0.3rem; }
        .drawer-no-dol { font-size: 11.5px; color: var(--text-muted); font-style: italic; }
        .drawer-team-empty { padding: 1rem; text-align: center; color: var(--text-muted); font-size: 12.5px; font-style: italic; }

        .drawer-timeline-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem 1rem; }
        .drawer-tl-item { display: flex; align-items: center; gap: 0.55rem; padding: 0.4rem 0; cursor: pointer; border-radius: 6px; }
        .drawer-tl-item:hover { background: var(--paper); }
        .drawer-tl-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; background: var(--line-strong); }
        .drawer-tl-dot.done { background: var(--good); }
        .drawer-tl-dot.overdue { background: var(--critical); }
        .drawer-tl-info { min-width: 0; }
        .drawer-tl-label { font-size: 10.5px; font-weight: 700; letter-spacing: 0.03em; text-transform: uppercase; color: var(--text-muted); }
        .drawer-tl-date { font-size: 12.5px; font-weight: 600; color: var(--text); font-variant-numeric: tabular-nums; }
        .drawer-tl-date.overdue { color: var(--critical); }
        .drawer-tl-date.empty { color: var(--text-muted); font-weight: 500; }
        .drawer-tl-hint { font-size: 10.5px; color: var(--text-muted); margin-top: 0.75rem; }
        .drawer-weekly-call-row { display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; margin-top: 0.85rem; padding-top: 0.85rem; border-top: 1px solid var(--line); }
        .drawer-weekly-call-row label { font-size: 11.5px; font-weight: 600; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .drawer-weekly-call-row select { font-size: 12.5px; font-weight: 600; color: var(--text); background: var(--paper); border: 1px solid var(--line); border-radius: 6px; padding: 5px 8px; cursor: pointer; }
        .drawer-weekly-call-row select:focus { outline: none; border-color: var(--ink); }
        .wcall-linked-row { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 0.6rem; position: relative; }
        .wcall-linked-label { font-size: 11px; color: var(--text-muted); }
        .wcall-name-row { width: 100%; font-size: 12px; font-weight: 600; color: var(--text); font-style: italic; display: flex; align-items: center; gap: 6px; }
        .wcall-rename-btn { border: none; background: transparent; color: var(--text-muted); cursor: pointer; font-size: 11px; padding: 2px; display: inline-flex; }
        .wcall-rename-btn:hover { color: var(--ink); }
        .wcall-chip { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 600; color: var(--ink); background: color-mix(in srgb, var(--ink) 12%, transparent); padding: 3px 6px 3px 9px; border-radius: 12px; }
        .wcall-unlink { border: none; background: transparent; color: var(--ink); cursor: pointer; font-size: 13px; padding: 0 2px; line-height: 1; opacity: 0.7; }
        .wcall-unlink:hover { opacity: 1; }
        .wcall-link-btn { border: none; background: transparent; color: var(--ink); font-size: 11.5px; font-weight: 600; cursor: pointer; padding: 3px 0; }
        .wcall-link-btn:hover { text-decoration: underline; }
        .wcall-link-hint { font-size: 10.5px; color: var(--text-muted); font-style: italic; }
        .wcall-ac-wrap { position: absolute; top: calc(100% + 4px); left: 0; right: 0; z-index: 20; }
        .wcall-ac-input { width: 100%; font-size: 12.5px; color: var(--text); background: var(--card); border: 1px solid var(--line); border-radius: 6px; padding: 6px 8px; }
        .wcall-ac-input:focus { outline: none; border-color: var(--ink); }
        .wcall-ac-list { margin-top: 3px; background: var(--card); border: 1px solid var(--line); border-radius: 8px; box-shadow: 0 8px 22px rgba(0,0,0,0.16); max-height: 200px; overflow-y: auto; }
        .wcall-ac-item { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 10px; cursor: pointer; font-size: 12.5px; color: var(--text); }
        .wcall-ac-item:hover { background: var(--paper); }
        .wcall-ac-id { font-size: 10.5px; color: var(--text-muted); }
        .wcall-ac-empty { padding: 9px 10px; font-size: 12px; color: var(--text-muted); }

        .drawer-loading { padding: 3rem 1rem; text-align: center; color: var(--text-muted); font-size: 13px; }

        @media (max-width: 640px) {
            .drawer { width: 100vw; }
            .drawer-info-grid, .drawer-details-grid, .drawer-timeline-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<!-- ========== HEADER ========== -->
<div class="top-header">
    <div class="header-inner">
        <a href="dashboard.php" class="brand" title="Engagement Tracker">
            <span class="brand-icon">ET</span>
            <span class="brand-mark">Engagement Tracker</span>
        </a>
        <nav class="main-nav">
            <a class="active" href="dashboard.php">Engagements</a>
            <a href="tools.php">Tools</a>
        </nav>
        <div class="header-right">
            <button class="icon-btn" title="Dark mode">
                <i class="bi bi-moon"></i>
            </button>
            <button class="icon-btn" title="What's Due" onclick="openDuePopup()">
                <i class="bi bi-list-check"></i>
            </button>

            <div class="profile-section">
                <div class="profile-wrapper" id="profileToggle">
                    <button class="profile-btn" title="Profile"><?php echo $initials; ?></button>
                    <button class="profile-dropdown-toggle"><i class="bi bi-chevron-down"></i></button>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                        <div class="profile-dropdown-avatar"><?php echo $initials; ?></div>
                        <div>
                            <div class="profile-dropdown-name"><?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></div>
                            <div class="profile-dropdown-email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                        </div>
                    </div>
                    <div class="profile-dropdown-menu">
                        <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'], true)): ?>
                        <a href="settings.php" class="profile-dropdown-item">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL . '/auth/logout.php'; ?>" class="profile-dropdown-item logout">
                            <i class="bi bi-box-arrow-right"></i> Log Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========== MAIN CONTENT ========== -->
<div class="main-container">

    <div class="page-head">
        <div>
            <h1>Engagements</h1>
            <p class="page-sub"><?php echo $activeCount; ?> active &middot; <?php echo $archivedCount; ?> archived</p>
        </div>
        <div class="head-actions">
            <div class="tab-toggle">
                <a class="<?php echo !$showArchived ? 'active' : ''; ?>" href="dashboard.php">Active <span class="count">(<?php echo $activeCount; ?>)</span></a>
                <a class="<?php echo $showArchived ? 'active' : ''; ?>" href="dashboard.php?view=archived">Archived <span class="count">(<?php echo $archivedCount; ?>)</span></a>
            </div>
            <?php if (!$showArchived): ?>
            <button class="btn-new-engagement">
                <i class="bi bi-plus"></i> New Engagement
            </button>
            <?php endif; ?>
        </div>
    </div>

    <hr class="rule">

    <div class="stat-row">
        <?php if (!$showArchived): ?>
            <div class="stat-card"><div><div class="value"><?php echo $activeCount; ?></div><div class="label">Total Active</div></div></div>
            <div class="stat-card <?php echo $attentionCount ? 'stat-card--setup' : ''; ?>"><div><div class="value"><?php echo $attentionCount; ?></div><div class="label">Needs Attention</div></div></div>
            <div class="stat-card <?php echo $dueSoonCount ? 'stat-card--attention' : ''; ?>"><div><div class="value"><?php echo $dueSoonCount; ?></div><div class="label">Due Soon</div></div></div>
            <?php if (!empty($statusCounts)): ?>
            <div class="dist-card">
                <div class="dist-bar">
                    <?php foreach ($statusCounts as $status => $count): ?>
                        <div class="dist-seg" style="width:<?php echo ($count / $activeCount) * 100; ?>%; background:var(<?php echo $statusMeta[$status]['var']; ?>);"></div>
                    <?php endforeach; ?>
                </div>
                <div class="dist-legend">
                    <?php foreach ($statusCounts as $status => $count): ?>
                        <div class="dist-legend-item">
                            <span class="dot" style="background:var(<?php echo $statusMeta[$status]['var']; ?>)"></span>
                            <b><?php echo $count; ?></b>&nbsp;<?php echo $statusMeta[$status]['label']; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="stat-card"><div><div class="value"><?php echo $archivedCount; ?></div><div class="label">Total Archived</div></div></div>
        <?php endif; ?>
    </div>

    <div class="toolbar">
        <div class="search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" class="search-input" id="searchInput" placeholder="Search engagements&hellip;">
        </div>
        <?php if (!$showArchived): ?>
        <div class="toolbar-flags">
            <button class="attention-link attention-link--setup" id="attentionLink">
                <span class="swatch"></span>
                <span><?php echo $attentionCount; ?> need attention</span>
            </button>
            <button class="attention-link" id="dueSoonLink">
                <span class="swatch"></span>
                <span><?php echo $dueSoonCount; ?> due soon</span>
            </button>
        </div>
        <?php endif; ?>
        <div class="result-note" id="resultNote"></div>
    </div>

    <?php if (empty($engagements)): ?>
        <div class="empty-state" id="emptyState"><?php echo $showArchived ? 'No archived engagements yet.' : 'No engagements found. Create one to get started.'; ?></div>
    <?php else: ?>
        <div class="list-head">
            <span class="lh-tick"></span>
            <span class="lh-id">ID</span>
            <span class="lh-main">Client</span>
            <span class="lh-type">Type</span>
            <span class="lh-due"><?php echo $showArchived ? 'Archived' : 'Due'; ?></span>
            <span class="lh-actions"></span>
        </div>

        <?php
        $renderRow = function ($eng) use ($timelineLookup, $teamLookup, $showArchived, $statusMeta) {
            [$due, $dueState] = getDueInfo($eng['eng_idno'], $timelineLookup);
            $critical = !$showArchived && $dueState === 'overdue';
            $tickVar = $showArchived ? '--text-muted' : ($statusMeta[$eng['eng_status']]['var'] ?? '--text-muted');
            $dueSoon = in_array($dueState, ['overdue', 'soon', 'archive_ready'], true) ? '1' : '0';
            $setup = $showArchived
                ? ['has_team' => true, 'dol_complete' => true, 'has_timeline' => true, 'has_planning_doc' => true, 'missing' => [], 'needs_setup' => false]
                : getSetupInfo($eng, $teamLookup, $timelineLookup);
            $setupFlagHtml = '';
            if ($setup['needs_setup']) {
                // First/most-actionable gap only — the row is a scannable
                // list, not the place to enumerate everything missing (the
                // drawer banner does that once you open the engagement).
                $setupReason = $setup['missing'][0];
                $setupFlagHtml = '<span class="reg-setup-flag"><i class="bi bi-exclamation-triangle-fill"></i> ' . htmlspecialchars($setupReason) . '</span>';
            }
            $searchBlob = strtolower($eng['eng_name'] . ' ' . ($eng['eng_manager'] ?? '') . ' ' . $eng['eng_idno'] . ' ' . ($eng['eng_poc'] ?? '') . ' ' . ($eng['eng_audit_type'] ?? ''));

            $dueHtml = '<div class="reg-due">&mdash;</div>';
            if (!$showArchived && $dueState === 'archive_ready') {
                $dueHtml = '<div class="reg-due archive-ready"><i class="bi bi-archive"></i> Archive Ready</div>';
            } elseif ($due) {
                $fmt = $due->format('M j, Y');
                if ($showArchived) {
                    $dueHtml = '<div class="reg-due">' . $fmt . '</div>';
                } elseif ($dueState === 'overdue') {
                    $daysLate = (new DateTime('today'))->diff($due)->days;
                    $dueHtml = '<div class="reg-due overdue">' . $fmt . '<span class="tag">' . $daysLate . 'd late</span></div>';
                } elseif ($dueState === 'soon') {
                    $dueHtml = '<div class="reg-due soon">' . $fmt . '<span class="tag">Due soon</span></div>';
                } else {
                    $dueHtml = '<div class="reg-due">' . $fmt . '</div>';
                }
            }

            $actions = $showArchived
                ? '<button data-action="restore" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                   <button class="danger" data-action="delete" title="Delete"><i class="bi bi-trash"></i></button>'
                : '<button data-action="archive" title="Archive"><i class="bi bi-archive"></i></button>
                   <button class="danger" data-action="delete" title="Delete"><i class="bi bi-trash"></i></button>';

            $detailPage = $showArchived ? 'archived-engagement-details.php' : 'engagement-details.php';

            echo '<div class="reg-row ' . ($critical ? 'is-critical' : '') . ' ' . ($setup['needs_setup'] ? 'is-needs-setup' : '') . ' ' . ($showArchived ? 'is-archived' : '') . '"'
                . ' data-id="' . htmlspecialchars($eng['eng_idno']) . '"'
                . ' data-detail-href="' . $detailPage . '?id=' . urlencode($eng['eng_idno']) . '"'
                . ' data-search="' . htmlspecialchars($searchBlob) . '"'
                . ' data-due-soon="' . $dueSoon . '"'
                . ' data-needs-setup="' . ($setup['needs_setup'] ? '1' : '0') . '">'
                . '<div class="reg-tick" style="background:var(' . $tickVar . ')"></div>'
                . '<div class="reg-id mono">' . htmlspecialchars($eng['eng_idno']) . '</div>'
                . '<div class="reg-main">'
                . '<div class="reg-name">' . htmlspecialchars($eng['eng_name']) . '</div>'
                . '<div class="reg-sub-row"><div class="reg-sub">' . htmlspecialchars($eng['eng_manager'] ?? 'Unassigned') . '</div>' . $setupFlagHtml . '</div>'
                . '</div>'
                . '<div class="reg-type">' . renderTypeBadges($eng['eng_audit_type']) . '</div>'
                . $dueHtml
                . '<div class="row-actions">' . $actions . '</div>'
                . '</div>';
        };
        ?>

        <?php if (!$showArchived): ?>
            <?php foreach ($sectionOrder as $status): ?>
                <?php
                    $items = array_filter($engagements, fn($e) => $e['eng_status'] === $status);
                    usort($items, function ($a, $b) use ($timelineLookup) {
                        [$dueA, $stateA] = getDueInfo($a['eng_idno'], $timelineLookup);
                        [$dueB, $stateB] = getDueInfo($b['eng_idno'], $timelineLookup);
                        // Ready-to-archive engagements float to the top of their
                        // section — there's nothing left to do but archive them,
                        // so they're the most actionable thing in the list.
                        $readyA = $stateA === 'archive_ready';
                        $readyB = $stateB === 'archive_ready';
                        if ($readyA !== $readyB) return $readyA ? -1 : 1;
                        if (!$dueA && !$dueB) return 0;
                        if (!$dueA) return 1;
                        if (!$dueB) return -1;
                        return $dueA <=> $dueB;
                    });
                    if (empty($items)) continue;
                ?>
                <div class="section-label">
                    <span class="swatch" style="background:var(<?php echo $statusMeta[$status]['var']; ?>)"></span>
                    <?php echo $statusMeta[$status]['label']; ?>
                    <span class="n">(<?php echo count($items); ?>)</span>
                </div>
                <div class="register">
                    <?php foreach ($items as $eng) $renderRow($eng); ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="register">
                <?php foreach ($engagements as $eng) $renderRow($eng); ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<!-- ========== WHAT'S DUE POPUP ========== -->
<div class="due-popup-scrim" id="duePopupScrim">
    <div class="due-popup">
        <div class="due-popup-header">
            <div>
                <h3>What's Due</h3>
                <div class="due-popup-sub" id="duePopupSub"></div>
            </div>
            <button class="due-popup-close" onclick="closeDuePopup()">&times;</button>
        </div>
        <div class="due-popup-body" id="duePopupBody">
            <div class="due-popup-empty">Loading&hellip;</div>
        </div>
    </div>
</div>

<!-- ========== NEW ENGAGEMENT WIZARD ========== -->
<div class="eng-wizard-scrim" id="engWizardScrim">
    <div class="eng-wizard">
        <div class="eng-wizard-header">
            <div class="eng-wizard-header-top">
                <h3>Create New Engagement</h3>
                <button class="eng-wizard-close" id="engWizardClose" aria-label="Close">&times;</button>
            </div>
            <div class="eng-wizard-steps">
                <button type="button" class="eng-wizard-step active" data-step="1">
                    <div class="eng-wizard-step-bar"></div>
                    <div class="eng-wizard-step-label">1. Basics</div>
                </button>
                <button type="button" class="eng-wizard-step" data-step="2">
                    <div class="eng-wizard-step-bar"></div>
                    <div class="eng-wizard-step-label">2. Team</div>
                </button>
                <button type="button" class="eng-wizard-step" data-step="3">
                    <div class="eng-wizard-step-bar"></div>
                    <div class="eng-wizard-step-label">3. Audit Scope</div>
                </button>
                <button type="button" class="eng-wizard-step" data-step="4">
                    <div class="eng-wizard-step-bar"></div>
                    <div class="eng-wizard-step-label">4. Review</div>
                </button>
            </div>
        </div>

        <div class="eng-wizard-body">
            <!-- Step 1: Basics -->
            <div class="eng-wizard-panel active" data-panel="1">
                <div class="eng-field">
                    <label>Engagement Name <span class="req">*</span></label>
                    <input type="text" id="new_eng_name" class="eng-input" placeholder="Enter engagement name">
                </div>
                <div class="eng-field">
                    <label>Location</label>
                    <input type="text" id="new_eng_location" class="eng-input" placeholder="Enter location">
                </div>
                <div class="eng-field">
                    <label>Point of Contact</label>
                    <input type="text" id="new_eng_poc" class="eng-input" placeholder="Enter point of contact">
                </div>
                <div class="eng-field">
                    <label>Manager</label>
                    <div class="team2-ac-wrap">
                        <input type="text" id="new_eng_manager_search" class="eng-input" placeholder="Search employees&hellip;" autocomplete="off">
                        <div class="team2-ac-list" id="new_eng_manager_ac_list" style="display:none;"></div>
                    </div>
                    <div class="eng-manager-selected" id="new_eng_manager_selected" style="display:none;"></div>
                </div>
                <div class="eng-field" style="margin-bottom: 0;">
                    <label>Status</label>
                    <div class="eng-segmented" id="new_eng_status_segment">
                        <button type="button" class="active" data-value="planning">Planning</button>
                        <button type="button" data-value="in-progress">In Progress</button>
                        <button type="button" data-value="in-review">In Review</button>
                        <button type="button" data-value="complete">Complete</button>
                    </div>
                </div>
            </div>

            <!-- Step 2: Team -->
            <div class="eng-wizard-panel" data-panel="2">
                <div class="eng-field">
                    <label>Add Team Members</label>
                    <div class="team2-ac-wrap">
                        <input type="text" id="new_eng_team_search" class="eng-input" placeholder="Search employees&hellip;" autocomplete="off">
                        <div class="team2-ac-list" id="new_eng_team_ac_list" style="display:none;"></div>
                    </div>
                    <div class="eng-field-hint">Manager is set in Step 1 &mdash; add Senior/Staff/Intern here, with budgeted hours for each. Optional; you can always add people later from the Team card.</div>
                </div>
                <div id="new_eng_team_list"></div>
            </div>

            <!-- Step 3: Audit Scope -->
            <div class="eng-wizard-panel" data-panel="3">
                <div class="eng-field">
                    <label>Audit Types (select all that apply)</label>
                    <div class="eng-chip-grid">
                        <label class="eng-chip"><input type="checkbox" class="new-audit-type-checkbox" value="SOC 1"> SOC 1</label>
                        <label class="eng-chip"><input type="checkbox" class="new-audit-type-checkbox" value="SOC 2"> SOC 2</label>
                        <label class="eng-chip"><input type="checkbox" class="new-audit-type-checkbox" value="PCI"> PCI</label>
                        <label class="eng-chip"><input type="checkbox" class="new-audit-type-checkbox" value="HITRUST"> HITRUST</label>
                        <label class="eng-chip"><input type="checkbox" class="new-audit-type-checkbox" value="FISMA"> FISMA</label>
                        <label class="eng-chip"><input type="checkbox" class="new-audit-type-checkbox" value="ISO"> ISO</label>
                        <label class="eng-chip"><input type="checkbox" class="new-audit-type-checkbox" value="HIPAA"> HIPAA</label>
                    </div>
                </div>
                <div id="new_soc_type_section" class="eng-soc-box" style="display: none;">
                    <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">SOC Type</label>
                    <div class="eng-segmented" id="new_soc_type_segment" style="margin-bottom: 0.9rem;">
                        <button type="button" data-value="Type 1">Type 1</button>
                        <button type="button" data-value="Type 2">Type 2</button>
                    </div>
                    <div id="new_soc_type1_dates" style="display: none;">
                        <div class="eng-field" style="margin-bottom: 0;">
                            <label>As Of Date</label>
                            <input type="date" id="new_soc_as_of_date" class="eng-date">
                        </div>
                    </div>
                    <div id="new_soc_type2_dates" style="display: none; gap: 0.9rem;">
                        <div class="eng-field" style="flex: 1; margin-bottom: 0;">
                            <label>Start Period</label>
                            <input type="date" id="new_soc_start_period" class="eng-date">
                        </div>
                        <div class="eng-field" style="flex: 1; margin-bottom: 0;">
                            <label>End Period</label>
                            <input type="date" id="new_soc_end_period" class="eng-date">
                        </div>
                    </div>
                </div>
                <div class="eng-field" style="margin-top: 1.15rem;">
                    <label>Trusted Service Criteria</label>
                    <input type="text" id="new_eng_tsc" class="eng-input" placeholder="Enter TSC">
                </div>
                <div class="eng-field" style="margin-bottom: 0;">
                    <label>Scope</label>
                    <textarea id="new_eng_scope" class="eng-textarea" placeholder="Enter scope"></textarea>
                </div>
            </div>

            <!-- Step 4: Review & Notes -->
            <div class="eng-wizard-panel" data-panel="4">
                <dl class="eng-review-summary" id="eng_review_summary"></dl>
                <div class="eng-field">
                    <div class="eng-switch-row">
                        <div>
                            <div class="eng-switch-label">Repeat Engagement</div>
                            <div class="eng-switch-hint">Recurs on a regular cadence</div>
                        </div>
                        <label class="eng-switch">
                            <input type="checkbox" id="new_eng_repeat">
                            <span class="eng-switch-track"></span>
                            <span class="eng-switch-thumb"></span>
                        </label>
                    </div>
                </div>
                <div class="eng-field" style="margin-bottom: 0;">
                    <label>Notes</label>
                    <textarea id="new_eng_notes" class="eng-textarea" placeholder="Enter notes" style="min-height: 100px;"></textarea>
                </div>
            </div>
        </div>

        <div class="eng-wizard-footer">
            <button type="button" class="eng-btn" id="engWizardBack" style="visibility: hidden;">Back</button>
            <div class="eng-wizard-footer-right">
                <button type="button" class="eng-btn" id="engWizardCancel">Cancel</button>
                <button type="button" class="eng-btn eng-btn-primary" id="engWizardNext">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- ========== ENGAGEMENT DRAWER ========== -->
<div class="drawer-scrim hidden" id="drawerScrim"></div>
<div class="drawer closed" id="drawer">
    <div class="drawer-header">
        <div class="drawer-header-top">
            <div style="min-width:0;">
                <div class="drawer-eng-id" id="drawerEngId"></div>
                <h2 class="drawer-client-name" id="drawerClientName"></h2>
                <div class="drawer-badge-row" id="drawerBadgeRow"></div>
            </div>
            <button class="drawer-close-btn" id="drawerCloseBtn" title="Close"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="drawer-header-actions">
            <button class="drawer-btn drawer-btn-primary" id="drawerEditBtn"><i class="bi bi-pencil"></i> Edit</button>
            <button class="drawer-btn" id="drawerArchiveBtn"><i class="bi bi-archive"></i> Archive</button>
            <button class="drawer-btn drawer-btn-danger" id="drawerDeleteBtn"><i class="bi bi-trash"></i> Delete</button>
        </div>
    </div>
    <div class="drawer-body" id="drawerBody">
        <div class="drawer-loading">Loading&hellip;</div>
    </div>
    <input type="file" id="timelineImportFileInput" accept=".xlsx,.xls,.csv" style="display:none;">
    <input type="file" id="planningDocFileInput" accept="image/png,image/jpeg,image/gif,image/webp" style="display:none;">
</div>

<!-- ========== NOTES & MEETINGS MODALS ========== -->
<div class="mtg-modal-scrim" id="mtgModalPlanning">
    <div class="mtg-modal-box">
        <div class="mtg-modal-header">
            <div class="mtg-modal-header-icon" style="background:color-mix(in srgb, var(--manager) 14%, transparent); color:var(--manager);"><i class="bi bi-clipboard2-check"></i></div>
            <div class="mtg-modal-header-text">
                <h3>Planning Meeting</h3>
                <p>Notes, plus independence for the whole team in one pass. Marks Internal Planning Call complete on the timeline.</p>
            </div>
            <button class="mtg-modal-close" data-mtg-close type="button"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mtg-modal-body">
            <label class="mtg-field-label">What was discussed?</label>
            <textarea class="mtg-textarea" id="mtgPlanningText" placeholder="Scope, timing, open questions&hellip;"></textarea>
            <div class="mtg-indep-section">
                <label class="mtg-field-label" id="mtgIndepLabel">Independence</label>
                <div class="mtg-indep-list" id="mtgIndepList"></div>
            </div>
        </div>
        <div class="mtg-modal-footer">
            <button class="mtg-btn" data-mtg-close type="button">Cancel</button>
            <button class="mtg-btn mtg-btn-primary" id="mtgSavePlanning" type="button">Save Planning Meeting</button>
        </div>
    </div>
</div>

<div class="mtg-modal-scrim" id="mtgModalClient">
    <div class="mtg-modal-box">
        <div class="mtg-modal-header">
            <div class="mtg-modal-header-icon" style="background:color-mix(in srgb, var(--senior) 14%, transparent); color:var(--senior);"><i class="bi bi-camera-video"></i></div>
            <div class="mtg-modal-header-text">
                <h3>Client Planning Meeting</h3>
                <p>Notes from the call with the client. Marks Client Planning Call complete on the timeline.</p>
            </div>
            <button class="mtg-modal-close" data-mtg-close type="button"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mtg-modal-body">
            <label class="mtg-field-label">Notes</label>
            <textarea class="mtg-textarea" id="mtgClientText" placeholder="What the client said, decisions made&hellip;"></textarea>
        </div>
        <div class="mtg-modal-footer">
            <button class="mtg-btn" data-mtg-close type="button">Cancel</button>
            <button class="mtg-btn mtg-btn-primary" id="mtgSaveClient" type="button">Save Notes</button>
        </div>
    </div>
</div>

<div class="mtg-modal-scrim" id="mtgModalWeekly">
    <div class="mtg-modal-box">
        <div class="mtg-modal-header">
            <div class="mtg-modal-header-icon" style="background:color-mix(in srgb, var(--staff) 14%, transparent); color:var(--staff);"><i class="bi bi-arrow-repeat"></i></div>
            <div class="mtg-modal-header-text">
                <h3>Weekly Status Call</h3>
                <p>Notes from this week's check-in.</p>
            </div>
            <button class="mtg-modal-close" data-mtg-close type="button"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mtg-modal-body">
            <label class="mtg-field-label">Notes</label>
            <textarea class="mtg-textarea" id="mtgWeeklyText" placeholder="Progress, blockers, next steps&hellip;"></textarea>
        </div>
        <div class="mtg-modal-footer">
            <button class="mtg-btn" data-mtg-close type="button">Cancel</button>
            <button class="mtg-btn mtg-btn-primary" id="mtgSaveWeekly" type="button">Save Notes</button>
        </div>
    </div>
</div>

<div class="mtg-modal-scrim" id="mtgModalGeneral">
    <div class="mtg-modal-box">
        <div class="mtg-modal-header">
            <div class="mtg-modal-header-icon" style="background:color-mix(in srgb, var(--text-muted) 14%, transparent); color:var(--text-muted);"><i class="bi bi-pencil"></i></div>
            <div class="mtg-modal-header-text">
                <h3>Add a Note</h3>
                <p>Anything that doesn't fit the other three.</p>
            </div>
            <button class="mtg-modal-close" data-mtg-close type="button"><i class="bi bi-x-lg"></i></button>
        </div>
        <div class="mtg-modal-body">
            <label class="mtg-field-label">Notes</label>
            <textarea class="mtg-textarea" id="mtgGeneralText" placeholder="Enter notes&hellip;"></textarea>
        </div>
        <div class="mtg-modal-footer">
            <button class="mtg-btn" data-mtg-close type="button">Cancel</button>
            <button class="mtg-btn mtg-btn-primary" id="mtgSaveGeneral" type="button">Save Note</button>
        </div>
    </div>
</div>

<div class="doc-lightbox-scrim" id="docLightboxScrim">
    <button class="doc-lightbox-close" id="docLightboxClose" title="Close"><i class="bi bi-x-lg"></i></button>
    <img class="doc-lightbox-img" id="docLightboxImg" src="" alt="Planning document">
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
    const BASE_URL = "<?= BASE_URL ?>";

    // Toast flags set before a reload
    if (sessionStorage.getItem('showEngagementCreatedToast')) {
        sessionStorage.removeItem('showEngagementCreatedToast');
        showToast('Engagement created successfully');
    }
    if (sessionStorage.getItem('showDeletedToast')) {
        sessionStorage.removeItem('showDeletedToast');
        showToast('Engagement deleted successfully');
    }
    if (sessionStorage.getItem('showArchivedToast')) {
        sessionStorage.removeItem('showArchivedToast');
        showToast('Engagement archived successfully');
    }
    if (sessionStorage.getItem('showRestoredToast')) {
        sessionStorage.removeItem('showRestoredToast');
        showToast('Engagement restored successfully');
    }
    if (sessionStorage.getItem('showTimelineToast')) {
        sessionStorage.removeItem('showTimelineToast');
        showToast('Timeline updated successfully');
    }
    if (sessionStorage.getItem('showEngagementUpdatedToast')) {
        sessionStorage.removeItem('showEngagementUpdatedToast');
        showToast('Engagement updated successfully');
    }

    function swalColors() {
        const isDarkMode = document.body.classList.contains('dark-mode');
        return {
            background: isDarkMode ? '#171F28' : '#FFFFFF',
            color: isDarkMode ? '#E7ECF1' : '#16202B'
        };
    }

    async function archiveEngagement(engagementId) {
        const colors = swalColors();
        const result = await Swal.fire({
            title: 'Archive Engagement?',
            text: 'Are you sure you want to archive this engagement? It will be moved to the archive and hidden from the main list.',
            icon: 'warning',
            confirmButtonText: 'Archive',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            confirmButtonColor: 'var(--ink)',
            background: colors.background,
            color: colors.color
        });
        if (!result.isConfirmed) return;
        try {
            const response = await fetch('../api/archive-engagement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ engagement_id: engagementId })
            });
            const data = await response.json();
            if (data.success) {
                sessionStorage.setItem('showArchivedToast', 'true');
                location.reload();
            } else {
                Swal.fire('Error', data.message || 'Failed to archive engagement', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to archive engagement', 'error');
        }
    }

    async function deleteEngagement(engagementId) {
        const colors = swalColors();
        const result = await Swal.fire({
            title: 'Delete Engagement?',
            text: 'This action cannot be undone. The engagement and all related data will be permanently deleted.',
            icon: 'warning',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            confirmButtonColor: 'var(--critical)',
            background: colors.background,
            color: colors.color
        });
        if (!result.isConfirmed) return;
        try {
            const response = await fetch('../api/delete-engagement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ engagement_id: engagementId })
            });
            const data = await response.json();
            if (data.success) {
                sessionStorage.setItem('showDeletedToast', 'true');
                location.reload();
            } else {
                Swal.fire('Error', data.message || 'Failed to delete engagement', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to delete engagement', 'error');
        }
    }

    async function restoreEngagement(engagementId) {
        const colors = swalColors();
        const result = await Swal.fire({
            title: 'Restore Engagement?',
            text: 'Are you sure you want to restore this engagement? It will be moved back to complete status.',
            icon: 'question',
            confirmButtonText: 'Restore',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            confirmButtonColor: 'var(--ink)',
            background: colors.background,
            color: colors.color
        });
        if (!result.isConfirmed) return;
        try {
            const response = await fetch('../api/restore-engagement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ engagement_id: engagementId })
            });
            const data = await response.json();
            if (data.success) {
                sessionStorage.setItem('showRestoredToast', 'true');
                location.reload();
            } else {
                Swal.fire('Error', data.message || 'Failed to restore engagement', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to restore engagement', 'error');
        }
    }

    // ---------- "What's Due" popup ----------
    function duePopupWhenLabel(item) {
        if (item.days_until < 0) {
            const n = Math.abs(item.days_until);
            return { cls: 'overdue', text: n + ' day' + (n === 1 ? '' : 's') + ' overdue' };
        }
        if (item.days_until === 0) return { cls: 'upcoming', text: 'Due today' };
        return { cls: 'upcoming', text: 'Due in ' + item.days_until + ' day' + (item.days_until === 1 ? '' : 's') };
    }

    function duePopupItemRow(item) {
        const when = duePopupWhenLabel(item);
        let dateLabel = fmtDate(item.due_date) || '';
        if (item.start_date) {
            const startLabel = fmtDate(item.start_date);
            if (startLabel) dateLabel = `${startLabel} - ${dateLabel}`;
        }
        return `
            <div class="due-popup-item" onclick="closeDuePopup(); openDrawer('${escAttr(item.engagement_idno)}');">
                <span class="due-popup-dot ${when.cls === 'overdue' ? 'overdue' : ''}"></span>
                <div class="due-popup-info">
                    <div class="due-popup-name">${escapeHtml(item.eng_name)}</div>
                    <div class="due-popup-item-title">${escapeHtml(item.title)} &middot; ${escapeHtml(dateLabel)}</div>
                </div>
                <div class="due-popup-when ${when.cls}">${when.text}</div>
            </div>
        `;
    }

    async function renderDuePopup() {
        const bodyEl = document.getElementById('duePopupBody');
        const subEl = document.getElementById('duePopupSub');
        bodyEl.innerHTML = '<div class="due-popup-empty">Loading&hellip;</div>';
        try {
            const res = await fetch('../api/get-due-items.php');
            const data = await res.json();
            if (!data.success) {
                bodyEl.innerHTML = '<div class="due-popup-empty">Couldn\'t load due items.</div>';
                return;
            }
            const { overdue, upcoming, days_ahead } = data;
            subEl.textContent = `Overdue items, plus anything due in the next ${days_ahead} day${days_ahead === 1 ? '' : 's'}`;

            if (!overdue.length && !upcoming.length) {
                bodyEl.innerHTML = '<div class="due-popup-empty">Nothing overdue or coming up &mdash; you\'re all caught up.</div>';
                return;
            }

            let html = '';
            if (overdue.length) {
                html += `<div class="due-popup-section-label">Overdue (${overdue.length})</div>` + overdue.map(duePopupItemRow).join('');
            }
            if (upcoming.length) {
                html += `<div class="due-popup-section-label">Upcoming (${upcoming.length})</div>` + upcoming.map(duePopupItemRow).join('');
            }
            bodyEl.innerHTML = html;
        } catch (error) {
            console.error('Error:', error);
            bodyEl.innerHTML = '<div class="due-popup-empty">Couldn\'t load due items.</div>';
        }
    }

    function openDuePopup() {
        document.getElementById('duePopupScrim').classList.add('open');
        renderDuePopup();
    }
    function closeDuePopup() {
        document.getElementById('duePopupScrim').classList.remove('open');
    }
    document.getElementById('duePopupScrim').addEventListener('click', (ev) => {
        if (ev.target.id === 'duePopupScrim') closeDuePopup();
    });

    <?php if ($showDuePopupOnLoad): ?>
    openDuePopup();
    <?php endif; ?>

    // Row click -> quick-view drawer (active list) or full page (archived list, not yet converted)
    const IS_ARCHIVED_VIEW = <?php echo $showArchived ? 'true' : 'false'; ?>;
    document.querySelectorAll('.reg-row').forEach(row => {
        row.addEventListener('click', () => {
            if (IS_ARCHIVED_VIEW) {
                window.location.href = row.dataset.detailHref;
            } else {
                openDrawer(row.dataset.id);
            }
        });
    });
    document.querySelectorAll('.row-actions button').forEach(btn => {
        btn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            const id = btn.closest('.reg-row').dataset.id;
            if (btn.dataset.action === 'archive') archiveEngagement(id);
            else if (btn.dataset.action === 'restore') restoreEngagement(id);
            else if (btn.dataset.action === 'delete') deleteEngagement(id);
        });
    });

    // Dark mode toggle
    const darkModeBtn = document.querySelector('.icon-btn[title="Dark mode"]');
    function updateDarkModeIcon(isDark) {
        const icon = darkModeBtn?.querySelector('i');
        if (icon) {
            icon.classList.toggle('bi-moon', !isDark);
            icon.classList.toggle('bi-sun', isDark);
        }
    }
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    if (isDarkMode) document.body.classList.add('dark-mode');
    updateDarkModeIcon(isDarkMode);
    darkModeBtn?.addEventListener('click', () => {
        const isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', isDark);
        updateDarkModeIcon(isDark);
    });

    // Profile dropdown toggle
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');
    profileToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown?.classList.toggle('active');
    });
    document.addEventListener('click', (e) => {
        if (!profileToggle?.contains(e.target) && !profileDropdown?.contains(e.target)) {
            profileDropdown?.classList.remove('active');
        }
    });

    // Search + status filtering. "Needs attention" (setup: no team/DOL) and
    // "due soon" (dates) are independent toggles now, not one bucket - when
    // both are active a row shows if it matches EITHER, same as picking two
    // quick-filter chips normally means "show me anything flagged."
    let setupOnly = false;
    let dueSoonOnly = false;
    function applyFilters() {
        const query = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
        let visibleCount = 0;
        document.querySelectorAll('.reg-row').forEach(row => {
            const matchesSearch = !query || row.dataset.search.includes(query);
            const noStatusFilterActive = !setupOnly && !dueSoonOnly;
            const matchesStatus = noStatusFilterActive
                || (setupOnly && row.dataset.needsSetup === '1')
                || (dueSoonOnly && row.dataset.dueSoon === '1');
            const show = matchesSearch && matchesStatus;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
        document.querySelectorAll('.register').forEach(group => {
            const anyVisible = Array.from(group.querySelectorAll('.reg-row')).some(r => r.style.display !== 'none');
            const label = group.previousElementSibling;
            if (label && label.classList.contains('section-label')) {
                label.style.display = anyVisible ? '' : 'none';
            }
            group.style.display = anyVisible ? '' : 'none';
        });
        const note = document.getElementById('resultNote');
        if (note) note.textContent = `${visibleCount} shown`;
    }
    document.getElementById('searchInput')?.addEventListener('input', applyFilters);
    document.getElementById('attentionLink')?.addEventListener('click', () => {
        setupOnly = !setupOnly;
        document.getElementById('attentionLink').classList.toggle('active', setupOnly);
        applyFilters();
    });
    document.getElementById('dueSoonLink')?.addEventListener('click', () => {
        dueSoonOnly = !dueSoonOnly;
        document.getElementById('dueSoonLink').classList.toggle('active', dueSoonOnly);
        applyFilters();
    });
    applyFilters();

    // New Engagement Wizard
    (function() {
        const scrim = document.getElementById('engWizardScrim');
        const closeBtn = document.getElementById('engWizardClose');
        const cancelBtn = document.getElementById('engWizardCancel');
        const backBtn = document.getElementById('engWizardBack');
        const nextBtn = document.getElementById('engWizardNext');
        const stepButtons = Array.from(document.querySelectorAll('.eng-wizard-step'));
        const panels = Array.from(document.querySelectorAll('.eng-wizard-panel'));
        const TOTAL_STEPS = panels.length;
        const STATUS_LABELS = { planning: 'Planning', 'in-progress': 'In Progress', 'in-review': 'In Review', complete: 'Complete' };
        let currentStep = 1;
        let maxReached = 1;
        let selectedManager = null; // { emp_name, isNew } once picked from the roster (or entered as a new employee), null otherwise
        let clearManagerSelection = () => {}; // replaced once wireManagerSearch() runs
        let wizardTeamMembers = []; // [{ emp_name, role, isNew, hours }] — Senior/Staff/Intern only, Manager stays the Step 1 field above

        function setSegmented(id, value) {
            document.getElementById(id).querySelectorAll('button').forEach(b => b.classList.toggle('active', b.dataset.value === value));
        }
        function getSegmentedValue(id) {
            return document.getElementById(id).querySelector('button.active')?.dataset.value || null;
        }
        document.querySelectorAll('.eng-segmented').forEach(seg => {
            seg.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', () => {
                    seg.querySelectorAll('button').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    if (seg.id === 'new_soc_type_segment') updateSocDateFields();
                });
            });
        });

        function updateAuditVisibility() {
            const selected = Array.from(document.querySelectorAll('.new-audit-type-checkbox:checked')).map(cb => cb.value);
            const hasSOC = selected.includes('SOC 1') || selected.includes('SOC 2');
            document.getElementById('new_soc_type_section').style.display = hasSOC ? 'block' : 'none';
            document.querySelectorAll('.eng-chip').forEach(chip => {
                chip.classList.toggle('checked', chip.querySelector('input').checked);
            });
        }
        function updateSocDateFields() {
            const val = getSegmentedValue('new_soc_type_segment');
            document.getElementById('new_soc_type1_dates').style.display = val === 'Type 1' ? 'block' : 'none';
            document.getElementById('new_soc_type2_dates').style.display = val === 'Type 2' ? 'flex' : 'none';
        }
        document.querySelectorAll('.new-audit-type-checkbox').forEach(cb => cb.addEventListener('change', updateAuditVisibility));

        // Manager picker — same roster autocomplete as the drawer's "Manage
        // Team" (search-employees.php, falling back to add-employee.php for
        // a brand-new name), just fixed to role "manager" and scoped to a
        // single pick instead of a whole team list.
        function wireManagerSearch() {
            const input = document.getElementById('new_eng_manager_search');
            const list = document.getElementById('new_eng_manager_ac_list');
            const selectedBox = document.getElementById('new_eng_manager_selected');
            if (!input || !list) return;

            let debounceTimer = null;
            input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const query = input.value.trim();
                if (!query) { list.style.display = 'none'; return; }
                debounceTimer = setTimeout(() => searchEmployees(query), 200);
            });
            document.addEventListener('click', (ev) => {
                if (!ev.target.closest('#new_eng_manager_search') && !ev.target.closest('#new_eng_manager_ac_list')) {
                    list.style.display = 'none';
                }
            });

            function searchEmployees(query) {
                fetch('../api/search-employees.php?q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => renderResults(query, data.employees || []))
                    .catch(() => renderResults(query, []));
            }

            function renderResults(query, matches) {
                let html = matches.map(e => `
                    <div class="team2-ac-item" data-emp-name="${escAttr(e.emp_name)}">
                        <div class="team2-avatar" style="width:22px;height:22px;font-size:9px;background:var(--manager)">${initials(e.emp_name)}</div>
                        ${escapeHtml(e.emp_name)}
                        <span class="role">${ROLE_LABELS[e.emp_role] || e.emp_role}</span>
                    </div>
                `).join('');
                if (!matches.length) {
                    html += `<div class="team2-ac-empty">No employee named "${escapeHtml(query)}" in the roster.</div>`;
                }
                html += `<div class="team2-ac-newbtn" id="mgr_ac_new_btn">+ Add "${escapeHtml(query)}" as a new employee&hellip;</div>`;

                list.innerHTML = html;
                list.style.display = 'block';

                list.querySelectorAll('.team2-ac-item').forEach(item => {
                    item.addEventListener('click', () => selectManager(item.dataset.empName, false));
                });
                document.getElementById('mgr_ac_new_btn')?.addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    selectManager(query, true);
                });
            }

            function selectManager(name, isNew) {
                selectedManager = { emp_name: name, isNew };
                input.value = '';
                list.style.display = 'none';
                selectedBox.style.display = 'flex';
                selectedBox.innerHTML = `
                    <div class="avatar">${initials(name)}</div>
                    <div class="name">${escapeHtml(name)}${isNew ? ' <span style="font-weight:400;color:var(--text-muted);">(new)</span>' : ''}</div>
                    <button type="button" class="clear-btn" id="new_eng_manager_clear" title="Remove">&times;</button>
                `;
                document.getElementById('new_eng_manager_clear').addEventListener('click', clearManager);
            }

            function clearManager() {
                selectedManager = null;
                selectedBox.style.display = 'none';
                selectedBox.innerHTML = '';
            }

            clearManagerSelection = clearManager;
        }
        wireManagerSearch();

        // Team-members picker (Step 2) — same roster autocomplete as the
        // manager picker and the drawer's "Manage Team" modal, but supports
        // adding more than one person plus an hours-per-person input.
        // Manager is filtered out of both the search results and the
        // new-employee role choices — that role stays the dedicated Step 1
        // field, same reasoning as the DOL Generator excluding Manager from
        // its own hours step.
        function wireTeamSearch() {
            const input = document.getElementById('new_eng_team_search');
            const list = document.getElementById('new_eng_team_ac_list');
            if (!input || !list) return;

            let debounceTimer = null;
            input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const query = input.value.trim();
                if (!query) { list.style.display = 'none'; return; }
                debounceTimer = setTimeout(() => searchEmployees(query), 200);
            });
            document.addEventListener('click', (ev) => {
                if (!ev.target.closest('#new_eng_team_search') && !ev.target.closest('#new_eng_team_ac_list')) {
                    list.style.display = 'none';
                }
            });

            function searchEmployees(query) {
                fetch('../api/search-employees.php?q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => renderResults(query, data.employees || []))
                    .catch(() => renderResults(query, []));
            }

            function renderResults(query, allMatches) {
                const addedNames = wizardTeamMembers.map(m => m.emp_name.toLowerCase());
                const matches = allMatches.filter(e => e.emp_role !== 'manager' && !addedNames.includes(e.emp_name.toLowerCase()));

                let html = matches.map(e => `
                    <div class="team2-ac-item" data-emp-name="${escAttr(e.emp_name)}" data-emp-role="${escAttr(e.emp_role)}">
                        <div class="team2-avatar" style="width:22px;height:22px;font-size:9px;background:${ROLE_COLOR_VAR[e.emp_role] || 'var(--ink)'}">${initials(e.emp_name)}</div>
                        ${escapeHtml(e.emp_name)}
                        <span class="role">${ROLE_LABELS[e.emp_role] || e.emp_role}</span>
                    </div>
                `).join('');
                if (!matches.length) {
                    html += `<div class="team2-ac-empty">No matching employee in the roster.</div>`;
                }
                html += `<div class="team2-ac-newbtn" id="team_ac_new_btn">+ Add "${escapeHtml(query)}" as a new employee&hellip;</div>`;

                list.innerHTML = html;
                list.style.display = 'block';

                list.querySelectorAll('.team2-ac-item').forEach(item => {
                    item.addEventListener('click', () => {
                        addWizardTeamMember(item.dataset.empName, item.dataset.empRole, false);
                        input.value = '';
                        list.style.display = 'none';
                    });
                });
                document.getElementById('team_ac_new_btn')?.addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    renderNewEmployeeRolePicker(query);
                });
            }

            function renderNewEmployeeRolePicker(name) {
                list.innerHTML = `
                    <div class="team2-ac-empty" style="padding-bottom:4px;">Role for "${escapeHtml(name)}"?</div>
                    ${['senior', 'staff', 'intern'].map(role => `<div class="team2-ac-item" data-role="${role}">${ROLE_LABELS[role]}</div>`).join('')}
                `;
                list.querySelectorAll('.team2-ac-item').forEach(item => {
                    item.addEventListener('click', () => {
                        addWizardTeamMember(name, item.dataset.role, true);
                        input.value = '';
                        list.style.display = 'none';
                    });
                });
            }
        }
        wireTeamSearch();

        function addWizardTeamMember(empName, role, isNew) {
            wizardTeamMembers.push({ emp_name: empName, role, isNew, hours: null });
            renderWizardTeamList();
        }

        function renderWizardTeamList() {
            const el = document.getElementById('new_eng_team_list');
            if (!el) return;
            if (!wizardTeamMembers.length) { el.innerHTML = ''; return; }
            el.innerHTML = wizardTeamMembers.map((m, i) => `
                <div class="eng-team-row">
                    <div class="avatar" style="background:${ROLE_COLOR_VAR[m.role] || 'var(--ink)'}">${initials(m.emp_name)}</div>
                    <div class="info">
                        <div class="name">${escapeHtml(m.emp_name)}${m.isNew ? ' <span style="font-weight:400;color:var(--text-muted);">(new)</span>' : ''}</div>
                        <div class="role">${ROLE_LABELS[m.role] || m.role}</div>
                    </div>
                    <input type="number" class="hours-input" min="0" step="0.5" value="${m.hours ?? ''}" placeholder="0" data-index="${i}" title="Budgeted hours">
                    <span class="hours-suffix">hrs</span>
                    <button type="button" class="remove-btn" data-index="${i}" title="Remove">&times;</button>
                </div>
            `).join('');

            el.querySelectorAll('.hours-input').forEach(inp => {
                inp.addEventListener('input', () => {
                    wizardTeamMembers[Number(inp.dataset.index)].hours = inp.value === '' ? null : inp.value;
                });
            });
            el.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    wizardTeamMembers.splice(Number(btn.dataset.index), 1);
                    renderWizardTeamList();
                });
            });
        }

        function resetWizard() {
            ['new_eng_name', 'new_eng_location', 'new_eng_poc', 'new_eng_tsc', 'new_eng_scope', 'new_eng_notes',
             'new_soc_as_of_date', 'new_soc_start_period', 'new_soc_end_period'].forEach(id => {
                document.getElementById(id).value = '';
            });
            document.getElementById('new_eng_repeat').checked = false;
            document.getElementById('new_eng_name').style.borderColor = '';
            document.querySelectorAll('.new-audit-type-checkbox').forEach(cb => cb.checked = false);
            document.querySelectorAll('.eng-chip').forEach(chip => chip.classList.remove('checked'));
            setSegmented('new_eng_status_segment', 'planning');
            setSegmented('new_soc_type_segment', null);
            document.getElementById('new_soc_type_section').style.display = 'none';
            document.getElementById('new_soc_type1_dates').style.display = 'none';
            document.getElementById('new_soc_type2_dates').style.display = 'none';
            document.getElementById('new_eng_manager_search').value = '';
            document.getElementById('new_eng_manager_ac_list').style.display = 'none';
            clearManagerSelection();
            document.getElementById('new_eng_team_search').value = '';
            document.getElementById('new_eng_team_ac_list').style.display = 'none';
            wizardTeamMembers = [];
            renderWizardTeamList();
            nextBtn.disabled = false;
            goToStep(1);
            maxReached = 1;
            stepButtons.forEach(b => b.classList.remove('clickable'));
        }

        function openWizard() {
            resetWizard();
            scrim.classList.add('open');
            setTimeout(() => document.getElementById('new_eng_name').focus(), 0);
        }
        function closeWizard() {
            scrim.classList.remove('open');
        }

        function goToStep(step) {
            currentStep = step;
            maxReached = Math.max(maxReached, step);
            panels.forEach(p => p.classList.toggle('active', Number(p.dataset.panel) === step));
            stepButtons.forEach(b => {
                const s = Number(b.dataset.step);
                b.classList.toggle('active', s === step);
                b.classList.toggle('done', s < step);
                b.classList.toggle('clickable', s <= maxReached);
            });
            backBtn.style.visibility = step === 1 ? 'hidden' : 'visible';
            nextBtn.textContent = step === TOTAL_STEPS ? 'Create Engagement' : 'Next';
            if (step === TOTAL_STEPS) renderReview();
        }

        function renderReview() {
            const name = document.getElementById('new_eng_name').value.trim() || '\u2014';
            const status = getSegmentedValue('new_eng_status_segment') || 'planning';
            const types = Array.from(document.querySelectorAll('.new-audit-type-checkbox:checked')).map(cb => cb.value).join(', ') || 'None selected';
            const tsc = document.getElementById('new_eng_tsc').value.trim() || '\u2014';
            const managerLabel = selectedManager ? selectedManager.emp_name : 'Unassigned';
            const teamLabel = wizardTeamMembers.length
                ? wizardTeamMembers.map(m => `${m.emp_name} (${ROLE_LABELS[m.role] || m.role}${m.hours ? ', ' + m.hours + ' hrs' : ''})`).join(', ')
                : 'None added';
            document.getElementById('eng_review_summary').innerHTML = `
                <div class="eng-review-row"><dt>Name</dt><dd>${escapeHtml(name)}</dd></div>
                <div class="eng-review-row"><dt>Manager</dt><dd>${escapeHtml(managerLabel)}</dd></div>
                <div class="eng-review-row"><dt>Team</dt><dd>${escapeHtml(teamLabel)}</dd></div>
                <div class="eng-review-row"><dt>Status</dt><dd>${escapeHtml(STATUS_LABELS[status] || status)}</dd></div>
                <div class="eng-review-row"><dt>Audit Types</dt><dd>${escapeHtml(types)}</dd></div>
                <div class="eng-review-row"><dt>TSC</dt><dd>${escapeHtml(tsc)}</dd></div>
            `;
        }

        stepButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                const s = Number(btn.dataset.step);
                if (s <= maxReached) goToStep(s);
            });
        });
        backBtn.addEventListener('click', () => { if (currentStep > 1) goToStep(currentStep - 1); });
        closeBtn.addEventListener('click', closeWizard);
        cancelBtn.addEventListener('click', closeWizard);
        scrim.addEventListener('click', (ev) => { if (ev.target === scrim) closeWizard(); });
        window.addEventListener('keydown', (ev) => {
            if (ev.key === 'Escape' && scrim.classList.contains('open')) closeWizard();
        });

        nextBtn.addEventListener('click', () => {
            if (currentStep === 1) {
                const nameField = document.getElementById('new_eng_name');
                if (!nameField.value.trim()) {
                    nameField.focus();
                    nameField.style.borderColor = 'var(--critical)';
                    return;
                }
                nameField.style.borderColor = '';
            }
            if (currentStep < TOTAL_STEPS) {
                goToStep(currentStep + 1);
                return;
            }
            submitEngagement();
        });

        function submitEngagement() {
            const engName = document.getElementById('new_eng_name').value.trim();
            if (!engName) { goToStep(1); return; }
            nextBtn.disabled = true;
            backBtn.disabled = true;
            nextBtn.textContent = 'Creating\u2026';
            const selectedAuditTypes = Array.from(document.querySelectorAll('.new-audit-type-checkbox:checked')).map(cb => cb.value).join(', ');
            const newEngagementData = {
                eng_name: engName,
                eng_location: document.getElementById('new_eng_location').value || null,
                eng_poc: document.getElementById('new_eng_poc').value || null,
                eng_status: getSegmentedValue('new_eng_status_segment') || 'planning',
                eng_tsc: document.getElementById('new_eng_tsc').value || null,
                eng_audit_type: selectedAuditTypes || null,
                eng_soc_type: getSegmentedValue('new_soc_type_segment'),
                eng_scope: document.getElementById('new_eng_scope').value || null,
                eng_as_of_date: document.getElementById('new_soc_as_of_date').value || null,
                eng_start_period: document.getElementById('new_soc_start_period').value || null,
                eng_end_period: document.getElementById('new_soc_end_period').value || null,
                eng_repeat: document.getElementById('new_eng_repeat').checked ? 'Y' : 'N',
                eng_notes: document.getElementById('new_eng_notes').value || null
            };

            function resetSubmitButton() {
                nextBtn.disabled = false;
                backBtn.disabled = false;
                nextBtn.textContent = 'Create Engagement';
            }

            // Manager + team assignment happen as a second batch of calls,
            // chained after the engagement actually exists — engagement_team
            // rows are keyed by engagement_idno, which only exists once
            // create-engagement.php returns it. If any of these secondary
            // calls fail, the engagement itself was still created
            // successfully, so this reloads either way rather than
            // stranding the user on an error for what's ultimately a
            // convenience step (they can always add people from the Team
            // card afterward).
            function addPersonToTeam(engagementIdno, empName, role, isNew, hours) {
                const addMember = () => fetch('../api/add-team-member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ engagement_idno: engagementIdno, emp_name: empName, role, budgeted_hours: hours })
                });
                if (isNew) {
                    return fetch('../api/add-employee.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ emp_name: empName, emp_role: role })
                    }).then(addMember);
                }
                return addMember();
            }

            function assignTeamThenReload(engagementIdno) {
                const tasks = [];
                if (selectedManager) {
                    tasks.push(addPersonToTeam(engagementIdno, selectedManager.emp_name, 'manager', selectedManager.isNew, null));
                }
                wizardTeamMembers.forEach(m => {
                    tasks.push(addPersonToTeam(engagementIdno, m.emp_name, m.role, m.isNew, m.hours));
                });
                Promise.allSettled(tasks).finally(() => location.reload());
            }

            fetch('../api/create-engagement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newEngagementData)
            })
            .then(response => response.text())
            .then(text => {
                let data;
                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    if (text.includes('"success":true')) {
                        data = { success: true };
                    } else {
                        resetSubmitButton();
                        Swal.fire('Error', 'Invalid response from server: ' + text.substring(0, 200), 'error');
                        return;
                    }
                }
                if (!data.success) {
                    resetSubmitButton();
                    Swal.fire('Error', data.message || 'Failed to create engagement', 'error');
                    return;
                }
                sessionStorage.setItem('showEngagementCreatedToast', 'true');
                if ((selectedManager || wizardTeamMembers.length) && data.engagement_id) {
                    assignTeamThenReload(data.engagement_id);
                } else {
                    location.reload();
                }
            })
            .catch(error => {
                resetSubmitButton();
                Swal.fire('Error', 'Failed to create engagement: ' + error.message, 'error');
            });
        }

        document.querySelector('.btn-new-engagement')?.addEventListener('click', openWizard);
    })();

    // ===================================================================
    // ENGAGEMENT DRAWER
    // Quick-view slide-over replacing engagement-details.php as the way
    // an engagement opens from this list. Team/Timeline/Edit modals below
    // are ported near-verbatim from that page so their behavior matches.
    // ===================================================================
    const STATUS_META = <?php echo json_encode($statusMeta); ?>;
    const DOL_AUDIT_TYPES = {
        'SOC 1':   'emp_soc1_dol',
        'SOC 2':   'emp_soc2_dol',
        'HIPAA':   'emp_hipaa_dol',
        'HITRUST': 'emp_hitrust_dol',
        'FISMA':   'emp_fisma_dol'
    };
    const DOL_TYPE_CLASS = { 'SOC 1': '', 'SOC 2': 't-soc2', 'HIPAA': '', 'HITRUST': 't-soc2', 'FISMA': '' };
    // SOC 2's fixed criteria order: the 9 Common Criteria numerically, then
    // the 4 additional Trust Services Categories. Anything not on this list
    // (shouldn't normally happen, but a stray freeform duty is still
    // possible) sorts after it, alphabetically, rather than disappearing.
    const SOC2_DOL_ORDER = ['CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9', 'Availability', 'Confidentiality', 'Processing Integrity', 'Privacy'];
    // Always shows duties in this canonical order rather than whatever
    // order they were typed in — SOC 2 uses the fixed list above; SOC 1
    // (CO1, CO2, ...) and anything else just sorts numerically by whatever
    // digits are in the tag, falling back to alphabetical for non-numeric
    // ones. Used both for the read-only Team card and the tag input in
    // "Edit Team Member" (which re-sorts on every render, not just once,
    // so removing a tag doesn't leave a stale order behind either).
    function sortDolTags(tags, auditType) {
        const sorted = [...tags];
        if (auditType === 'SOC 2') {
            sorted.sort((a, b) => {
                const ia = SOC2_DOL_ORDER.indexOf(a);
                const ib = SOC2_DOL_ORDER.indexOf(b);
                if (ia !== -1 && ib !== -1) return ia - ib;
                if (ia !== -1) return -1;
                if (ib !== -1) return 1;
                return a.localeCompare(b);
            });
        } else {
            sorted.sort((a, b) => {
                const na = parseInt(a.replace(/\D/g, ''), 10);
                const nb = parseInt(b.replace(/\D/g, ''), 10);
                if (!isNaN(na) && !isNaN(nb)) return na - nb;
                if (!isNaN(na)) return -1;
                if (!isNaN(nb)) return 1;
                return a.localeCompare(b);
            });
        }
        return sorted;
    }
    const ROLE_COLOR_VAR = { manager: 'var(--manager)', senior: 'var(--senior)', staff: 'var(--staff)', intern: 'var(--intern)' };
    const ROLE_LABELS = { manager: 'Manager', senior: 'Senior', staff: 'Staff', intern: 'Intern' };
    const TIMELINE_STEPS = [
        { label: 'Internal Planning Call', date: 'internal_planning_call_date', completed: 'internal_planning_call_completed_at' },
        { label: 'Planning Memo',          date: 'planning_memo_date',          completed: 'planning_memo_completed_at' },
        { label: 'IRL Due',                date: 'irl_due_date',                completed: 'irl_completed_at' },
        { label: 'Client Planning Call',   date: 'client_planning_call_date',   completed: 'client_planning_call_completed_at' },
        { label: 'Fieldwork - Client Calls',    date: 'fieldwork_client_calls_end_date',    startDate: 'fieldwork_client_calls_start_date',    completed: 'fieldwork_client_calls_completed_at' },
        { label: 'Fieldwork - Documentation',   date: 'fieldwork_documentation_end_date',   startDate: 'fieldwork_documentation_start_date',   completed: 'fieldwork_documentation_completed_at' },
        { label: 'Leadsheet Due',          date: 'leadsheet_date',              completed: 'leadsheet_completed_at' },
        { label: 'Conclusion Memo',        date: 'conclusion_memo_date',        completed: 'conclusion_memo_completed_at' },
        { label: 'Draft Report Due',       date: 'draft_report_due_date',       completed: 'draft_report_completed_at' },
        { label: 'Final Report',           date: 'final_report_date',           completed: 'final_report_completed_at' },
        { label: 'Archive',                date: 'archive_date',                completed: 'archive_completed_at' }
    ];

    let drawerData = null;
    const drawerEl = document.getElementById('drawer');
    const drawerScrimEl = document.getElementById('drawerScrim');

    function initials(name) {
        return (name || '').split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('');
    }
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    function escAttr(str) {
        return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }
    // Trims a whole-number ".00" but keeps a half-hour like ".5" — mirrors
    // the same formatting on the read-only Team card (engagement-details.php).
    function fmtHours(hours) {
        if (hours === null || hours === undefined || hours === '') return null;
        const n = parseFloat(hours);
        if (isNaN(n)) return null;
        return n.toFixed(2).replace(/\.?0+$/, '');
    }
    function fmtDate(raw) {
        if (!raw || raw === '0000-00-00') return null;
        const d = new Date(raw.length === 10 ? raw + 'T00:00:00' : raw);
        if (isNaN(d.getTime())) return null;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }
    // Date + time, for note/meeting timestamps (fmtDate above is date-only).
    // Built from two separate toLocaleString calls rather than one combined
    // string with a comma-replace — combining date and time options in one
    // call produces two commas ("Sep 11, 2026, 10:23 AM"), and replacing
    // "the first comma" silently grabs the wrong one.
    function fmtDateTime(raw) {
        if (!raw) return null;
        const d = new Date(raw.replace(' ', 'T'));
        if (isNaN(d.getTime())) return null;
        const datePart = d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        const timePart = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
        return datePart + ' · ' + timePart;
    }

    async function fetchEngagementData(id) {
        const res = await fetch('../api/get-engagement-details.php?id=' + encodeURIComponent(id));
        return res.json();
    }

    async function openDrawer(id) {
        drawerEl.classList.remove('closed');
        drawerScrimEl.classList.remove('hidden');
        document.getElementById('drawerBody').innerHTML = '<div class="drawer-loading">Loading&hellip;</div>';
        const data = await fetchEngagementData(id);
        if (!data.success) {
            closeDrawer();
            Swal.fire('Error', data.message || 'Failed to load engagement', 'error');
            return;
        }
        drawerData = data;
        renderDrawer(data);
    }

    function closeDrawer() {
        drawerEl.classList.add('closed');
        drawerScrimEl.classList.add('hidden');
    }

    async function refreshDrawer() {
        if (!drawerData) return;
        const data = await fetchEngagementData(drawerData.engagement.eng_idno);
        if (data.success) {
            drawerData = data;
            renderDrawer(data);
        }
    }

    function reopenDrawerAfterReload(id, extraFlag) {
        sessionStorage.setItem('reopenDrawerFor', id);
        if (extraFlag) sessionStorage.setItem(extraFlag, 'true');
    }

    // Quick status change from the drawer header popover. Reloads (rather than
    // refreshing the drawer in place) because a status change can move the
    // engagement into a different section on the list behind it.
    async function updateEngagementStatus(engagementId, newStatus) {
        try {
            const response = await fetch('../api/update-engagement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ engagement_id: engagementId, eng_status: newStatus })
            });
            const data = await response.json();
            if (data.success) {
                reopenDrawerAfterReload(engagementId);
                sessionStorage.setItem('showEngagementUpdatedToast', 'true');
                location.reload();
            } else {
                Swal.fire('Error', data.message || 'Failed to update status', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('Error', 'Failed to update status', 'error');
        }
    }

    function renderDrawer(data) {
        const eng = data.engagement;
        const timeline = data.timeline || {};
        const team = data.team || [];

        document.getElementById('drawerEngId').textContent = eng.eng_idno;
        document.getElementById('drawerClientName').textContent = eng.eng_name;

        const statusInfo = STATUS_META[eng.eng_status] || { label: eng.eng_status, var: '--text-muted' };
        const statusOptionsHtml = Object.entries(STATUS_META).map(([key, meta]) => `
            <div class="drawer-status-popover-item ${key === eng.eng_status ? 'current' : ''}" data-status="${key}">
                <span class="dot" style="background:var(${meta.var})"></span>${escapeHtml(meta.label)}<span class="check"><i class="bi bi-check"></i></span>
            </div>
        `).join('');
        let badgeHtml = `
            <div class="drawer-status-wrap" id="drawerStatusWrap">
                <button class="drawer-badge drawer-status-badge" id="drawerStatusBadge" style="background:color-mix(in srgb, var(${statusInfo.var}) 12%, transparent); color:var(${statusInfo.var})">
                    ${escapeHtml(statusInfo.label)} <i class="bi bi-chevron-down"></i>
                </button>
                <div class="drawer-status-popover" id="drawerStatusPopover">${statusOptionsHtml}</div>
            </div>
        `;
        const auditTypes = (eng.eng_audit_type || '').split(',').map(t => t.trim()).filter(Boolean);
        if (eng.eng_repeat === 'Y') {
            badgeHtml += `<span class="drawer-badge b-repeat"><i class="bi bi-arrow-repeat"></i> Repeat</span>`;
        }
        document.getElementById('drawerBadgeRow').innerHTML = badgeHtml;

        document.getElementById('drawerStatusBadge').addEventListener('click', (ev) => {
            ev.stopPropagation();
            document.getElementById('drawerStatusPopover').classList.toggle('open');
        });
        document.querySelectorAll('#drawerStatusPopover .drawer-status-popover-item').forEach(item => {
            item.addEventListener('click', (ev) => {
                ev.stopPropagation();
                document.getElementById('drawerStatusPopover').classList.remove('open');
                if (item.dataset.status !== eng.eng_status) {
                    updateEngagementStatus(eng.eng_idno, item.dataset.status);
                }
            });
        });

        let period = 'N/A';
        if (eng.eng_start_period && eng.eng_end_period) {
            period = `${fmtDate(eng.eng_start_period) || 'N/A'} – ${fmtDate(eng.eng_end_period) || 'N/A'}`;
        } else if (eng.eng_as_of_date) {
            period = 'As of ' + (fmtDate(eng.eng_as_of_date) || 'N/A');
        }
        const reportTypeRow = eng.eng_soc_type
            ? `<div class="drawer-info-item"><div class="drawer-info-label">Report Type</div><div class="drawer-info-value">${escapeHtml(eng.eng_soc_type === 'Type 1' ? 'Type I' : (eng.eng_soc_type === 'Type 2' ? 'Type II' : eng.eng_soc_type))}</div></div>`
            : '';

        document.getElementById('drawerBody').innerHTML = `
            <div id="drawerSetupBanner"></div>
            <div class="drawer-section">
                <div class="drawer-section-head"><div class="drawer-section-title"><span class="dot" style="background:var(--ink)"></span>Overview</div></div>
                <div class="drawer-info-grid">
                    <div class="drawer-info-item"><div class="drawer-info-label">Location</div><div class="drawer-info-value">${escapeHtml(eng.eng_location || 'N/A')}</div></div>
                    <div class="drawer-info-item"><div class="drawer-info-label">Review Period</div><div class="drawer-info-value">${escapeHtml(period)}</div></div>
                    <div class="drawer-info-item"><div class="drawer-info-label">Audit Type</div><div class="drawer-info-value">${escapeHtml(auditTypes.join(', ') || 'N/A')}</div></div>
                    ${reportTypeRow}
                    <div class="drawer-info-item"><div class="drawer-info-label">Manager</div><div class="drawer-info-value">${escapeHtml(eng.eng_manager || 'Unassigned')}</div></div>
                    <div class="drawer-info-item"><div class="drawer-info-label">Point of Contact</div><div class="drawer-info-value">${escapeHtml(eng.eng_poc || 'N/A')}</div></div>
                </div>
            </div>

            <div class="drawer-section">
                <div class="drawer-section-head"><div class="drawer-section-title"><span class="dot" style="background:var(--caution)"></span>Details</div></div>
                <div class="drawer-details-grid">
                    <div class="drawer-info-item"><div class="drawer-info-label">Created</div><div class="drawer-info-value muted">${fmtDate(eng.eng_created) || 'N/A'}</div></div>
                    <div class="drawer-info-item"><div class="drawer-info-label">Last Updated</div><div class="drawer-info-value muted">${fmtDate(eng.eng_updated) || 'N/A'}</div></div>
                    <div class="drawer-info-item"><div class="drawer-info-label">Archive Date</div><div class="drawer-info-value muted">${fmtDate(eng.eng_archive) || 'Not archived'}</div></div>
                    <div class="drawer-info-item drawer-detail-scope"><div class="drawer-info-label">Scope</div><div class="drawer-info-value">${escapeHtml(eng.eng_scope || 'N/A')}</div></div>
                </div>
            </div>

            <div class="drawer-section">
                <div class="drawer-section-head">
                    <div class="drawer-section-title"><span class="dot" style="background:var(--senior)"></span>Notes &amp; Meetings</div>
                </div>
                <div class="mtg-actions">
                    <button class="mtg-action-btn t-planning" id="mtgOpenPlanning" type="button"><i class="bi bi-clipboard2-check"></i> Planning Meeting</button>
                    <button class="mtg-action-btn t-client" id="mtgOpenClient" type="button"><i class="bi bi-camera-video"></i> Client Planning</button>
                    <button class="mtg-action-btn t-weekly" id="mtgOpenWeekly" type="button"><i class="bi bi-arrow-repeat"></i> Weekly Status</button>
                    <button class="mtg-action-btn t-general" id="mtgOpenGeneral" type="button"><i class="bi bi-pencil"></i> Note</button>
                </div>
                <div class="note-log" id="drawerNoteLog"></div>
            </div>

            <div class="drawer-section">
                <div class="drawer-section-head">
                    <div class="drawer-section-title"><span class="dot" style="background:var(--manager)"></span>Team (DOL)</div>
                    <button class="drawer-link-btn" id="drawerManageTeamBtn">Manage Team</button>
                </div>
                <div id="drawerTeamContent"></div>
            </div>

            <div class="drawer-section">
                <div class="drawer-section-head">
                    <div class="drawer-section-title"><span class="dot" style="background:var(--good)"></span>Timeline &amp; Key Dates</div>
                    <div style="display:flex; gap:0.9rem;">
                        <button class="drawer-link-btn" id="drawerImportTimelineBtn">Import Timeline</button>
                        <button class="drawer-link-btn" id="drawerEditTimelineBtn">Edit Timeline</button>
                    </div>
                </div>
                <div id="drawerPlanningDocRow" class="drawer-planning-doc-row"></div>
                <div id="drawerTimelineContent"></div>
                <div class="drawer-tl-hint">Click a date to mark it complete or incomplete.</div>
                <div class="drawer-weekly-call-row">
                    <label for="drawerWeeklyCallSelect"><i class="bi bi-arrow-repeat"></i> Weekly Status Call</label>
                    <select id="drawerWeeklyCallSelect">
                        <option value="">Not set</option>
                        <option value="0">Sunday</option>
                        <option value="1">Monday</option>
                        <option value="2">Tuesday</option>
                        <option value="3">Wednesday</option>
                        <option value="4">Thursday</option>
                        <option value="5">Friday</option>
                        <option value="6">Saturday</option>
                    </select>
                </div>
                <div id="drawerWeeklyCallLinks" class="wcall-linked-row"></div>
            </div>
        `;

        updateDrawerSetupBanner(eng, team, auditTypes, timeline);
        renderDrawerTeam(team, auditTypes);
        renderNoteLog(data.notes || []);
        wireMeetingButtons();
        renderDrawerTimeline(timeline, eng.eng_idno);
        renderPlanningDocRow(eng);
        renderWeeklyStatusCallControl(timeline, eng.eng_idno, eng.eng_name, data.linked_calls || []);

        document.getElementById('drawerManageTeamBtn').addEventListener('click', openManageTeamModal);
        document.getElementById('drawerEditTimelineBtn').addEventListener('click', () => openEditTimelineModal());
        document.getElementById('drawerImportTimelineBtn').addEventListener('click', () => {
            document.getElementById('timelineImportFileInput').click();
        });
    }

    // Recurring, not a one-time date — no completed_at, no due/overdue
    // state, just "does this engagement have a standing weekly call, and on
    // what day." Saves immediately on change (matches the settings-page
    // toggle pattern) rather than needing the full Edit Timeline modal for
    // a single field. linkedCalls is the list of other engagements sharing
    // this one's call (from api/get-engagement-details.php), if any — two
    // or more engagements can share the same call so the calendar shows
    // one combined entry instead of duplicate chips.
    function renderWeeklyStatusCallControl(timeline, engagementId, engagementName, linkedCalls) {
        const select = document.getElementById('drawerWeeklyCallSelect');
        const day = timeline.weekly_status_call_day;
        select.value = (day === null || day === undefined) ? '' : String(day);
        select.addEventListener('change', async () => {
            const value = select.value;
            try {
                const response = await fetch('../api/update-timeline.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ engagement_id: engagementId, weekly_status_call_day: value })
                });
                const resData = await response.json();
                if (!resData.success) {
                    Swal.fire('Error', resData.message || 'Failed to save weekly status call', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to save weekly status call', 'error');
            }
        });

        renderWeeklyCallLinks(engagementId, engagementName, linkedCalls, timeline.weekly_status_call_group_name, day !== null && day !== undefined);
    }

    function renderWeeklyCallLinks(engagementId, engagementName, linkedCalls, groupName, hasDay) {
        const container = document.getElementById('drawerWeeklyCallLinks');
        const chips = linkedCalls.map(l => `
            <span class="wcall-chip">${escapeHtml(l.eng_name)}<button class="wcall-unlink" data-idno="${escAttr(l.engagement_idno)}" title="Unlink">&times;</button></span>
        `).join('');
        const linkBtn = hasDay
            ? `<button class="wcall-link-btn" id="wcallLinkBtn">+ Link engagement</button>`
            : `<span class="wcall-link-hint">Set a day to link another engagement</span>`;
        const nameRow = linkedCalls.length
            ? `<div class="wcall-name-row">"${escapeHtml(groupName || '')}" <button class="wcall-rename-btn" id="wcallRenameBtn" title="Rename"><i class="bi bi-pencil"></i></button></div>`
            : '';

        container.innerHTML = `
            ${nameRow}
            ${linkedCalls.length ? `<span class="wcall-linked-label">Linked with:</span>${chips}` : ''}
            ${linkBtn}
            <div class="wcall-ac-wrap" id="wcallAcWrap" style="display:none;">
                <input type="text" class="wcall-ac-input" id="wcallAcInput" placeholder="Search engagements&hellip;">
                <div class="wcall-ac-list" id="wcallAcList"></div>
            </div>
        `;

        container.querySelectorAll('.wcall-unlink').forEach(btn => {
            btn.addEventListener('click', async () => {
                try {
                    const response = await fetch('../api/unlink-weekly-status-call.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ engagement_idno: btn.dataset.idno })
                    });
                    const resData = await response.json();
                    if (resData.success) refreshDrawer();
                    else Swal.fire('Error', resData.message || 'Failed to unlink', 'error');
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to unlink', 'error');
                }
            });
        });

        document.getElementById('wcallRenameBtn')?.addEventListener('click', async () => {
            const result = await Swal.fire({
                title: 'Rename weekly status call',
                input: 'text',
                inputValue: groupName || '',
                inputAttributes: { maxlength: 100 },
                showCancelButton: true,
                confirmButtonText: 'Save',
                inputValidator: (v) => !v.trim() ? 'Enter a name' : undefined
            });
            if (!result.isConfirmed) return;
            try {
                const response = await fetch('../api/rename-weekly-status-call.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ engagement_idno: engagementId, name: result.value.trim() })
                });
                const resData = await response.json();
                if (resData.success) refreshDrawer();
                else Swal.fire('Error', resData.message || 'Failed to rename', 'error');
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to rename', 'error');
            }
        });

        const linkBtnEl = document.getElementById('wcallLinkBtn');
        const acWrap = document.getElementById('wcallAcWrap');
        const acInput = document.getElementById('wcallAcInput');
        const acList = document.getElementById('wcallAcList');
        if (!linkBtnEl) return;

        linkBtnEl.addEventListener('click', (ev) => {
            ev.stopPropagation();
            acWrap.style.display = 'block';
            acInput.focus();
        });
        document.addEventListener('click', (ev) => {
            if (!ev.target.closest('.wcall-ac-wrap') && ev.target.id !== 'wcallLinkBtn') {
                acWrap.style.display = 'none';
            }
        });

        async function linkTo(idno) {
            // Only the first link needs a name — once a group exists, the
            // name already carries over to whoever else joins it.
            let callName = '';
            if (!groupName) {
                const result = await Swal.fire({
                    title: 'Name this weekly status call',
                    text: 'Shown on the calendar for every engagement linked to it.',
                    input: 'text',
                    inputValue: `${engagementName} Call`,
                    inputAttributes: { maxlength: 100 },
                    showCancelButton: true,
                    confirmButtonText: 'Link',
                    inputValidator: (v) => !v.trim() ? 'Enter a name' : undefined
                });
                if (!result.isConfirmed) return;
                callName = result.value.trim();
            }
            try {
                const linkRes = await fetch('../api/link-weekly-status-call.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ engagement_idno: engagementId, link_to_idno: idno, call_name: callName })
                });
                const linkData = await linkRes.json();
                if (linkData.success) refreshDrawer();
                else Swal.fire('Error', linkData.message || 'Failed to link engagement', 'error');
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to link engagement', 'error');
            }
        }

        let debounceTimer = null;
        acInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const query = acInput.value.trim();
            if (!query) { acList.innerHTML = ''; return; }
            debounceTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`../api/search-engagements.php?q=${encodeURIComponent(query)}&exclude=${encodeURIComponent(engagementId)}`);
                    const resData = await res.json();
                    const matches = resData.engagements || [];
                    acList.innerHTML = matches.length
                        ? matches.map(e => `<div class="wcall-ac-item" data-idno="${escAttr(e.eng_idno)}">${escapeHtml(e.eng_name)} <span class="wcall-ac-id">${escapeHtml(e.eng_idno)}</span></div>`).join('')
                        : `<div class="wcall-ac-empty">No matching engagements.</div>`;
                    acList.querySelectorAll('.wcall-ac-item').forEach(item => {
                        item.addEventListener('click', () => linkTo(item.dataset.idno));
                    });
                } catch (error) {
                    console.error('Error:', error);
                }
            }, 200);
        });
    }

    // ---------- Planning doc (uploaded screenshot of the client's key-dates table) ----------
    function renderPlanningDocRow(eng) {
        const row = document.getElementById('drawerPlanningDocRow');
        if (eng.eng_planning_doc) {
            row.innerHTML = `
                <button class="drawer-link-btn" id="drawerViewDocBtn"><i class="bi bi-image"></i> View Planning Doc</button>
                <button class="drawer-link-btn" id="drawerReplaceDocBtn">Replace</button>
                <button class="drawer-link-btn drawer-link-btn-danger" id="drawerRemoveDocBtn">Remove</button>
            `;
            document.getElementById('drawerViewDocBtn').addEventListener('click', () => {
                openDocLightbox('../api/view-engagement-screenshot.php?id=' + encodeURIComponent(eng.eng_idno));
            });
            document.getElementById('drawerReplaceDocBtn').addEventListener('click', () => {
                document.getElementById('planningDocFileInput').click();
            });
            document.getElementById('drawerRemoveDocBtn').addEventListener('click', () => {
                Swal.fire({
                    title: 'Remove Planning Doc?',
                    text: 'This deletes the uploaded screenshot for this engagement. This cannot be undone.',
                    icon: 'warning',
                    confirmButtonText: 'Remove',
                    cancelButtonText: 'Cancel',
                    showCancelButton: true,
                    confirmButtonColor: 'var(--danger-red)'
                }).then((result) => {
                    if (!result.isConfirmed) return;
                    fetch('../api/delete-engagement-screenshot.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ engagement_id: eng.eng_idno })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) refreshDrawer();
                        else Swal.fire('Error', data.message || 'Failed to remove screenshot', 'error');
                    })
                    .catch(() => Swal.fire('Error', 'Failed to remove screenshot', 'error'));
                });
            });
        } else {
            row.innerHTML = `<button class="drawer-link-btn" id="drawerUploadDocBtn"><i class="bi bi-upload"></i> Upload Planning Doc</button>`;
            document.getElementById('drawerUploadDocBtn').addEventListener('click', () => {
                document.getElementById('planningDocFileInput').click();
            });
        }
    }

    document.getElementById('planningDocFileInput').addEventListener('change', async (ev) => {
        const file = ev.target.files[0];
        ev.target.value = '';
        if (!file || !drawerData) return;

        const formData = new FormData();
        formData.append('engagement_id', drawerData.engagement.eng_idno);
        formData.append('screenshot', file);

        try {
            const res = await fetch('../api/upload-engagement-screenshot.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                refreshDrawer();
            } else {
                Swal.fire('Error', data.message || 'Failed to upload screenshot', 'error');
            }
        } catch (err) {
            console.error('Error:', err);
            Swal.fire('Error', 'Failed to upload screenshot', 'error');
        }
    });

    // Same completeness check as PHP's getSetupInfo() (dashboard.php) — team,
    // DOL for whatever audit types actually need it, a timeline that's
    // actually been filled out, and a planning doc. Kept here client-side
    // (rather than a round-trip) since the drawer already has the team,
    // timeline, and engagement data in hand the moment it opens.
    //
    // PCI-only engagements: see the matching comment on PHP's getSetupInfo()
    // — neither timeline nor planning doc is independently required, only
    // having neither counts as missing.
    function drawerSetupStatus(team, auditTypes, timeline, hasPlanningDoc) {
        const hasTeam = team.length > 0;
        const isPciOnly = auditTypes.length === 1 && auditTypes[0] === 'PCI';
        const relevant = auditTypes.filter(t => DOL_AUDIT_TYPES.hasOwnProperty(t));
        const dolComplete = !hasTeam || relevant.every(auditType => {
            const field = DOL_AUDIT_TYPES[auditType];
            return team.some(m => !!m[field]);
        });
        const hasTimeline = !!timeline && TIMELINE_STEPS.some(step =>
            !!timeline[step.date] || (step.startDate && !!timeline[step.startDate])
        );

        const missing = [];
        if (!hasTeam) missing.push('No team');
        if (isPciOnly) {
            if (!hasTimeline && !hasPlanningDoc) missing.push('No timeline or planning doc');
        } else {
            if (!hasTimeline) missing.push('No timeline');
            if (!hasPlanningDoc) missing.push('No planning doc');
        }
        if (hasTeam && !dolComplete) missing.push('DOL incomplete');

        return { hasTeam, dolComplete, hasTimeline, hasPlanningDoc, missing, needsSetup: missing.length > 0 };
    }

    function updateDrawerSetupBanner(eng, team, auditTypes, timeline) {
        const banner = document.getElementById('drawerSetupBanner');
        if (!banner) return;
        const status = drawerSetupStatus(team, auditTypes, timeline, !!eng.eng_planning_doc);
        if (!status.needsSetup) { banner.innerHTML = ''; return; }
        banner.innerHTML = `
            <div class="drawer-setup-banner">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div><b>Onboarding incomplete:</b> ${status.missing.map(escapeHtml).join(' &middot; ')}</div>
            </div>
        `;
    }

    // One row per distinct person on the team, merging their (possibly
    // several — one per audit type they have DOL on) engagement_team rows
    // into a single record with an emp_ids array. Shared by the read-only
    // Team card below and the Planning Meeting note's independence list,
    // which both need "every unique person on this team," just laid out
    // differently.
    function groupTeamMembers(team, auditTypes) {
        const relevantAuditTypes = auditTypes.filter(t => DOL_AUDIT_TYPES.hasOwnProperty(t));
        const grouped = {};
        team.forEach(member => {
            const key = member.emp_name + '|' + member.role;
            if (!grouped[key]) {
                const dolMap = {};
                relevantAuditTypes.forEach(auditType => {
                    const field = DOL_AUDIT_TYPES[auditType];
                    if (member[field]) {
                        dolMap[auditType] = member[field].split(',').map(t => t.trim()).filter(Boolean);
                    }
                });
                grouped[key] = {
                    emp_name: member.emp_name, role: (member.role || '').toLowerCase(), audit_types: dolMap,
                    emp_ids: [], independent: member.emp_independent || null, budgeted_hours: member.budgeted_hours
                };
            }
            // Every engagement_team row for this person (one per audit type
            // they have DOL on) needs to be updated together when the
            // independence attestation is set — it's a fact about the
            // person on this engagement, not about one specific row.
            grouped[key].emp_ids.push(member.emp_id);
        });
        return Object.values(grouped);
    }

    function renderDrawerTeam(team, auditTypes) {
        const el = document.getElementById('drawerTeamContent');
        if (!team.length) {
            el.innerHTML = '<div class="drawer-team-empty">No team assigned yet.</div>';
            return;
        }

        let manager = null;
        const bucketed = { senior: [], staff: [], intern: [] };
        groupTeamMembers(team, auditTypes).forEach(m => {
            if (m.role === 'manager') { manager = manager || m; }
            else if (bucketed[m.role]) { bucketed[m.role].push(m); }
        });

        function dolLinesHtml(member) {
            const groups = Object.entries(member.audit_types).filter(([, tags]) => tags.length);
            if (!groups.length) return '<span class="drawer-no-dol">No DOL assigned</span>';
            return '<div class="drawer-dol-lines">' + groups.map(([auditType, tags]) => `
                <div class="drawer-dol-line">
                    <span class="drawer-dol-audit-label">${escapeHtml(auditType)}</span>
                    <span class="drawer-dol-chips-wrap">${sortDolTags(tags, auditType).map(t => `<span class="drawer-dol-chip ${DOL_TYPE_CLASS[auditType] || ''}">${escapeHtml(t)}</span>`).join('')}</span>
                </div>
            `).join('') + '</div>';
        }

        // Independence is an audit-conflict-of-interest attestation, per
        // person per engagement. Read-only here now — it's set for the
        // whole team in one pass from the Planning Meeting note instead of
        // a separate popup per person (see openMeetingModal('planning')
        // below). Unanswered shows a neutral outline, not a red X — an X
        // specifically means "confirmed NOT independent," not "hasn't said
        // yet."
        function independenceIconHtml(member) {
            const val = member.independent;
            const icon = val === 'Y' ? 'bi-check-circle-fill' : val === 'N' ? 'bi-x-circle-fill' : 'bi-question-circle';
            const cls = val === 'Y' ? 'yes' : val === 'N' ? 'no' : 'unset';
            const title = val === 'Y' ? 'Confirmed independent from client' : val === 'N' ? 'Confirmed NOT independent from client' : 'Independence not confirmed yet — set during a Planning Meeting note';
            return `<span class="drawer-independence-badge ${cls}" title="${title}"><i class="bi ${icon}"></i></span>`;
        }

        function hoursBadgeHtml(member) {
            const h = fmtHours(member.budgeted_hours);
            return h !== null ? `<span class="drawer-hours-badge">${h} hrs budgeted</span>` : '';
        }

        let html = '';
        if (manager) {
            html += `
                <div class="drawer-team-lead-row">
                    <div class="drawer-avatar" style="background:var(--manager)">${initials(manager.emp_name)}</div>
                    <div><div class="drawer-lead-name">${escapeHtml(manager.emp_name)}</div><div class="drawer-lead-role">Manager</div></div>
                    ${hoursBadgeHtml(manager)}
                    ${independenceIconHtml(manager)}
                </div>
            `;
        }
        [['senior', 'Senior'], ['staff', 'Staff'], ['intern', 'Intern']].forEach(([roleKey, roleLabel]) => {
            if (!bucketed[roleKey].length) return;
            html += `<div class="drawer-role-group"><div class="drawer-role-group-label">${roleLabel} (${bucketed[roleKey].length})</div>`;
            bucketed[roleKey].forEach(member => {
                html += `
                    <div class="drawer-member-row">
                        <div class="drawer-avatar" style="background:var(--${roleKey})">${initials(member.emp_name)}</div>
                        <div class="drawer-member-info">
                            <div class="drawer-member-name">${escapeHtml(member.emp_name)} ${hoursBadgeHtml(member)}</div>
                            ${dolLinesHtml(member)}
                        </div>
                        ${independenceIconHtml(member)}
                    </div>
                `;
            });
            html += '</div>';
        });

        if (!manager && !html) {
            html = '<div class="drawer-team-empty">No team assigned yet.</div>';
        }
        el.innerHTML = html;
    }

    const NOTE_TYPE_META = {
        planning: { label: 'Planning Meeting', icon: 'bi-clipboard2-check' },
        client:   { label: 'Client Planning',  icon: 'bi-camera-video' },
        weekly:   { label: 'Weekly Status',    icon: 'bi-arrow-repeat' },
        general:  { label: 'Note',             icon: 'bi-pencil' },
    };

    function renderNoteLog(notes) {
        const el = document.getElementById('drawerNoteLog');
        if (!el) return;
        if (!notes || !notes.length) {
            el.innerHTML = '<div class="note-empty">No notes logged yet.</div>';
            return;
        }
        el.innerHTML = notes.map(note => {
            const meta = NOTE_TYPE_META[note.note_type] || NOTE_TYPE_META.general;
            let indepHtml = '';
            if (note.independence_snapshot) {
                let snapshot = null;
                try { snapshot = JSON.parse(note.independence_snapshot); } catch (e) { snapshot = null; }
                if (snapshot) {
                    indepHtml = '<div class="note-indep-summary">' + Object.entries(snapshot).map(([name, val]) => {
                        const cls = val === 'Y' ? 'yes' : val === 'N' ? 'no' : '';
                        const icon = val === 'Y' ? 'bi-check-circle-fill' : val === 'N' ? 'bi-x-circle-fill' : 'bi-question-circle';
                        return `<span class="note-indep-chip ${cls}"><i class="bi ${icon}"></i> ${escapeHtml(name)}</span>`;
                    }).join('') + '</div>';
                }
            }
            return `
                <div class="note-entry t-${escAttr(note.note_type)}">
                    <div class="note-type-rail"></div>
                    <div class="note-entry-body">
                        <div class="note-entry-head">
                            <span class="note-type-label"><i class="bi ${meta.icon}"></i> ${meta.label}</span>
                            <span class="note-meta">${escapeHtml(note.author_name || 'Unknown')} &middot; ${fmtDateTime(note.created_at) || ''}</span>
                        </div>
                        <div class="note-text">${escapeHtml(note.note_text)}</div>
                        ${indepHtml}
                    </div>
                </div>
            `;
        }).join('');
    }

    // ===================================================================
    // NOTES & MEETINGS MODALS
    // Four buttons feeding one log (engagement_notes via
    // api/add-engagement-note.php) instead of the old single eng_notes
    // field and the old per-person independence popup.
    //
    // The 4 *trigger* buttons (mtgOpenPlanning etc.) live inside
    // drawerBody's innerHTML template, same as everything else in the
    // drawer — rebuilt from scratch every renderDrawer() call, which means
    // any listener attached to them only lasts until the next render.
    // wireMeetingButtons() (bottom of this block) re-attaches them fresh
    // and is called from renderDrawer() itself, same as renderNoteLog()
    // and renderDrawerTeam() already are. The 4 *modals* they open are
    // different: static markup, siblings of .drawer, never rebuilt — so
    // their own close/save buttons only need wiring once, below.
    // ===================================================================
    const MTG_CONFIG = {
        planning: { scrim: 'mtgModalPlanning', text: 'mtgPlanningText', save: 'mtgSavePlanning', savingLabel: 'Saving…', savedLabel: 'Save Planning Meeting' },
        client:   { scrim: 'mtgModalClient',   text: 'mtgClientText',   save: 'mtgSaveClient',   savingLabel: 'Saving…', savedLabel: 'Save Notes' },
        weekly:   { scrim: 'mtgModalWeekly',    text: 'mtgWeeklyText',   save: 'mtgSaveWeekly',   savingLabel: 'Saving…', savedLabel: 'Save Notes' },
        general:  { scrim: 'mtgModalGeneral',   text: 'mtgGeneralText',  save: 'mtgSaveGeneral',  savingLabel: 'Saving…', savedLabel: 'Save Note' },
    };

    // { emp_name: 'Y'|'N'|null } — current picks in the open Planning
    // Meeting modal, seeded from each member's live emp_independent and
    // edited in place via the segmented control before saving.
    let planningIndepState = {};
    let planningIndepMembers = []; // grouped team members currently shown, incl. their emp_ids — set together with planningIndepState so save doesn't have to re-derive it

    function renderPlanningIndepList() {
            const team = drawerData?.team || [];
            const auditTypes = (drawerData?.engagement?.eng_audit_type || '').split(',').map(t => t.trim()).filter(Boolean);
            const members = groupTeamMembers(team, auditTypes);
            const clientName = drawerData?.engagement?.eng_name || 'this client';
            document.getElementById('mtgIndepLabel').textContent = `Independence — confirmed independent from ${clientName}?`;

            planningIndepMembers = members;
            const list = document.getElementById('mtgIndepList');
            if (!members.length) {
                list.innerHTML = '<div class="note-empty">No team assigned yet — add a team before logging independence.</div>';
                planningIndepState = {};
                return;
            }
            planningIndepState = {};
            members.forEach(m => { planningIndepState[m.emp_name] = m.independent || null; });

            function paint() {
                list.innerHTML = members.map(m => {
                    const val = planningIndepState[m.emp_name];
                    return `
                        <div class="mtg-indep-row" data-emp-name="${escAttr(m.emp_name)}">
                            <div class="mtg-indep-avatar" style="background:var(--${m.role === 'manager' ? 'manager' : m.role})">${initials(m.emp_name)}</div>
                            <div class="mtg-indep-name-wrap">
                                <div class="mtg-indep-name">${escapeHtml(m.emp_name)}</div>
                                <div class="mtg-indep-role">${ROLE_LABELS[m.role] || m.role}</div>
                            </div>
                            <div class="mtg-indep-segmented">
                                <button type="button" data-val="Y" class="${val === 'Y' ? 'active yes' : ''}">Yes</button>
                                <button type="button" data-val="N" class="${val === 'N' ? 'active no' : ''}">No</button>
                                <button type="button" data-val="" class="${!val ? 'active unset' : ''}">&mdash;</button>
                            </div>
                        </div>
                    `;
                }).join('');
                list.querySelectorAll('.mtg-indep-row').forEach(row => {
                    const empName = row.dataset.empName;
                    row.querySelectorAll('.mtg-indep-segmented button').forEach(btn => {
                        btn.addEventListener('click', () => {
                            planningIndepState[empName] = btn.dataset.val || null;
                            paint();
                        });
                    });
                });
            }
            paint();
        }

        function openMeetingModal(type) {
            if (type === 'planning') renderPlanningIndepList();
            document.getElementById(MTG_CONFIG[type].scrim).classList.add('open');
        }
        function closeMeetingModal(scrimEl) {
            scrimEl.classList.remove('open');
        }

        // The 4 trigger buttons live inside drawerBody's innerHTML — fresh
        // elements every renderDrawer() call — so this has to run again
        // each time too, unlike the modal wiring below it (called once,
        // since the modals themselves are static markup that never gets
        // rebuilt).
        function wireMeetingButtons() {
            document.getElementById('mtgOpenPlanning')?.addEventListener('click', () => openMeetingModal('planning'));
            document.getElementById('mtgOpenClient')?.addEventListener('click', () => openMeetingModal('client'));
            document.getElementById('mtgOpenWeekly')?.addEventListener('click', () => openMeetingModal('weekly'));
            document.getElementById('mtgOpenGeneral')?.addEventListener('click', () => openMeetingModal('general'));
        }

        document.querySelectorAll('.mtg-modal-scrim').forEach(scrimEl => {
            scrimEl.addEventListener('click', (ev) => { if (ev.target === scrimEl) closeMeetingModal(scrimEl); });
            scrimEl.querySelectorAll('[data-mtg-close]').forEach(btn => btn.addEventListener('click', () => closeMeetingModal(scrimEl)));
        });
        window.addEventListener('keydown', (ev) => {
            if (ev.key !== 'Escape') return;
            document.querySelectorAll('.mtg-modal-scrim.open').forEach(closeMeetingModal);
        });

        function saveMeetingNote(type) {
            const cfg = MTG_CONFIG[type];
            const textEl = document.getElementById(cfg.text);
            const text = textEl.value.trim();
            if (!text) { textEl.style.borderColor = 'var(--critical)'; textEl.focus(); return; }
            textEl.style.borderColor = '';

            const saveBtn = document.getElementById(cfg.save);
            saveBtn.disabled = true;
            saveBtn.textContent = cfg.savingLabel;

            const payload = {
                engagement_idno: drawerData.engagement.eng_idno,
                note_type: type,
                note_text: text,
            };
            if (type === 'planning') {
                payload.independence = planningIndepMembers.map(m => ({
                    emp_ids: m.emp_ids,
                    emp_name: m.emp_name,
                    value: planningIndepState[m.emp_name] || null,
                }));
            }

            fetch('../api/add-engagement-note.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(data => {
                saveBtn.disabled = false;
                saveBtn.textContent = cfg.savedLabel;
                if (!data.success) {
                    Swal.fire('Error', data.message || 'Failed to save', 'error');
                    return;
                }
                textEl.value = '';
                closeMeetingModal(document.getElementById(cfg.scrim));
                refreshDrawer();
            })
            .catch(error => {
                saveBtn.disabled = false;
                saveBtn.textContent = cfg.savedLabel;
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to save: ' + error.message, 'error');
            });
        }

        document.getElementById('mtgSavePlanning')?.addEventListener('click', () => saveMeetingNote('planning'));
        document.getElementById('mtgSaveClient')?.addEventListener('click', () => saveMeetingNote('client'));
        document.getElementById('mtgSaveWeekly')?.addEventListener('click', () => saveMeetingNote('weekly'));
        document.getElementById('mtgSaveGeneral')?.addEventListener('click', () => saveMeetingNote('general'));

    function renderDrawerTimeline(timeline, engagementId) {
        const el = document.getElementById('drawerTimelineContent');
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        el.innerHTML = TIMELINE_STEPS.map(step => {
            const rawDate = timeline[step.date];
            const completedAt = timeline[step.completed];
            let dotClass = '';
            let dateClass = '';
            let dateLabel = 'Not set';

            if (rawDate) {
                dateLabel = fmtDate(rawDate) || 'Not set';
                // Steps with a startDate (currently the two fieldwork phases)
                // show as a range once both ends are on file — the end date
                // still drives overdue/complete, the start is display-only.
                if (step.startDate) {
                    const startLabel = fmtDate(timeline[step.startDate]);
                    if (startLabel) dateLabel = `${startLabel} - ${dateLabel}`;
                }
                const due = new Date(rawDate + 'T00:00:00');
                if (completedAt) {
                    dotClass = 'done';
                } else if (due < today) {
                    dotClass = 'overdue';
                    dateClass = 'overdue';
                }
            } else {
                dateClass = 'empty';
            }

            return `
                <div class="drawer-tl-item" data-date-field="${step.date}" data-completed-field="${step.completed}" data-checked="${completedAt ? '1' : '0'}" title="Click to mark ${completedAt ? 'incomplete' : 'complete'}">
                    <span class="drawer-tl-dot ${dotClass}"></span>
                    <div class="drawer-tl-info">
                        <div class="drawer-tl-label">${escapeHtml(step.label)}</div>
                        <div class="drawer-tl-date ${dateClass}">${escapeHtml(dateLabel)}</div>
                    </div>
                </div>
            `;
        }).join('');

        el.querySelectorAll('.drawer-tl-item').forEach(item => {
            item.addEventListener('click', async () => {
                const dateField = item.dataset.dateField;
                const completedField = item.dataset.completedField;
                const isChecked = item.dataset.checked === '1';
                const completedDateTime = !isChecked ? new Date().toISOString().slice(0, 19).replace('T', ' ') : null;
                try {
                    const response = await fetch('../api/update-timeline-checkbox.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            engagement_id: engagementId,
                            date_field: dateField,
                            completed_field: completedField,
                            completed_datetime: completedDateTime
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        // Reload (rather than just refreshing the drawer in
                        // place) because completing a date — especially the
                        // final report — can change the row's due state,
                        // highlighting, and sort position in the list behind
                        // the drawer, same reasoning as the status popover.
                        reopenDrawerAfterReload(engagementId);
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update timeline', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to update timeline', 'error');
                }
            });
        });
    }

    // ---------- Manage Team modal — split workspace (roster + detail panel) ----------
    // Redesigned from the old two-column-list-plus-Edit-modal flow (mocked up
    // as a Claude Artifact first, per Garrett — "B: Split Workspace" was the
    // direction picked). Master/detail like the dashboard list + drawer
    // itself: the roster stays visible on the left at all times, and
    // selecting someone (or hitting "Add Team Member") opens their full
    // editor on the right, in place — no nested Swal for Edit, no nested
    // Swal for the delete confirm (a second Swal.fire() while this one is
    // open replaces the shared popup instance and closes this one, same
    // issue the old new-employee role picker had to work around). Add/Edit/
    // Remove all patch currentTeam and re-render in place rather than
    // reloading — only closing the modal itself reloads, to refresh the
    // drawer behind it.
    function openManageTeamModal() {
        const engagementId = drawerData.engagement.eng_idno;
        let currentTeam = (drawerData.team || []).slice();
        const auditTypesArray = (drawerData.engagement.eng_audit_type || '').split(',').map(t => t.trim()).filter(Boolean);
        const relevantAuditTypes = auditTypesArray.filter(type => DOL_AUDIT_TYPES.hasOwnProperty(type));
        const roleOrder = ['manager', 'senior', 'staff', 'intern'];

        let selectedEmpId = null;
        let mode = 'detail'; // 'detail' (right pane bound to selectedEmpId) | 'add' (right pane is the search form)
        let confirmingRemoveId = null;
        let filterQuery = '';

        const bodyHTML = `
            <div class="team3-body">
                <div class="team3-left">
                    <div class="team3-left-head">
                        <input type="text" id="team3_filter" placeholder="Filter roster…" autocomplete="off">
                        <button type="button" class="team3-add-btn" id="team3_add_btn"><i class="bi bi-plus"></i> Add Team Member</button>
                    </div>
                    <div class="team3-list" id="team3_list"></div>
                </div>
                <div class="team3-right" id="team3_right"></div>
            </div>
        `;

        Swal.fire({
            title: 'Manage Team Members',
            html: bodyHTML,
            showConfirmButton: false,
            width: '980px',
            heightAuto: false,
            customClass: { popup: 'team3-modal-popup' },
            didOpen: () => {
                renderList();
                renderRight();
                document.getElementById('team3_add_btn').addEventListener('click', () => {
                    mode = 'add';
                    selectedEmpId = null;
                    confirmingRemoveId = null;
                    renderList();
                    renderRight();
                });
                document.getElementById('team3_filter').addEventListener('input', (e) => {
                    filterQuery = e.target.value.trim().toLowerCase();
                    renderList();
                });
            },
            willClose: () => {
                reopenDrawerAfterReload(engagementId);
                location.reload();
            }
        });

        function renderList() {
            const listEl = document.getElementById('team3_list');
            if (!listEl) return;

            if (currentTeam.length === 0) {
                listEl.innerHTML = `<div class="team3-empty-list">No team members yet.<br>Add someone to get started.</div>`;
                return;
            }

            const visible = currentTeam.filter(m => !filterQuery || m.emp_name.toLowerCase().includes(filterQuery));
            if (visible.length === 0) {
                listEl.innerHTML = `<div class="team3-empty-list">No one matches "${escapeHtml(filterQuery)}".</div>`;
                return;
            }

            const sortedTeam = visible.slice().sort((a, b) => {
                const ra = roleOrder.indexOf((a.role || '').toLowerCase());
                const rb = roleOrder.indexOf((b.role || '').toLowerCase());
                return (ra === -1 ? 99 : ra) - (rb === -1 ? 99 : rb);
            });

            listEl.innerHTML = sortedTeam.map(member => renderListItem(member)).join('');

            listEl.querySelectorAll('[data-select]').forEach(el => {
                el.addEventListener('click', () => {
                    selectedEmpId = el.dataset.select;
                    mode = 'detail';
                    confirmingRemoveId = null;
                    renderList();
                    renderRight();
                });
            });
        }

        function renderListItem(member) {
            const roleKey = (member.role || '').toLowerCase();
            const isSelected = mode === 'detail' && String(selectedEmpId) === String(member.emp_id);
            const dolChips = roleKey === 'manager' ? '' : relevantAuditTypes.map(auditType => {
                const fieldName = DOL_AUDIT_TYPES[auditType];
                const duties = (member[fieldName] || '').split(',').map(d => d.trim()).filter(Boolean);
                const typeClass = DOL_TYPE_CLASS[auditType] || '';
                return sortDolTags(duties, auditType).map(d => `<span class="team2-chip ${typeClass}">${escapeHtml(d)}</span>`).join('');
            }).join('');

            return `
                <div class="team3-item ${isSelected ? 'selected' : ''}" data-select="${member.emp_id}">
                    <div class="team2-avatar" style="width:30px;height:30px;font-size:11px;background:${ROLE_COLOR_VAR[roleKey] || 'var(--ink)'}">${escapeHtml(initials(member.emp_name))}</div>
                    <div class="team3-item-info">
                        <div class="team3-item-name">${escapeHtml(member.emp_name)}</div>
                        <div class="team3-item-sub">${ROLE_LABELS[roleKey] || member.role}${fmtHours(member.budgeted_hours) !== null ? ` &middot; ${fmtHours(member.budgeted_hours)} hrs` : ''}</div>
                        ${dolChips ? `<div class="team3-item-dols">${dolChips}</div>` : ''}
                    </div>
                </div>
            `;
        }

        function renderRight() {
            const right = document.getElementById('team3_right');
            if (!right) return;

            if (mode === 'add') {
                right.innerHTML = `
                    <div class="team3-add-panel">
                        <h3 style="font-size:14px; font-weight:700; margin:0 0 1rem; color:var(--text-primary);">Add someone to this engagement</h3>
                        <div class="team2-field">
                            <input type="text" id="team3_add_search" class="swal2-input" placeholder="Search employees…" autocomplete="off" style="margin:0; width:100%; font-size:13px;">
                        </div>
                        <div id="team3_add_results"></div>
                    </div>
                `;
                wireAddPanel();
                return;
            }

            const member = currentTeam.find(m => String(m.emp_id) === String(selectedEmpId));
            if (!member) {
                right.innerHTML = `
                    <div class="team3-right-empty">
                        <i class="bi bi-people"></i>
                        <div>Select a team member on the left,<br>or add someone new to this engagement.</div>
                    </div>
                `;
                return;
            }
            right.innerHTML = renderDetailPanel(member);
            wireDetailPanel(member);
        }

        function renderDetailPanel(member) {
            const roleKey = (member.role || '').toLowerCase();
            const dolColumnsHtml = relevantAuditTypes.map(auditType => `
                <div>
                    <div class="team2-dol-col-label">${auditType}</div>
                    <div class="team2-tag-input-box" data-field="${DOL_AUDIT_TYPES[auditType]}" data-audit-type="${escAttr(auditType)}">
                        <div class="tags"></div>
                        <input type="text" placeholder="Add duty…">
                    </div>
                </div>
            `).join('');
            const isConfirmingRemove = confirmingRemoveId !== null && String(confirmingRemoveId) === String(member.emp_id);

            return `
                <div class="team3-detail-head">
                    <div class="team2-avatar-lg" id="team3_detail_avatar" style="background:${ROLE_COLOR_VAR[roleKey] || 'var(--ink)'}">${escapeHtml(initials(member.emp_name))}</div>
                    <div>
                        <div class="team2-edit-name">${escapeHtml(member.emp_name)}</div>
                        <div class="team2-edit-role-badge ${roleKey}" id="team3_detail_role_badge">${ROLE_LABELS[roleKey] || member.role}</div>
                    </div>
                </div>
                <div class="team3-field">
                    <label class="team2-edit-label">Role on this engagement</label>
                    <div class="team2-segmented" id="team3_role_segment">
                        ${roleOrder.map(role =>
                            `<button type="button" data-role="${role}" class="${role === roleKey ? 'active' : ''}">${ROLE_LABELS[role]}</button>`
                        ).join('')}
                    </div>
                </div>
                <div class="team3-field hours-field">
                    <label class="team2-edit-label">Budgeted Hours</label>
                    <input type="number" id="team3_hours_input" class="swal2-input" min="0" step="0.5"
                           placeholder="Not set" value="${fmtHours(member.budgeted_hours) ?? ''}"
                           style="margin: 0; width: 140px; font-size: 13px;">
                </div>
                <div class="team3-field" id="team3_dol_section" style="display:${roleKey === 'manager' ? 'none' : 'block'}; max-width:640px;">
                    <label class="team2-edit-label">Duties &amp; Responsibilities</label>
                    <div class="team3-dol-columns">${dolColumnsHtml}</div>
                    <div class="team2-tag-hint">e.g. CC1, CC2 — press Enter or comma to add a duty</div>
                </div>
                <div class="team3-detail-actions">
                    ${isConfirmingRemove ? `
                        <div class="team3-remove-confirm">
                            Remove <b>${escapeHtml(member.emp_name)}</b> from this engagement?
                            <button type="button" class="team2-btn team2-btn-secondary" id="team3_cancel_remove" style="padding:5px 10px;">Cancel</button>
                            <button type="button" class="team2-btn" id="team3_confirm_remove" style="padding:5px 10px; background:var(--critical); color:#fff;">Remove</button>
                        </div>
                    ` : `<button type="button" class="team2-btn team2-btn-secondary" id="team3_remove_btn" style="color:var(--critical); border-color:var(--critical);">Remove from engagement</button>`}
                    <span style="display:flex; align-items:center; gap:10px;">
                        <span class="team3-saved-flash" id="team3_saved_flash"><i class="bi bi-check-circle-fill"></i> Saved</span>
                        <button type="button" class="team2-btn team2-btn-primary" id="team3_save_btn">Save Changes</button>
                    </span>
                </div>
            `;
        }

        function wireDetailPanel(member) {
            let selectedRole = (member.role || '').toLowerCase();
            const tagState = {};
            relevantAuditTypes.forEach(auditType => {
                const fieldName = DOL_AUDIT_TYPES[auditType];
                tagState[fieldName] = (member[fieldName] || '').split(',').map(t => t.trim()).filter(Boolean);
            });

            document.querySelectorAll('#team3_right .team2-tag-input-box').forEach(box => {
                const fieldName = box.dataset.field;
                const auditType = box.dataset.auditType;
                const tagsEl = box.querySelector('.tags');
                const input = box.querySelector('input');

                // Re-sorts into canonical order every render (not just on
                // add) so removing a tag can't leave a stale order behind
                // either — reassigns tagState itself, not just a display
                // copy, so the delete button's index-based splice below
                // stays correct.
                function render() {
                    tagState[fieldName] = sortDolTags(tagState[fieldName], auditType);
                    tagsEl.innerHTML = tagState[fieldName].map((t, i) =>
                        `<span class="team2-tag-chip">${escapeHtml(t)}<button type="button" data-i="${i}">&times;</button></span>`
                    ).join('');
                    tagsEl.querySelectorAll('button').forEach(btn => {
                        btn.addEventListener('click', (ev) => {
                            ev.stopPropagation();
                            tagState[fieldName].splice(Number(btn.dataset.i), 1);
                            render();
                        });
                    });
                }
                render();
                box.addEventListener('click', () => input.focus());
                input.addEventListener('keydown', (ev) => {
                    if (ev.key === 'Enter' || ev.key === ',') {
                        ev.preventDefault();
                        const val = input.value.trim().replace(/,$/, '');
                        if (val && !tagState[fieldName].includes(val)) {
                            tagState[fieldName].push(val);
                            render();
                        }
                        input.value = '';
                    } else if (ev.key === 'Backspace' && !input.value && tagState[fieldName].length) {
                        tagState[fieldName].pop();
                        render();
                    }
                });
            });

            document.querySelectorAll('#team3_role_segment button').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('#team3_role_segment button').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    selectedRole = btn.dataset.role;
                    document.getElementById('team3_detail_avatar').style.background = ROLE_COLOR_VAR[selectedRole] || 'var(--ink)';
                    const badge = document.getElementById('team3_detail_role_badge');
                    badge.className = 'team2-edit-role-badge ' + selectedRole;
                    badge.textContent = ROLE_LABELS[selectedRole];
                    document.getElementById('team3_dol_section').style.display = selectedRole === 'manager' ? 'none' : 'block';
                });
            });

            document.getElementById('team3_remove_btn')?.addEventListener('click', () => {
                confirmingRemoveId = member.emp_id;
                renderRight();
            });
            document.getElementById('team3_cancel_remove')?.addEventListener('click', () => {
                confirmingRemoveId = null;
                renderRight();
            });
            document.getElementById('team3_confirm_remove')?.addEventListener('click', () => {
                fetch('../api/delete-team-member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ engagement_idno: engagementId, emp_id: member.emp_id })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentTeam = currentTeam.filter(m => String(m.emp_id) !== String(member.emp_id));
                        confirmingRemoveId = null;
                        selectedEmpId = null;
                        renderList();
                        renderRight();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to remove team member', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to remove team member: ' + error.message, 'error');
                });
            });

            document.getElementById('team3_save_btn').addEventListener('click', () => {
                const saveBtn = document.getElementById('team3_save_btn');
                const updateData = {
                    engagement_idno: engagementId,
                    emp_id: member.emp_id,
                    emp_name: member.emp_name,
                    role: selectedRole,
                    budgeted_hours: document.getElementById('team3_hours_input').value
                };
                relevantAuditTypes.forEach(auditType => {
                    const fieldName = DOL_AUDIT_TYPES[auditType];
                    updateData[fieldName] = selectedRole === 'manager' ? '' : tagState[fieldName].join(', ');
                });

                saveBtn.disabled = true;
                saveBtn.textContent = 'Saving…';
                fetch('../api/update-team-member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updateData)
                })
                .then(response => response.json())
                .then(data => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Changes';
                    if (data.success) {
                        const idx = currentTeam.findIndex(m => String(m.emp_id) === String(member.emp_id));
                        if (idx !== -1) currentTeam[idx] = Object.assign({}, currentTeam[idx], data.member);
                        renderList();
                        const flash = document.getElementById('team3_saved_flash');
                        if (flash) {
                            flash.classList.add('show');
                            setTimeout(() => flash.classList.remove('show'), 1800);
                        }
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update team member', 'error');
                    }
                })
                .catch(error => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Save Changes';
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to update team member: ' + error.message, 'error');
                });
            });
        }

        function wireAddPanel() {
            const input = document.getElementById('team3_add_search');
            const results = document.getElementById('team3_add_results');
            if (!input || !results) return;
            input.focus();

            let debounceTimer = null;
            input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const query = input.value.trim();
                if (!query) { results.innerHTML = ''; return; }
                debounceTimer = setTimeout(() => searchEmployees(query), 200);
            });

            function searchEmployees(query) {
                fetch('../api/search-employees.php?q=' + encodeURIComponent(query))
                    .then(r => r.json())
                    .then(data => {
                        const existingNames = currentTeam.map(m => m.emp_name.toLowerCase());
                        const allMatches = data.employees || [];
                        const matches = allMatches.filter(e => !existingNames.includes(e.emp_name.toLowerCase()));
                        const onTeamAlready = allMatches.filter(e => existingNames.includes(e.emp_name.toLowerCase()));
                        renderResults(query, matches, onTeamAlready);
                    })
                    .catch(() => renderResults(query, [], []));
            }

            function renderResults(query, matches, onTeamAlready) {
                let html = '';
                if (matches.length) {
                    html += `<div class="team3-search-results">` + matches.map(e => `
                        <div class="team2-ac-item" data-emp-name="${escAttr(e.emp_name)}" data-emp-role="${escAttr(e.emp_role)}">
                            <div class="team2-avatar" style="width:22px;height:22px;font-size:9px;background:${ROLE_COLOR_VAR[e.emp_role] || 'var(--ink)'}">${escapeHtml(initials(e.emp_name))}</div>
                            ${escapeHtml(e.emp_name)}
                            <span class="role">${ROLE_LABELS[e.emp_role] || e.emp_role}</span>
                        </div>
                    `).join('') + `</div>`;
                } else if (onTeamAlready.length) {
                    html += `<div class="team2-ac-empty">${escapeHtml(onTeamAlready.map(e => e.emp_name).join(', '))} — already on this team.</div>`;
                } else {
                    html += `<div class="team2-ac-empty">No employee named "${escapeHtml(query)}" in the roster.</div>`;
                }
                if (!matches.length && !onTeamAlready.length) {
                    html += `<div class="team2-ac-newbtn" id="team3_ac_new_btn">+ Add "${escapeHtml(query)}" as a new employee…</div>`;
                }

                results.innerHTML = html;

                results.querySelectorAll('.team2-ac-item').forEach(item => {
                    item.addEventListener('click', () => {
                        addTeamMember(item.dataset.empName, item.dataset.empRole);
                    });
                });
                document.getElementById('team3_ac_new_btn')?.addEventListener('click', () => {
                    renderNewEmployeeRolePicker(query);
                });
            }

            // Renders in the same results area rather than a nested Swal —
            // a second Swal.fire() while "Manage Team Members" is open
            // replaces the shared popup instance, which would close this
            // whole modal and reload the page.
            function renderNewEmployeeRolePicker(name) {
                let selectedRole = 'staff';
                results.innerHTML = `
                    <div class="team2-new-emp-picker" style="padding:0;">
                        <div class="team2-ac-empty" style="padding:0 0 8px;">
                            Role for "<strong style="color:var(--text-primary);">${escapeHtml(name)}</strong>"?
                        </div>
                        <div class="role-pick-grid" id="team3_new_emp_role_segment">
                            ${roleOrder.map(role =>
                                `<button type="button" data-role="${role}" class="role-pick-btn ${role === selectedRole ? 'active' : ''}">${ROLE_LABELS[role]}</button>`
                            ).join('')}
                        </div>
                        <div class="team2-new-emp-actions">
                            <button type="button" class="team2-btn team2-btn-secondary" id="team3_new_emp_cancel">Cancel</button>
                            <button type="button" class="team2-btn team2-btn-primary" id="team3_new_emp_confirm">Add Employee</button>
                        </div>
                    </div>
                `;

                document.querySelectorAll('#team3_new_emp_role_segment button').forEach(btn => {
                    btn.addEventListener('click', () => {
                        document.querySelectorAll('#team3_new_emp_role_segment button').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        selectedRole = btn.dataset.role;
                    });
                });
                document.getElementById('team3_new_emp_cancel').addEventListener('click', () => {
                    input.value = '';
                    results.innerHTML = '';
                });
                document.getElementById('team3_new_emp_confirm').addEventListener('click', () => {
                    createEmployeeThenAdd(name, selectedRole);
                });
            }

            function createEmployeeThenAdd(name, role) {
                fetch('../api/add-employee.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ emp_name: name, emp_role: role })
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            addTeamMember(name, role);
                        } else {
                            Swal.fire('Error', data.message || 'Failed to add employee', 'error');
                        }
                    })
                    .catch(error => Swal.fire('Error', 'Failed to add employee: ' + error.message, 'error'));
            }
        }

        function addTeamMember(empName, empRole) {
            fetch('../api/add-team-member.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ engagement_idno: engagementId, emp_name: empName, role: empRole })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentTeam.push(data.member);
                    selectedEmpId = data.member.emp_id;
                    mode = 'detail';
                    confirmingRemoveId = null;
                    renderList();
                    renderRight();
                } else {
                    Swal.fire('Error', data.message || 'Failed to add team member', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to add team member: ' + error.message, 'error');
            });
        }
    }

    // ---------- Edit Timeline modal (ported from engagement-details.php) ----------
    function openEditTimelineModal(overrides, unmatched) {
        const engagementId = drawerData.engagement.eng_idno;
        const timeline = drawerData.timeline || {};
        const timelineData = {
            internal_planning_call_date:   (overrides && overrides.internal_planning_call_date) || timeline.internal_planning_call_date || '',
            planning_memo_date:            (overrides && overrides.planning_memo_date) || timeline.planning_memo_date || '',
            irl_due_date:                  (overrides && overrides.irl_due_date) || timeline.irl_due_date || '',
            client_planning_call_date:     (overrides && overrides.client_planning_call_date) || timeline.client_planning_call_date || '',
            fieldwork_client_calls_start_date:   (overrides && overrides.fieldwork_client_calls_start_date) || timeline.fieldwork_client_calls_start_date || '',
            fieldwork_client_calls_end_date:     (overrides && overrides.fieldwork_client_calls_end_date) || timeline.fieldwork_client_calls_end_date || '',
            fieldwork_documentation_start_date:  (overrides && overrides.fieldwork_documentation_start_date) || timeline.fieldwork_documentation_start_date || '',
            fieldwork_documentation_end_date:    (overrides && overrides.fieldwork_documentation_end_date) || timeline.fieldwork_documentation_end_date || '',
            leadsheet_date:                (overrides && overrides.leadsheet_date) || timeline.leadsheet_date || '',
            conclusion_memo_date:          (overrides && overrides.conclusion_memo_date) || timeline.conclusion_memo_date || '',
            draft_report_due_date:         (overrides && overrides.draft_report_due_date) || timeline.draft_report_due_date || '',
            final_report_date:             (overrides && overrides.final_report_date) || timeline.final_report_date || '',
            archive_date:                  (overrides && overrides.archive_date) || timeline.archive_date || ''
        };
        const importedCount = overrides ? Object.keys(overrides).length : 0;
        const importBanner = overrides
            ? `<div style="margin-bottom:1rem; padding:0.75rem 0.9rem; background:color-mix(in srgb, var(--primary-blue) 10%, transparent); border-left:3px solid var(--primary-blue); border-radius:6px; font-size:12.5px; color:var(--text-primary);">
                   ${importedCount ? `Filled ${importedCount} date${importedCount === 1 ? '' : 's'} from your spreadsheet` : 'Could not match any task names from your spreadsheet'}.
                   ${unmatched && unmatched.length ? `<br>Couldn't find a match for: ${unmatched.map(escapeHtml).join(', ')}.` : ''}
                   Review before saving.
               </div>`
            : '';

        Swal.fire({
            title: 'Edit Timeline & Key Dates',
            html: `
                <div style="text-align: left; margin: 1rem 0;">
                ${importBanner}
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; width: 100%; box-sizing: border-box;">
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Internal Planning Call</label>
                        <input type="date" id="internal_planning_call_date" class="swal2-input" value="${timelineData.internal_planning_call_date}">
                    </div>
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Planning Memo</label>
                        <input type="date" id="planning_memo_date" class="swal2-input" value="${timelineData.planning_memo_date}">
                    </div>
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">IRL Due</label>
                        <input type="date" id="irl_due_date" class="swal2-input" value="${timelineData.irl_due_date}">
                    </div>
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Client Planning Call</label>
                        <input type="date" id="client_planning_call_date" class="swal2-input" value="${timelineData.client_planning_call_date}">
                    </div>
                    <div style="grid-column: span 2; width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Fieldwork - Client Calls</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;">
                            <div>
                                <label style="display: block; margin-bottom: 0.25rem; font-size: 9px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px;">Start</label>
                                <input type="date" id="fieldwork_client_calls_start_date" class="swal2-input" value="${timelineData.fieldwork_client_calls_start_date}">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.25rem; font-size: 9px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px;">End</label>
                                <input type="date" id="fieldwork_client_calls_end_date" class="swal2-input" value="${timelineData.fieldwork_client_calls_end_date}">
                            </div>
                        </div>
                    </div>
                    <div style="grid-column: span 2; width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Fieldwork - Documentation</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.6rem;">
                            <div>
                                <label style="display: block; margin-bottom: 0.25rem; font-size: 9px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px;">Start</label>
                                <input type="date" id="fieldwork_documentation_start_date" class="swal2-input" value="${timelineData.fieldwork_documentation_start_date}">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.25rem; font-size: 9px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.4px;">End</label>
                                <input type="date" id="fieldwork_documentation_end_date" class="swal2-input" value="${timelineData.fieldwork_documentation_end_date}">
                            </div>
                        </div>
                    </div>
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Leadsheet Due</label>
                        <input type="date" id="leadsheet_date" class="swal2-input" value="${timelineData.leadsheet_date}">
                    </div>
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Conclusion Memo</label>
                        <input type="date" id="conclusion_memo_date" class="swal2-input" value="${timelineData.conclusion_memo_date}">
                    </div>
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Draft Report Due</label>
                        <input type="date" id="draft_report_due_date" class="swal2-input" value="${timelineData.draft_report_due_date}">
                    </div>
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Final Report Due</label>
                        <input type="date" id="final_report_date" class="swal2-input" value="${timelineData.final_report_date}">
                    </div>
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Archive Date</label>
                        <input type="date" id="archive_date" class="swal2-input" value="${timelineData.archive_date}">
                    </div>
                </div>
                </div>
            `,
            confirmButtonText: 'Save Changes',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            didOpen: () => {
                document.getElementById('internal_planning_call_date').focus();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const updatedData = {
                    engagement_id:               engagementId,
                    internal_planning_call_date: document.getElementById('internal_planning_call_date').value,
                    planning_memo_date:          document.getElementById('planning_memo_date').value,
                    irl_due_date:                document.getElementById('irl_due_date').value,
                    client_planning_call_date:   document.getElementById('client_planning_call_date').value,
                    fieldwork_client_calls_start_date:   document.getElementById('fieldwork_client_calls_start_date').value,
                    fieldwork_client_calls_end_date:     document.getElementById('fieldwork_client_calls_end_date').value,
                    fieldwork_documentation_start_date:  document.getElementById('fieldwork_documentation_start_date').value,
                    fieldwork_documentation_end_date:    document.getElementById('fieldwork_documentation_end_date').value,
                    leadsheet_date:              document.getElementById('leadsheet_date').value,
                    conclusion_memo_date:        document.getElementById('conclusion_memo_date').value,
                    draft_report_due_date:       document.getElementById('draft_report_due_date').value,
                    final_report_date:           document.getElementById('final_report_date').value,
                    archive_date:                document.getElementById('archive_date').value
                };

                fetch('../api/update-timeline.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updatedData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reopenDrawerAfterReload(engagementId);
                        sessionStorage.setItem('showTimelineToast', 'true');
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update timeline', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to update timeline', 'error');
                });
            }
        });
    }

    // ---------- Edit Engagement modal (ported from engagement-details.php) ----------
    function openEditEngagementModal() {
        const eng = drawerData.engagement;
        const engagementId = eng.eng_idno;
        const engagementData = {
            eng_name:         eng.eng_name || '',
            eng_location:     eng.eng_location || '',
            eng_poc:          eng.eng_poc || '',
            eng_status:       eng.eng_status || '',
            eng_tsc:          eng.eng_tsc || '',
            eng_audit_type:   eng.eng_audit_type || '',
            eng_soc_type:     eng.eng_soc_type || '',
            eng_scope:        eng.eng_scope || '',
            eng_as_of_date:   eng.eng_as_of_date || '',
            eng_start_period: eng.eng_start_period || '',
            eng_end_period:   eng.eng_end_period || '',
            eng_repeat:       eng.eng_repeat || '',
            eng_notes:        eng.eng_notes || ''
        };

        const htmlContent = `
            <div style="text-align: left; max-height: 600px; overflow-y: auto; padding: 1rem;">
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Basic Information</h3>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Engagement Name</label>
                        <input type="text" id="edit_eng_name" class="swal2-input" value="${escAttr(engagementData.eng_name)}" style="width: 100%;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Location</label>
                        <input type="text" id="edit_eng_location" class="swal2-input" value="${escAttr(engagementData.eng_location)}" style="width: 100%;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Point of Contact</label>
                        <input type="text" id="edit_eng_poc" class="swal2-input" value="${escAttr(engagementData.eng_poc)}" style="width: 100%;">
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Status</label>
                        <select id="edit_eng_status" class="swal2-input" style="width: 100%; padding: 0.6rem;">
                            <option value="planning"    ${engagementData.eng_status === 'planning'    ? 'selected' : ''}>Planning</option>
                            <option value="in-progress" ${engagementData.eng_status === 'in-progress' ? 'selected' : ''}>In Progress</option>
                            <option value="in-review"   ${engagementData.eng_status === 'in-review'   ? 'selected' : ''}>In Review</option>
                            <option value="complete"    ${engagementData.eng_status === 'complete'    ? 'selected' : ''}>Complete</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Trusted Service Criteria</label>
                        <input type="text" id="edit_eng_tsc" class="swal2-input" value="${escAttr(engagementData.eng_tsc)}" style="width: 100%;">
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Audit Details</h3>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Audit Types (Select all that apply)</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="audit-type-checkbox" value="SOC 1" ${engagementData.eng_audit_type.includes('SOC 1') ? 'checked' : ''}>
                                <span style="font-size: 13px; color: var(--text-primary);">SOC 1</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="audit-type-checkbox" value="SOC 2" ${engagementData.eng_audit_type.includes('SOC 2') ? 'checked' : ''}>
                                <span style="font-size: 13px; color: var(--text-primary);">SOC 2</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="audit-type-checkbox" value="PCI" ${engagementData.eng_audit_type.includes('PCI') ? 'checked' : ''}>
                                <span style="font-size: 13px; color: var(--text-primary);">PCI</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="audit-type-checkbox" value="HITRUST" ${engagementData.eng_audit_type.includes('HITRUST') ? 'checked' : ''}>
                                <span style="font-size: 13px; color: var(--text-primary);">HITRUST</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="audit-type-checkbox" value="FISMA" ${engagementData.eng_audit_type.includes('FISMA') ? 'checked' : ''}>
                                <span style="font-size: 13px; color: var(--text-primary);">FISMA</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="audit-type-checkbox" value="ISO" ${engagementData.eng_audit_type.includes('ISO') ? 'checked' : ''}>
                                <span style="font-size: 13px; color: var(--text-primary);">ISO</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="audit-type-checkbox" value="HIPAA" ${engagementData.eng_audit_type.includes('HIPAA') ? 'checked' : ''}>
                                <span style="font-size: 13px; color: var(--text-primary);">HIPAA</span>
                            </label>
                        </div>
                    </div>

                    <div id="soc_type_section" style="margin-bottom: 1.5rem; display: none; padding: 1rem; background: color-mix(in srgb, var(--primary-blue) 10%, transparent); border-radius: 8px; border-left: 3px solid var(--primary-blue);">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">SOC Audit Type</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="radio" name="soc_type" value="Type 1" ${engagementData.eng_soc_type === 'Type 1' ? 'checked' : ''}>
                                <span style="font-size: 13px; color: var(--text-primary);">Type 1</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                <input type="radio" name="soc_type" value="Type 2" ${engagementData.eng_soc_type === 'Type 2' ? 'checked' : ''}>
                                <span style="font-size: 13px; color: var(--text-primary);">Type 2</span>
                            </label>
                        </div>
                        <div id="soc_type1_dates" style="display: none;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">As Of Date</label>
                            <input type="date" id="edit_soc_as_of_date" class="swal2-input" value="${engagementData.eng_as_of_date}" style="width: 100%;">
                        </div>
                        <div id="soc_type2_dates" style="display: none;">
                            <div style="margin-bottom: 0.75rem;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Start Period</label>
                                <input type="date" id="edit_soc_start_period" class="swal2-input" value="${engagementData.eng_start_period}" style="width: 100%;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">End Period</label>
                                <input type="date" id="edit_soc_end_period" class="swal2-input" value="${engagementData.eng_end_period}" style="width: 100%;">
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Scope</label>
                        <textarea id="edit_eng_scope" class="swal2-input" style="width: 100%; min-height: 80px; resize: vertical; padding: 0.6rem;">${escapeHtml(engagementData.eng_scope)}</textarea>
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 500;">
                            <input type="checkbox" id="edit_eng_repeat" ${engagementData.eng_repeat === 'Y' ? 'checked' : ''}>
                            <span style="font-size: 13px; color: var(--text-primary);">Repeat Engagement</span>
                        </label>
                    </div>
                </div>

                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Hours &amp; Notes</h3>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Notes</label>
                        <textarea id="edit_eng_notes" class="swal2-input" style="width: 100%; min-height: 100px; resize: vertical; padding: 0.6rem;">${escapeHtml(engagementData.eng_notes)}</textarea>
                    </div>
                </div>
            </div>
        `;

        Swal.fire({
            title: 'Edit Engagement',
            html: htmlContent,
            confirmButtonText: 'Save Changes',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            width: '700px',
            confirmButtonColor: 'var(--ink)',
            background: swalColors().background,
            color: swalColors().color,
            didOpen: () => {
                const auditCheckboxes = document.querySelectorAll('.audit-type-checkbox');
                const socTypeSection  = document.getElementById('soc_type_section');
                const socTypeRadios   = document.querySelectorAll('input[name="soc_type"]');
                const socType1Dates   = document.getElementById('soc_type1_dates');
                const socType2Dates   = document.getElementById('soc_type2_dates');

                function updateFormVisibility() {
                    const selectedTypes = Array.from(auditCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                    const hasSOC = selectedTypes.includes('SOC 1') || selectedTypes.includes('SOC 2');
                    socTypeSection.style.display = hasSOC ? 'block' : 'none';
                    updateDateFields();
                }
                function updateDateFields() {
                    const selectedSocType = document.querySelector('input[name="soc_type"]:checked')?.value;
                    if (selectedSocType === 'Type 1') {
                        socType1Dates.style.display = 'block';
                        socType2Dates.style.display = 'none';
                    } else if (selectedSocType === 'Type 2') {
                        socType1Dates.style.display = 'none';
                        socType2Dates.style.display = 'block';
                    }
                }
                auditCheckboxes.forEach(checkbox => checkbox.addEventListener('change', updateFormVisibility));
                socTypeRadios.forEach(radio => radio.addEventListener('change', updateDateFields));
                updateFormVisibility();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const selectedAuditTypes = Array.from(document.querySelectorAll('.audit-type-checkbox'))
                    .filter(cb => cb.checked).map(cb => cb.value).join(',');
                const selectedSocType = document.querySelector('input[name="soc_type"]:checked')?.value || '';

                const updatedData = {
                    engagement_id:    engagementId,
                    eng_name:         document.getElementById('edit_eng_name').value,
                    eng_location:     document.getElementById('edit_eng_location').value,
                    eng_poc:          document.getElementById('edit_eng_poc').value,
                    eng_status:       document.getElementById('edit_eng_status').value,
                    eng_tsc:          document.getElementById('edit_eng_tsc').value,
                    eng_audit_type:   selectedAuditTypes,
                    eng_soc_type:     selectedSocType,
                    eng_scope:        document.getElementById('edit_eng_scope').value,
                    eng_as_of_date:   document.getElementById('edit_soc_as_of_date')?.value || '',
                    eng_start_period: document.getElementById('edit_soc_start_period')?.value || '',
                    eng_end_period:   document.getElementById('edit_soc_end_period')?.value || '',
                    eng_repeat:       document.getElementById('edit_eng_repeat').checked ? 'Y' : 'N',
                    eng_notes:        document.getElementById('edit_eng_notes').value
                };

                fetch('../api/update-engagement.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updatedData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reopenDrawerAfterReload(engagementId);
                        sessionStorage.setItem('showEngagementUpdatedToast', 'true');
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update engagement', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to update engagement', 'error');
                });
            }
        });
    }

    // ---------- Timeline import from spreadsheet (.xlsx/.xls/.csv) ----------
    // Each field tries an exact (case-insensitive, trimmed) match against the
    // task name your template actually uses first, falling back to a looser
    // keyword pattern for engagements whose sheet wording varies. Exact-first
    // matters: a loose "draft report" pattern alone would grab "Prepare Draft
    // Report" before "Draft Report Due" just because it appears earlier in
    // the sheet. Always routes into the Edit Timeline modal for review rather
    // than saving directly, and reports any of the 11 fields it couldn't find.
    // The two fieldwork phases match against the "finish/due" column exactly
    // like every other field (into their *_end_date), then RANGE_FIELD_PAIRS
    // below additionally pulls a start date for that same matched row from
    // whatever start-ish column the sheet has, if any — so both ends of the
    // range get filled from one import when the sheet has both columns, with
    // the end date always still reviewable/fillable by hand in the Edit
    // Timeline modal if the sheet doesn't have a start column.
    const TIMELINE_FIELD_LABELS = {
        internal_planning_call_date:  'Internal Planning Call',
        planning_memo_date:           'Planning Memo',
        irl_due_date:                 'IRL Due',
        client_planning_call_date:    'Client Planning Call',
        fieldwork_client_calls_end_date:    'Fieldwork - Client Calls',
        fieldwork_documentation_end_date:   'Fieldwork - Documentation',
        leadsheet_date:               'Leadsheet Due',
        conclusion_memo_date:         'Conclusion Memo',
        draft_report_due_date:        'Draft Report Due',
        final_report_date:            'Final Report Due',
        archive_date:                 'Archive Date'
    };
    const TIMELINE_IMPORT_PATTERNS = {
        internal_planning_call_date:  { exact: 'Internal Team Planning Call',            fallback: [/internal.*planning.*call/i] },
        planning_memo_date:           { exact: 'Compose Planning Memo',                  fallback: [/\bplanning memo\b/i] },
        irl_due_date:                 { exact: 'Send Information Request List (IRL)',    fallback: [/\birl\b/i, /information request list/i] },
        client_planning_call_date:    { exact: 'Client Planning Call',                   fallback: [/client.*planning.*call/i] },
        // Real templates seen so far label these as plain sub-task rows
        // under a parent "Fieldwork" line — "Client Calls" and
        // "Documentation Week 1" (which may repeat as Week 2, Week 3, etc.
        // on longer engagements) — not "Fieldwork - Client Calls" like the
        // fallback below assumes. \bclient\s*calls?\b matches "Client
        // Calls" without also grabbing "Client Planning Call" (a different,
        // separately-tracked field) since "Planning" breaks the \s*-only
        // gap between "client" and "call".
        fieldwork_client_calls_end_date:    { exact: 'Client Calls',          fallback: [/\bclient\s*calls?\b/i, /fieldwork.*client.*call/i] },
        fieldwork_documentation_end_date:   { exact: 'Documentation',         fallback: [/\bdocumentation\b/i] },
        leadsheet_date:                { exact: 'Lead Sheets Due',                        fallback: [/lead\s*sheet/i] },
        conclusion_memo_date:          { exact: 'Compose Conclusion Memo',                fallback: [/conclusion memo/i] },
        draft_report_due_date:         { exact: 'Draft Report Due',                       fallback: [/draft report.*due/i] },
        final_report_date:             { exact: 'Final Report Due',                       fallback: [/final report/i] },
        archive_date:                  { exact: 'Alek Archive',                           fallback: [/\barchive\b/i] }
    };
    // end-date field -> paired start-date field, both filled from the same
    // matched spreadsheet row when the sheet has a start-ish column.
    const RANGE_FIELD_PAIRS = {
        fieldwork_client_calls_end_date:  'fieldwork_client_calls_start_date',
        fieldwork_documentation_end_date: 'fieldwork_documentation_start_date',
    };

    function parseSpreadsheetRows(file) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = new Uint8Array(e.target.result);
                    // cellDates:false — read raw values (Excel serials / plain text) so
                    // date conversion never passes through a JS Date/timezone at all.
                    const workbook = XLSX.read(data, { type: 'array', cellDates: false, raw: true });
                    const sheet = workbook.Sheets[workbook.SheetNames[0]];
                    resolve(XLSX.utils.sheet_to_json(sheet, { defval: '', raw: true }));
                } catch (err) {
                    reject(err);
                }
            };
            reader.onerror = () => reject(new Error('Could not read the file'));
            reader.readAsArrayBuffer(file);
        });
    }

    // No Date object anywhere in here on purpose — Date/.toISOString() shifts
    // date-only values by a day depending on local timezone, which is exactly
    // what was happening before (6/1 imported as 6/2, etc).
    function parseDateCell(value) {
        if (value === '' || value === null || value === undefined) return null;
        if (typeof value === 'number') {
            const d = XLSX.SSF.parse_date_code(value);
            if (!d) return null;
            return `${d.y}-${String(d.m).padStart(2, '0')}-${String(d.d).padStart(2, '0')}`;
        }
        const str = String(value).trim();
        const slash = str.match(/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})$/);
        if (slash) {
            let [, mo, da, yr] = slash;
            if (yr.length === 2) yr = (Number(yr) < 70 ? '20' : '19') + yr;
            return `${yr}-${mo.padStart(2, '0')}-${da.padStart(2, '0')}`;
        }
        const iso = str.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (iso) return `${iso[1]}-${iso[2]}-${iso[3]}`;
        return null;
    }

    function matchTimelineFieldsFromRows(rows) {
        if (!rows.length) return { matched: {}, unmatched: Object.keys(TIMELINE_IMPORT_PATTERNS) };
        const keys = Object.keys(rows[0]);
        const taskKey = keys.find(k => /task/i.test(k) && /name/i.test(k)) || keys.find(k => /task/i.test(k)) || keys[0];
        const dateKey = keys.find(k => /planned/i.test(k) && /finish/i.test(k))
            || keys.find(k => /finish/i.test(k))
            || keys.find(k => /due/i.test(k));
        // Only used to additionally fill the start half of the two fieldwork
        // ranges (RANGE_FIELD_PAIRS) — every other field only ever reads
        // dateKey above, same as before.
        const startDateKey = keys.find(k => /planned/i.test(k) && /start/i.test(k))
            || keys.find(k => /^start/i.test(k.trim()))
            || keys.find(k => /start/i.test(k));

        const matched = {};
        const unmatched = [];
        Object.entries(TIMELINE_IMPORT_PATTERNS).forEach(([field, { exact, fallback }]) => {
            if (!dateKey) { unmatched.push(field); return; }

            const startField = RANGE_FIELD_PAIRS[field];
            if (startField) {
                // The two fieldwork ranges can span more than one row on
                // some templates — "Documentation Week 1"/"Week 2"/... —
                // so match every row whose name qualifies (exact label
                // first, falling back to the looser patterns) and span the
                // full range: earliest start across all of them to latest
                // finish, rather than just whichever row happens to be
                // first in the sheet.
                let matchedRows = rows.filter(r => String(r[taskKey] || '').trim().toLowerCase() === exact.toLowerCase());
                if (!matchedRows.length) {
                    for (const pattern of fallback) {
                        matchedRows = rows.filter(r => pattern.test(String(r[taskKey] || '').trim()));
                        if (matchedRows.length) break;
                    }
                }
                const endDates = matchedRows.map(r => parseDateCell(r[dateKey])).filter(Boolean).sort();
                if (!endDates.length) { unmatched.push(field); return; }
                matched[field] = endDates[endDates.length - 1];

                if (startDateKey) {
                    const startDates = matchedRows.map(r => parseDateCell(r[startDateKey])).filter(Boolean).sort();
                    if (startDates.length) matched[startField] = startDates[0];
                }
                return;
            }

            let row = rows.find(r => String(r[taskKey] || '').trim().toLowerCase() === exact.toLowerCase());
            if (!row) {
                for (const pattern of fallback) {
                    row = rows.find(r => pattern.test(String(r[taskKey] || '').trim()));
                    if (row) break;
                }
            }
            const parsedDate = row ? parseDateCell(row[dateKey]) : null;
            if (parsedDate) {
                matched[field] = parsedDate;
            } else {
                unmatched.push(field);
            }
        });
        return { matched, unmatched };
    }

    document.getElementById('timelineImportFileInput').addEventListener('change', async (ev) => {
        const file = ev.target.files[0];
        ev.target.value = '';
        if (!file || !drawerData) return;
        try {
            const rows = await parseSpreadsheetRows(file);
            const { matched, unmatched } = matchTimelineFieldsFromRows(rows);
            openEditTimelineModal(matched, unmatched.map(f => TIMELINE_FIELD_LABELS[f]));
        } catch (err) {
            console.error('Error:', err);
            Swal.fire('Error', 'Could not read that spreadsheet: ' + err.message, 'error');
        }
    });

    // Wire drawer chrome
    document.getElementById('drawerCloseBtn').addEventListener('click', closeDrawer);
    drawerScrimEl.addEventListener('click', closeDrawer);
    document.getElementById('drawerEditBtn').addEventListener('click', () => { if (drawerData) openEditEngagementModal(); });
    document.getElementById('drawerArchiveBtn').addEventListener('click', () => { if (drawerData) archiveEngagement(drawerData.engagement.eng_idno); });
    document.getElementById('drawerDeleteBtn').addEventListener('click', () => { if (drawerData) deleteEngagement(drawerData.engagement.eng_idno); });
    document.addEventListener('click', (ev) => {
        if (!ev.target.closest('.drawer-status-wrap')) {
            document.getElementById('drawerStatusPopover')?.classList.remove('open');
        }
    });

    // Planning doc lightbox
    function openDocLightbox(url) {
        document.getElementById('docLightboxImg').src = url;
        document.getElementById('docLightboxScrim').classList.add('open');
    }
    function closeDocLightbox() {
        document.getElementById('docLightboxScrim').classList.remove('open');
        document.getElementById('docLightboxImg').src = '';
    }
    document.getElementById('docLightboxClose').addEventListener('click', closeDocLightbox);
    document.getElementById('docLightboxScrim').addEventListener('click', (ev) => {
        if (ev.target.id === 'docLightboxScrim') closeDocLightbox();
    });
    document.addEventListener('keydown', (ev) => {
        if (ev.key === 'Escape') closeDocLightbox();
    });

    // Reopen the drawer (and, if flagged, the Manage Team modal) after a reload
    // triggered by a save inside it — mirrors the app's existing reload+reopen idiom.
    if (sessionStorage.getItem('reopenDrawerFor')) {
        const reopenId = sessionStorage.getItem('reopenDrawerFor');
        sessionStorage.removeItem('reopenDrawerFor');
        const reopenTeam = sessionStorage.getItem('reopenTeamModal');
        sessionStorage.removeItem('reopenTeamModal');
        openDrawer(reopenId).then(() => {
            if (reopenTeam) openManageTeamModal();
        });
    }

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'custom-toast';
        toast.innerHTML = `<i class="bi bi-check-circle-fill"></i><span>${message}</span>`;
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
            toast.classList.remove('show');
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, 4500);
    }
</script>
<script src="../assets/js/activity_counter.js?v=<?php echo time(); ?>"></script>
</body>
</html>
