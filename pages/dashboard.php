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

// Due date state for an engagement: [dateObj|null, 'overdue'|'soon'|'ok'|'none']
function getDueInfo($engIdno, $timelineLookup) {
    if (!isset($timelineLookup[$engIdno]) || empty($timelineLookup[$engIdno]['final_report_date'])) {
        return [null, 'none'];
    }
    $due = new DateTime($timelineLookup[$engIdno]['final_report_date']);
    $today = new DateTime('today');
    $diffDays = (int) $today->diff($due)->format('%r%a');
    if ($diffDays < 0) return [$due, 'overdue'];
    if ($diffDays <= 5) return [$due, 'soon'];
    return [$due, 'ok'];
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

$attentionCount = 0;
foreach ($activeEngagements as $e) {
    [, $state] = getDueInfo($e['eng_idno'], $timelineLookup);
    if (in_array($state, ['overdue', 'soon'], true)) $attentionCount++;
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

        .result-note { margin-left: auto; font-size: 12px; color: var(--text-muted); }

        /* ---------- register list ---------- */
        .section-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.09em; color: var(--text-muted); margin: 1.6rem 0 0.5rem; display: flex; align-items: center; gap: 8px; }
        .section-label:first-child { margin-top: 0; }
        .section-label .swatch { width: 7px; height: 7px; border-radius: 1px; flex-shrink: 0; }
        .section-label .n { color: var(--text-muted); font-weight: 400; }

        .register { border-top: 1px solid var(--line); }
        .reg-row { display: flex; align-items: center; gap: 1.25rem; padding: 13px 8px 13px 4px; border-bottom: 1px solid var(--line); cursor: pointer; position: relative; transition: background-color 0.1s ease; }
        .reg-row.is-critical { background: var(--critical-tint); }
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
        }

        .reg-tick { width: 3px; align-self: stretch; border-radius: 2px; flex-shrink: 0; }
        .reg-id { font-size: 11.5px; color: var(--text-muted); width: 90px; flex-shrink: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reg-main { flex: 0 1 420px; min-width: 0; }
        .reg-name { font-weight: 600; font-size: 14px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reg-sub { font-size: 12px; color: var(--text-muted); margin-top: 1px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .reg-type { display: flex; align-items: center; gap: 4px; width: 170px; flex-shrink: 0; overflow: hidden; }
        .type-badge { font-size: 10.5px; font-weight: 700; color: var(--ink); background: color-mix(in srgb, var(--ink) 12%, transparent); padding: 2px 7px; border-radius: 5px; white-space: nowrap; flex-shrink: 0; }
        .type-more { font-size: 11px; color: var(--text-muted); font-weight: 600; flex-shrink: 0; }
        .type-none { font-size: 12.5px; color: var(--text-muted); }

        .reg-due { font-size: 12.5px; font-weight: 600; width: 96px; flex-shrink: 0; text-align: right; }
        .reg-due .tag { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .reg-due.overdue, .reg-due.overdue .tag { color: var(--critical); }
        .reg-due.soon .tag { color: var(--caution); }

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

        .drawer-notes-text { font-size: 13px; line-height: 1.6; color: var(--text-muted); white-space: pre-line; }

        .drawer-team-lead-row { display: flex; align-items: center; gap: 0.65rem; padding: 0.5rem 0.6rem; background: color-mix(in srgb, var(--manager) 7%, transparent); border-radius: 8px; margin-bottom: 0.6rem; }
        .drawer-avatar { width: 30px; height: 30px; border-radius: 8px; color: #fff; font-weight: 700; font-size: 11.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .drawer-lead-name { font-size: 13.5px; font-weight: 700; }
        .drawer-lead-role { font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--manager); }

        .drawer-role-group-label { font-size: 10px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); margin: 0.5rem 0 0.35rem; }
        .drawer-member-row { display: flex; align-items: flex-start; gap: 0.6rem; padding: 0.4rem 0.2rem; border-top: 1px solid var(--line); }
        .drawer-role-group:first-child .drawer-member-row:first-child { border-top: none; }
        .drawer-member-row .drawer-avatar { width: 26px; height: 26px; font-size: 10.5px; }
        .drawer-member-info { flex: 1; min-width: 0; }
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
            <div class="stat-card <?php echo $attentionCount ? 'stat-card--attention' : ''; ?>"><div><div class="value"><?php echo $attentionCount; ?></div><div class="label">Needs Attention</div></div></div>
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
        <button class="attention-link" id="attentionLink">
            <span class="swatch"></span>
            <span><?php echo $attentionCount; ?> need attention</span>
        </button>
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
        $renderRow = function ($eng) use ($timelineLookup, $showArchived, $statusMeta) {
            [$due, $dueState] = getDueInfo($eng['eng_idno'], $timelineLookup);
            $critical = !$showArchived && $dueState === 'overdue';
            $tickVar = $showArchived ? '--text-muted' : ($statusMeta[$eng['eng_status']]['var'] ?? '--text-muted');
            $attention = in_array($dueState, ['overdue', 'soon'], true) ? '1' : '0';
            $searchBlob = strtolower($eng['eng_name'] . ' ' . ($eng['eng_manager'] ?? ''));

            $dueHtml = '<div class="reg-due">&mdash;</div>';
            if ($due) {
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

            echo '<div class="reg-row ' . ($critical ? 'is-critical' : '') . ' ' . ($showArchived ? 'is-archived' : '') . '"'
                . ' data-id="' . htmlspecialchars($eng['eng_idno']) . '"'
                . ' data-detail-href="' . $detailPage . '?id=' . urlencode($eng['eng_idno']) . '"'
                . ' data-search="' . htmlspecialchars($searchBlob) . '"'
                . ' data-attention="' . $attention . '">'
                . '<div class="reg-tick" style="background:var(' . $tickVar . ')"></div>'
                . '<div class="reg-id mono">' . htmlspecialchars($eng['eng_idno']) . '</div>'
                . '<div class="reg-main">'
                . '<div class="reg-name">' . htmlspecialchars($eng['eng_name']) . '</div>'
                . '<div class="reg-sub">' . htmlspecialchars($eng['eng_manager'] ?? 'Unassigned') . '</div>'
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
                        [$dueA] = getDueInfo($a['eng_idno'], $timelineLookup);
                        [$dueB] = getDueInfo($b['eng_idno'], $timelineLookup);
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

    // Search + needs-attention filtering
    let attentionOnly = false;
    function applyFilters() {
        const query = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
        let visibleCount = 0;
        document.querySelectorAll('.reg-row').forEach(row => {
            const matchesSearch = !query || row.dataset.search.includes(query);
            const matchesAttention = !attentionOnly || row.dataset.attention === '1';
            const show = matchesSearch && matchesAttention;
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
        attentionOnly = !attentionOnly;
        document.getElementById('attentionLink').classList.toggle('active', attentionOnly);
        applyFilters();
    });
    applyFilters();

    // New Engagement
    document.querySelector('.btn-new-engagement')?.addEventListener('click', () => {
        const htmlContent = `
            <div style="text-align: left; max-height: 600px; overflow-y: auto;">
                <div style="margin-bottom: 2.5rem;">
                    <h3 style="font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.5px;">Basic Information</h3>
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">Engagement Name <span style="color: var(--critical);">*</span></label>
                        <input type="text" id="new_eng_name" class="swal2-input" placeholder="Enter engagement name" style="width: 100%; padding: 0.75rem;">
                    </div>
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">Location</label>
                        <input type="text" id="new_eng_location" class="swal2-input" placeholder="Enter location" style="width: 100%; padding: 0.75rem;">
                    </div>
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">Point of Contact</label>
                        <input type="text" id="new_eng_poc" class="swal2-input" placeholder="Enter point of contact" style="width: 100%; padding: 0.75rem;">
                    </div>
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">Status</label>
                        <select id="new_eng_status" class="swal2-input" style="width: 100%; padding: 0.75rem;">
                            <option value="planning" selected>Planning</option>
                            <option value="in-progress">In Progress</option>
                            <option value="in-review">In Review</option>
                            <option value="complete">Complete</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">Trusted Service Criteria</label>
                        <input type="text" id="new_eng_tsc" class="swal2-input" placeholder="Enter TSC" style="width: 100%; padding: 0.75rem;">
                    </div>
                </div>

                <div style="margin-bottom: 2.5rem;">
                    <h3 style="font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.5px;">Audit Details</h3>
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">Audit Types (Select all that apply)</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.9rem;">
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="SOC 1" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px; color: var(--text-primary);">SOC 1</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="SOC 2" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px; color: var(--text-primary);">SOC 2</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="PCI" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px; color: var(--text-primary);">PCI</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="HITRUST" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px; color: var(--text-primary);">HITRUST</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="FISMA" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px; color: var(--text-primary);">FISMA</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="ISO" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px; color: var(--text-primary);">ISO</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="HIPAA" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px; color: var(--text-primary);">HIPAA</span>
                            </label>
                        </div>
                    </div>

                    <div id="new_soc_type_section" style="margin-bottom: 1.5rem; display: none; padding: 1.25rem; background: color-mix(in srgb, var(--ink) 8%, transparent); border-radius: 8px; border-left: 3px solid var(--ink);">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">SOC Type</label>
                        <div style="display: flex; gap: 1.5rem; margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="radio" name="new_soc_type" value="Type 1" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px; color: var(--text-primary);">Type 1</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="radio" name="new_soc_type" value="Type 2" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px; color: var(--text-primary);">Type 2</span>
                            </label>
                        </div>
                        <div id="new_soc_type1_dates" style="display: none;">
                            <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">As Of Date</label>
                            <input type="date" id="new_soc_as_of_date" class="swal2-input" style="width: 100%; padding: 0.75rem;">
                        </div>
                        <div id="new_soc_type2_dates" style="display: none;">
                            <div style="margin-bottom: 0.9rem;">
                                <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">Start Period</label>
                                <input type="date" id="new_soc_start_period" class="swal2-input" style="width: 100%; padding: 0.75rem;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">End Period</label>
                                <input type="date" id="new_soc_end_period" class="swal2-input" style="width: 100%; padding: 0.75rem;">
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">Scope</label>
                        <textarea id="new_eng_scope" class="swal2-input" style="width: 100%; min-height: 80px; resize: vertical; padding: 0.75rem;" placeholder="Enter scope"></textarea>
                    </div>
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 500;">
                            <input type="checkbox" id="new_eng_repeat" style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="font-size: 13px; color: var(--text-primary);">Repeat Engagement</span>
                        </label>
                    </div>
                </div>

                <div>
                    <h3 style="font-size: 13px; font-weight: 700; color: var(--text); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.5px;">Notes</h3>
                    <div>
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">Notes</label>
                        <textarea id="new_eng_notes" class="swal2-input" style="width: 100%; min-height: 100px; resize: vertical; padding: 0.75rem;" placeholder="Enter notes"></textarea>
                    </div>
                </div>
            </div>
        `;

        Swal.fire({
            title: 'Create New Engagement',
            html: htmlContent,
            confirmButtonText: 'Create Engagement',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            width: '700px',
            confirmButtonColor: 'var(--ink)',
            background: swalColors().background,
            color: swalColors().color,
            didOpen: () => {
                const auditCheckboxes = document.querySelectorAll('.new-audit-type-checkbox');
                const socTypeSection = document.getElementById('new_soc_type_section');
                const socTypeRadios = document.querySelectorAll('input[name="new_soc_type"]');
                const socType1Dates = document.getElementById('new_soc_type1_dates');
                const socType2Dates = document.getElementById('new_soc_type2_dates');

                function updateFormVisibility() {
                    const selectedTypes = Array.from(auditCheckboxes).filter(cb => cb.checked).map(cb => cb.value);
                    const hasSOC = selectedTypes.includes('SOC 1') || selectedTypes.includes('SOC 2');
                    socTypeSection.style.display = hasSOC ? 'block' : 'none';
                    updateDateFields();
                }
                function updateDateFields() {
                    const selectedSocType = document.querySelector('input[name="new_soc_type"]:checked')?.value;
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
                document.getElementById('new_eng_name').focus();
            }
        }).then((result) => {
            if (!result.isConfirmed) return;
            const engName = document.getElementById('new_eng_name').value?.trim();
            if (!engName) {
                Swal.fire('Error', 'Engagement Name is required', 'error');
                return;
            }
            const selectedAuditTypes = Array.from(document.querySelectorAll('.new-audit-type-checkbox:checked')).map(cb => cb.value).join(', ');
            const newEngagementData = {
                eng_name: engName,
                eng_location: document.getElementById('new_eng_location').value || null,
                eng_poc: document.getElementById('new_eng_poc').value || null,
                eng_status: document.getElementById('new_eng_status').value || 'planning',
                eng_tsc: document.getElementById('new_eng_tsc').value || null,
                eng_audit_type: selectedAuditTypes || null,
                eng_soc_type: document.querySelector('input[name="new_soc_type"]:checked')?.value || null,
                eng_scope: document.getElementById('new_eng_scope').value || null,
                eng_as_of_date: document.getElementById('new_soc_as_of_date').value || null,
                eng_start_period: document.getElementById('new_soc_start_period').value || null,
                eng_end_period: document.getElementById('new_soc_end_period').value || null,
                eng_repeat: document.getElementById('new_eng_repeat').checked ? 'Y' : 'N',
                eng_notes: document.getElementById('new_eng_notes').value || null
            };

            fetch('../api/create-engagement.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(newEngagementData)
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        sessionStorage.setItem('showEngagementCreatedToast', 'true');
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to create engagement', 'error');
                    }
                } catch (parseError) {
                    if (text.includes('"success":true')) {
                        sessionStorage.setItem('showEngagementCreatedToast', 'true');
                        location.reload();
                    } else {
                        Swal.fire('Error', 'Invalid response from server: ' + text.substring(0, 200), 'error');
                    }
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Failed to create engagement: ' + error.message, 'error');
            });
        });
    });

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
    function fmtDate(raw) {
        if (!raw || raw === '0000-00-00') return null;
        const d = new Date(raw.length === 10 ? raw + 'T00:00:00' : raw);
        if (isNaN(d.getTime())) return null;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
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
                <div class="drawer-section-head"><div class="drawer-section-title"><span class="dot" style="background:var(--senior)"></span>Notes</div></div>
                <div class="drawer-notes-text">${eng.eng_notes ? escapeHtml(eng.eng_notes) : '<span style="font-style:italic;color:var(--text-muted);">No notes added yet.</span>'}</div>
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
            </div>
        `;

        renderDrawerTeam(team, auditTypes);
        renderDrawerTimeline(timeline, eng.eng_idno);
        renderPlanningDocRow(eng);

        document.getElementById('drawerManageTeamBtn').addEventListener('click', openManageTeamModal);
        document.getElementById('drawerEditTimelineBtn').addEventListener('click', () => openEditTimelineModal());
        document.getElementById('drawerImportTimelineBtn').addEventListener('click', () => {
            document.getElementById('timelineImportFileInput').click();
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

    function renderDrawerTeam(team, auditTypes) {
        const el = document.getElementById('drawerTeamContent');
        if (!team.length) {
            el.innerHTML = '<div class="drawer-team-empty">No team assigned yet.</div>';
            return;
        }

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
                grouped[key] = { emp_name: member.emp_name, role: (member.role || '').toLowerCase(), audit_types: dolMap };
            }
        });

        let manager = null;
        const bucketed = { senior: [], staff: [], intern: [] };
        Object.values(grouped).forEach(m => {
            if (m.role === 'manager') { manager = manager || m; }
            else if (bucketed[m.role]) { bucketed[m.role].push(m); }
        });

        function dolLinesHtml(member) {
            const groups = Object.entries(member.audit_types).filter(([, tags]) => tags.length);
            if (!groups.length) return '<span class="drawer-no-dol">No DOL assigned</span>';
            return '<div class="drawer-dol-lines">' + groups.map(([auditType, tags]) => `
                <div class="drawer-dol-line">
                    <span class="drawer-dol-audit-label">${escapeHtml(auditType)}</span>
                    <span class="drawer-dol-chips-wrap">${tags.map(t => `<span class="drawer-dol-chip ${DOL_TYPE_CLASS[auditType] || ''}">${escapeHtml(t)}</span>`).join('')}</span>
                </div>
            `).join('') + '</div>';
        }

        let html = '';
        if (manager) {
            html += `
                <div class="drawer-team-lead-row">
                    <div class="drawer-avatar" style="background:var(--manager)">${initials(manager.emp_name)}</div>
                    <div><div class="drawer-lead-name">${escapeHtml(manager.emp_name)}</div><div class="drawer-lead-role">Manager</div></div>
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
                            <div class="drawer-member-name">${escapeHtml(member.emp_name)}</div>
                            ${dolLinesHtml(member)}
                        </div>
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
                        refreshDrawer();
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

    // ---------- Manage Team modal (ported from engagement-details.php) ----------
    function openManageTeamModal() {
        const engagementId = drawerData.engagement.eng_idno;
        let currentTeam = (drawerData.team || []).slice();
        const auditTypesArray = (drawerData.engagement.eng_audit_type || '').split(',').map(t => t.trim()).filter(Boolean);
        const relevantAuditTypes = auditTypesArray.filter(type => DOL_AUDIT_TYPES.hasOwnProperty(type));

        const teamHTML = `
            <div class="team2-manage-body">
                <div class="team2-manage-left">
                    <h3>Team Members</h3>
                    <div class="team2-list-scroll" id="team-list"></div>
                </div>
                <div class="team2-manage-right">
                    <div>
                        <h4>Add Member</h4>
                        <div class="team2-field team2-ac-wrap">
                            <input type="text" id="add_emp_search" class="swal2-input" placeholder="Search employees…" autocomplete="off" style="margin: 0; width: 100%; font-size: 13px;">
                            <div class="team2-ac-list" id="add_emp_ac_list" style="display:none;"></div>
                        </div>
                    </div>
                    <div>
                        <h4>Overview</h4>
                        <div class="team2-stat-mini"><div class="n" id="manager-count">0</div><div class="l">Managers</div></div>
                        <div class="team2-stat-mini"><div class="n" id="senior-count">0</div><div class="l">Seniors</div></div>
                        <div class="team2-stat-mini"><div class="n" id="staff-count">0</div><div class="l">Staff</div></div>
                        <div class="team2-stat-mini"><div class="n" id="intern-count">0</div><div class="l">Interns</div></div>
                    </div>
                </div>
            </div>
        `;

        Swal.fire({
            title: 'Manage Team Members',
            html: teamHTML,
            showConfirmButton: false,
            cancelButtonText: 'Close',
            width: '1400px',
            heightAuto: false,
            customClass: { popup: 'milestone-modal-popup' },
            didOpen: () => {
                renderTeamList();
                wireAddMemberSearch();
            },
            willClose: () => {
                reopenDrawerAfterReload(engagementId);
                location.reload();
            }
        });

        function renderTeamList() {
            const teamListElement = document.getElementById('team-list');
            if (!teamListElement) return;

            if (currentTeam.length === 0) {
                teamListElement.innerHTML = `
                    <div style="display: flex; align-items: center; justify-content: center; text-align: center; color: var(--text-secondary); padding: 3rem 2rem; flex: 1;">
                        <div>
                            <i class="bi bi-people" style="font-size: 48px; display: block; margin-bottom: 1rem; opacity: 0.4;"></i>
                            <div style="font-size: 14px; font-weight: 600;">No team members yet</div>
                        </div>
                    </div>
                `;
                updateRoleCounts();
                return;
            }

            const roleOrder = ['manager', 'senior', 'staff', 'intern'];
            const sortedTeam = currentTeam.slice().sort((a, b) => {
                const ra = roleOrder.indexOf((a.role || '').toLowerCase());
                const rb = roleOrder.indexOf((b.role || '').toLowerCase());
                return (ra === -1 ? 99 : ra) - (rb === -1 ? 99 : rb);
            });

            teamListElement.innerHTML = sortedTeam.map(member => {
                const memberInitials = member.emp_name.split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('');
                const roleKey = (member.role || '').toLowerCase();
                return `
                    <div class="team2-card-row" data-emp-id="${member.emp_id}">
                        <div class="team2-card-row-top">
                            <div class="team2-avatar" style="background:${ROLE_COLOR_VAR[roleKey] || 'var(--ink)'}">${memberInitials}</div>
                            <div style="flex:1; min-width:0;">
                                <div class="team2-name">${member.emp_name}</div>
                                <div class="team2-role-label">${ROLE_LABELS[roleKey] || member.role}</div>
                            </div>
                            <div class="team2-icon-btns">
                                <button class="edit-team-btn" data-emp-id="${member.emp_id}" title="Edit"><i class="bi bi-pencil"></i></button>
                                <button class="danger delete-team-btn" data-emp-id="${member.emp_id}" title="Remove"><i class="bi bi-trash3"></i></button>
                            </div>
                        </div>
                        ${roleKey !== 'manager' ? getDOLByAuditType(member) : ''}
                    </div>
                `;
            }).join('');

            document.querySelectorAll('.edit-team-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const member = currentTeam.find(m => String(m.emp_id) === String(this.dataset.empId));
                    if (member) editTeamMember(member);
                    else Swal.fire('Error', 'Member not found', 'error');
                });
            });
            document.querySelectorAll('.delete-team-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const member = currentTeam.find(m => String(m.emp_id) === String(this.dataset.empId));
                    if (member) deleteTeamMember(member);
                    else Swal.fire('Error', 'Member not found', 'error');
                });
            });

            updateRoleCounts();
        }

        function getDOLByAuditType(member) {
            const dolSections = [];
            relevantAuditTypes.forEach(auditType => {
                const fieldName = DOL_AUDIT_TYPES[auditType];
                const dolValue = member[fieldName];
                if (dolValue) {
                    const duties = dolValue.split(',').map(d => d.trim()).filter(d => d);
                    const typeClass = DOL_TYPE_CLASS[auditType] || '';
                    const pillsHTML = duties.map(duty => `<span class="team2-chip ${typeClass}">${duty}</span>`).join('');
                    dolSections.push(`
                        <div class="team2-dol-line" style="margin-top: 0.6rem;">
                            <span class="team2-dol-type-tag">${auditType}</span>
                            <div class="team2-chip-row">${pillsHTML}</div>
                        </div>
                    `);
                }
            });
            if (dolSections.length > 0) {
                return `<div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--line);">${dolSections.join('')}</div>`;
            }
            return '<div class="team2-no-dol" style="margin-top: 0.6rem;">No DOL assigned</div>';
        }

        function updateRoleCounts() {
            ['manager', 'senior', 'staff', 'intern'].forEach(role => {
                const el = document.getElementById(role + '-count');
                if (el) el.textContent = currentTeam.filter(m => (m.role || '').toLowerCase() === role).length;
            });
        }

        function wireAddMemberSearch() {
            const input = document.getElementById('add_emp_search');
            const list = document.getElementById('add_emp_ac_list');
            if (!input || !list) return;

            let debounceTimer = null;
            input.addEventListener('input', () => {
                clearTimeout(debounceTimer);
                const query = input.value.trim();
                if (!query) { list.style.display = 'none'; return; }
                debounceTimer = setTimeout(() => searchEmployees(query), 200);
            });
            document.addEventListener('click', (ev) => {
                if (!ev.target.closest('.team2-ac-wrap')) list.style.display = 'none';
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
                    html += matches.map(e => `
                        <div class="team2-ac-item" data-emp-name="${e.emp_name}" data-emp-role="${e.emp_role}">
                            <div class="team2-avatar" style="width:22px;height:22px;font-size:9px;background:${ROLE_COLOR_VAR[e.emp_role] || 'var(--ink)'}">${e.emp_name.split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('')}</div>
                            ${e.emp_name}
                            <span class="role">${ROLE_LABELS[e.emp_role] || e.emp_role}</span>
                        </div>
                    `).join('');
                } else if (onTeamAlready.length) {
                    html += `<div class="team2-ac-empty">${onTeamAlready.map(e => e.emp_name).join(', ')} — already on this team.</div>`;
                } else {
                    html += `<div class="team2-ac-empty">No employee named "${query}" in the roster.</div>`;
                }
                if (!matches.length && !onTeamAlready.length) {
                    html += `<div class="team2-ac-newbtn" id="ac_new_btn">+ Add "${query}" as a new employee…</div>`;
                }

                list.innerHTML = html;
                list.style.display = 'block';

                list.querySelectorAll('.team2-ac-item').forEach(item => {
                    item.addEventListener('click', () => {
                        addTeamMember(item.dataset.empName, item.dataset.empRole);
                        input.value = '';
                        list.style.display = 'none';
                    });
                });
                document.getElementById('ac_new_btn')?.addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    renderNewEmployeeRolePicker(query);
                });
            }

            // Small inline picker (not a nested Swal — a second Swal.fire() while
            // "Manage Team Members" is open replaces the shared popup instance,
            // which closed the outer modal and reloaded the page). Lives in the
            // same dropdown as the search results, so nothing else closes.
            function renderNewEmployeeRolePicker(name) {
                let selectedRole = 'staff';
                list.innerHTML = `
                    <div class="team2-new-emp-picker">
                        <div class="team2-ac-empty" style="padding-bottom:8px;">
                            Role for "<strong style="color:var(--text-primary);">${escapeHtml(name)}</strong>"?
                        </div>
                        <div class="role-pick-grid" id="new_emp_role_segment">
                            ${['manager', 'senior', 'staff', 'intern'].map(role =>
                                `<button type="button" data-role="${role}" class="role-pick-btn ${role === selectedRole ? 'active' : ''}">${ROLE_LABELS[role]}</button>`
                            ).join('')}
                        </div>
                        <div class="team2-new-emp-actions">
                            <button type="button" class="team2-btn team2-btn-secondary" id="new_emp_cancel">Cancel</button>
                            <button type="button" class="team2-btn team2-btn-primary" id="new_emp_confirm">Add Employee</button>
                        </div>
                    </div>
                `;
                list.style.display = 'block';

                document.querySelectorAll('#new_emp_role_segment button').forEach(btn => {
                    btn.addEventListener('click', (ev) => {
                        ev.stopPropagation();
                        document.querySelectorAll('#new_emp_role_segment button').forEach(b => b.classList.remove('active'));
                        btn.classList.add('active');
                        selectedRole = btn.dataset.role;
                    });
                });
                document.getElementById('new_emp_cancel').addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    input.value = '';
                    list.style.display = 'none';
                });
                document.getElementById('new_emp_confirm').addEventListener('click', (ev) => {
                    ev.stopPropagation();
                    input.value = '';
                    list.style.display = 'none';
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
                    renderTeamList();
                } else {
                    Swal.fire('Error', data.message || 'Failed to add team member', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to add team member: ' + error.message, 'error');
            });
        }

        function editTeamMember(member) {
            const roleKey = (member.role || '').toLowerCase();
            let selectedRole = roleKey;
            const tagState = {};
            relevantAuditTypes.forEach(auditType => {
                const fieldName = DOL_AUDIT_TYPES[auditType];
                tagState[fieldName] = (member[fieldName] || '').split(',').map(t => t.trim()).filter(Boolean);
            });

            const dolColumnsHtml = relevantAuditTypes.map(auditType => `
                <div>
                    <div class="team2-dol-col-label">${auditType}</div>
                    <div class="team2-tag-input-box" data-field="${DOL_AUDIT_TYPES[auditType]}">
                        <div class="tags"></div>
                        <input type="text" placeholder="Add duty…">
                    </div>
                </div>
            `).join('');

            Swal.fire({
                html: `
                    <div style="text-align: left;">
                        <div class="team2-edit-header">
                            <div class="team2-avatar-lg" id="edit_avatar" style="background:${ROLE_COLOR_VAR[roleKey] || 'var(--ink)'}">${member.emp_name.split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('')}</div>
                            <div>
                                <div class="team2-edit-name">${member.emp_name}</div>
                                <div class="team2-edit-role-badge ${roleKey}" id="edit_role_badge">${ROLE_LABELS[roleKey] || member.role}</div>
                            </div>
                        </div>
                        <div class="team2-field">
                            <label class="team2-edit-label">Role on this Engagement</label>
                            <div class="team2-segmented" id="edit_role_segment">
                                ${['manager', 'senior', 'staff', 'intern'].map(role =>
                                    `<button type="button" data-role="${role}" class="${role === roleKey ? 'active' : ''}">${ROLE_LABELS[role]}</button>`
                                ).join('')}
                            </div>
                        </div>
                        <div class="team2-field" id="edit_dol_section" style="display:${roleKey === 'manager' ? 'none' : 'block'};">
                            <label class="team2-edit-label">Duties &amp; Responsibilities</label>
                            <div class="team2-dol-columns">${dolColumnsHtml}</div>
                            <div class="team2-tag-hint">e.g. CC1, CC2 — press Enter or comma to add a duty</div>
                        </div>
                    </div>
                `,
                confirmButtonText: 'Save Changes',
                cancelButtonText: 'Cancel',
                showCancelButton: true,
                confirmButtonColor: 'var(--ink)',
                didOpen: () => {
                    document.querySelectorAll('.team2-tag-input-box').forEach(box => {
                        const fieldName = box.dataset.field;
                        const tagsEl = box.querySelector('.tags');
                        const input = box.querySelector('input');

                        function render() {
                            tagsEl.innerHTML = tagState[fieldName].map((t, i) =>
                                `<span class="team2-tag-chip">${t}<button type="button" data-i="${i}">&times;</button></span>`
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

                    document.querySelectorAll('#edit_role_segment button').forEach(btn => {
                        btn.addEventListener('click', () => {
                            document.querySelectorAll('#edit_role_segment button').forEach(b => b.classList.remove('active'));
                            btn.classList.add('active');
                            selectedRole = btn.dataset.role;
                            document.getElementById('edit_avatar').style.background = ROLE_COLOR_VAR[selectedRole] || 'var(--ink)';
                            const badge = document.getElementById('edit_role_badge');
                            badge.className = 'team2-edit-role-badge ' + selectedRole;
                            badge.textContent = ROLE_LABELS[selectedRole];
                            document.getElementById('edit_dol_section').style.display = selectedRole === 'manager' ? 'none' : 'block';
                        });
                    });
                },
                preConfirm: () => {
                    const updateData = {
                        engagement_idno: engagementId,
                        emp_id: member.emp_id,
                        emp_name: member.emp_name,
                        role: selectedRole
                    };
                    relevantAuditTypes.forEach(auditType => {
                        const fieldName = DOL_AUDIT_TYPES[auditType];
                        updateData[fieldName] = selectedRole === 'manager' ? '' : tagState[fieldName].join(', ');
                    });
                    return updateData;
                }
            }).then((result) => {
                if (!result.isConfirmed || !result.value) return;

                fetch('../api/update-team-member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(result.value)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        reopenDrawerAfterReload(engagementId, 'reopenTeamModal');
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update team member', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to update team member: ' + error.message, 'error');
                });
            });
        }

        function deleteTeamMember(member) {
            Swal.fire({
                title: 'Remove Team Member?',
                text: `Are you sure you want to remove "${member.emp_name}" from the team? This action cannot be undone.`,
                icon: 'warning',
                confirmButtonText: 'Remove',
                cancelButtonText: 'Cancel',
                showCancelButton: true,
                confirmButtonColor: 'var(--danger-red)'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('../api/delete-team-member.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ engagement_idno: engagementId, emp_id: member.emp_id })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            currentTeam = currentTeam.filter(m => String(m.emp_id) !== String(member.emp_id));
                            renderTeamList();
                        } else {
                            Swal.fire('Error', data.message || 'Failed to delete team member', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire('Error', 'Failed to delete team member: ' + error.message, 'error');
                    });
                }
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
</body>
</html>
