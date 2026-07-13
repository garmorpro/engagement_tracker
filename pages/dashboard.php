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

// Get unread notifications
$notifications = [];
$unreadNotificationCount = 0;

$tableCheckQuery = "SHOW TABLES LIKE 'engagement_notifications'";
$tableCheckResult = $conn->query($tableCheckQuery);

if ($tableCheckResult && $tableCheckResult->num_rows > 0) {
    $notificationQuery = "SELECT * FROM engagement_notifications WHERE is_read = 'N' ORDER BY notif_timestamp DESC LIMIT 10";
    $notificationResult = $conn->query($notificationQuery);
    $notifications = $notificationResult ? $notificationResult->fetch_all(MYSQLI_ASSOC) : [];
    $unreadNotificationCount = count($notifications);
}

function getTimeAgo($datetime) {
    $now = new DateTime();
    $created = new DateTime($datetime);
    $diff = $now->diff($created);

    if ($diff->days > 0) {
        return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
    } elseif ($diff->h > 0) {
        return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    } elseif ($diff->i > 0) {
        return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    } else {
        return 'Just now';
    }
}

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

        .notification-badge { position: absolute; top: 3px; right: 3px; min-width: 15px; height: 15px; padding: 0 3px; border-radius: 8px; background: var(--critical); color: #fff; font-size: 9.5px; font-weight: 700; display: flex; align-items: center; justify-content: center; border: 2px solid var(--card); }

        .notification-dropdown {
            position: absolute; top: calc(100% + 8px); right: -10px; width: 360px; max-height: 420px; overflow-y: auto;
            background: var(--card); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,0.14);
            display: none; z-index: 60;
        }
        .notification-dropdown.active { display: block; }
        .notification-header { display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1rem; border-bottom: 1px solid var(--line); }
        .notification-header h3 { font-size: 13px; font-weight: 700; margin: 0; }
        .notification-item { display: flex; gap: 10px; padding: 0.75rem 1rem; border-bottom: 1px solid var(--line); cursor: pointer; }
        .notification-item:last-child { border-bottom: none; }
        .notification-item:hover { background: var(--paper); }
        .notification-item.unread { background: color-mix(in srgb, var(--ink) 5%, var(--card)); }
        .notification-icon { width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 13px; }
        .notification-icon.upcoming { background: color-mix(in srgb, var(--ink) 14%, transparent); color: var(--ink); }
        .notification-icon.milestone { background: color-mix(in srgb, var(--good) 14%, transparent); color: var(--good); }
        .notification-icon.archive { background: color-mix(in srgb, var(--text-muted) 14%, transparent); color: var(--text-muted); }
        .notification-title { font-size: 12.5px; font-weight: 700; }
        .notification-message { font-size: 12px; color: var(--text-muted); margin-top: 2px; }
        .notification-time { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
        .notification-empty { padding: 2rem 1rem; text-align: center; color: var(--text-muted); font-size: 12.5px; }

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
        .reg-row:hover { background: color-mix(in srgb, var(--ink) 6%, var(--paper)); }
        .reg-row:hover .row-actions { opacity: 1; }
        .reg-row.is-critical { background: var(--critical-tint); }
        .reg-row.is-critical:hover { background: var(--critical-tint-strong); }
        .reg-row.is-archived { opacity: 0.62; }

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

        @media (max-width: 720px) {
            .main-nav { display: none; }
            .search-wrap { width: 100%; }
            .toolbar { flex-wrap: wrap; }
            .reg-type, .list-head .lh-type { display: none; }
        }
    </style>
</head>
<body>

<!-- ========== HEADER ========== -->
<div class="top-header">
    <div class="header-inner">
        <a href="dashboard.php" class="brand">
            <span class="brand-icon">ET</span>
            <span class="brand-mark">Engagement Tracker</span>
        </a>
        <nav class="main-nav">
            <a class="active" href="dashboard.php">Engagements</a>
            <a href="engagement-timeline.php">Timeline</a>
            <a href="engagement-analytics.php">Analytics</a>
            <a href="tools.php">Tools</a>
        </nav>
        <div class="header-right">
            <button class="icon-btn" title="Dark mode">
                <i class="bi bi-moon"></i>
            </button>
            <div style="position: relative;">
                <button class="icon-btn" title="Notifications" onclick="event.stopPropagation(); document.getElementById('notificationDropdown')?.classList.toggle('active')">
                    <i class="bi bi-bell"></i>
                    <?php if ($unreadNotificationCount > 0): ?>
                    <div class="notification-badge"><?php echo $unreadNotificationCount; ?></div>
                    <?php endif; ?>
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button class="icon-btn" style="font-size: 14px;" onclick="document.getElementById('notificationDropdown').classList.remove('active')">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $notif): ?>
                            <?php
                                $iconClass = 'upcoming';
                                $icon = 'bi-calendar-event';
                                if ($notif['notif_type'] === 'upcoming_milestone') {
                                    $iconClass = 'milestone';
                                    $icon = 'bi-check-circle';
                                } elseif ($notif['notif_type'] === 'ready_to_archive') {
                                    $iconClass = 'archive';
                                    $icon = 'bi-archive';
                                }

                                $timeAgo = getTimeAgo($notif['notif_timestamp']);
                                $displayMessage = $notif['notif_message'];

                                if ($notif['notif_type'] === 'upcoming_key_date') {
                                    $engIdno = $notif['engagement_idno'];
                                    $timelineQuery = "SELECT
                                        internal_planning_call_date, internal_planning_call_completed_at,
                                        planning_memo_date, planning_memo_completed_at,
                                        irl_due_date, irl_completed_at,
                                        client_planning_call_date, client_planning_call_completed_at,
                                        fieldwork_date, fieldwork_completed_at,
                                        leadsheet_date, leadsheet_completed_at,
                                        conclusion_memo_date, conclusion_memo_completed_at,
                                        draft_report_due_date, draft_report_completed_at,
                                        final_report_date, final_report_completed_at,
                                        archive_date, archive_completed_at
                                        FROM engagement_timeline WHERE engagement_idno = ?";
                                    $stmt = $conn->prepare($timelineQuery);
                                    $stmt->bind_param('s', $engIdno);
                                    $stmt->execute();
                                    $tlResult = $stmt->get_result();
                                    $timeline = $tlResult->fetch_assoc();
                                    $stmt->close();

                                    if ($timeline) {
                                        $dateFields = [
                                            'internal_planning_call_date' => 'internal_planning_call_completed_at',
                                            'planning_memo_date' => 'planning_memo_completed_at',
                                            'irl_due_date' => 'irl_completed_at',
                                            'client_planning_call_date' => 'client_planning_call_completed_at',
                                            'fieldwork_date' => 'fieldwork_completed_at',
                                            'leadsheet_date' => 'leadsheet_completed_at',
                                            'conclusion_memo_date' => 'conclusion_memo_completed_at',
                                            'draft_report_due_date' => 'draft_report_completed_at',
                                            'final_report_date' => 'final_report_completed_at',
                                            'archive_date' => 'archive_completed_at'
                                        ];
                                        $titleMap = [
                                            'internal_planning_call_date' => 'Internal Planning Call',
                                            'planning_memo_date' => 'Planning Memo',
                                            'irl_due_date' => 'IRL Due Date',
                                            'client_planning_call_date' => 'Client Planning Call',
                                            'fieldwork_date' => 'Fieldwork',
                                            'leadsheet_date' => 'Leadsheet',
                                            'conclusion_memo_date' => 'Conclusion Memo',
                                            'draft_report_due_date' => 'Draft Report Due',
                                            'final_report_date' => 'Final Report',
                                            'archive_date' => 'Archive'
                                        ];
                                        foreach ($dateFields as $dateCol => $completedCol) {
                                            if ($timeline[$dateCol] && !$timeline[$completedCol]) {
                                                $daysAway = round((strtotime($timeline[$dateCol]) - time()) / 86400);
                                                $engName = htmlspecialchars($notif['engagement_idno']);
                                                $dateTitle = $titleMap[$dateCol];
                                                $displayMessage = $engName . ' - ' . $dateTitle . ' due in ' . max(0, $daysAway) . ' days';
                                                break;
                                            }
                                        }
                                    }
                                } elseif ($notif['notif_type'] === 'upcoming_milestone') {
                                    $engIdno = $notif['engagement_idno'];
                                    $milestoneQuery = "SELECT m.milestone_type, m.due_date, e.eng_name
                                        FROM engagement_milestones m
                                        JOIN engagements e ON m.engagement_idno = e.eng_idno
                                        WHERE m.engagement_idno = ? AND m.is_completed = 'N' AND m.due_date IS NOT NULL
                                        ORDER BY m.due_date ASC LIMIT 1";
                                    $stmt = $conn->prepare($milestoneQuery);
                                    $stmt->bind_param('s', $engIdno);
                                    $stmt->execute();
                                    $mResult = $stmt->get_result();
                                    $milestone = $mResult->fetch_assoc();
                                    $stmt->close();

                                    if ($milestone) {
                                        $daysAway = round((strtotime($milestone['due_date']) - time()) / 86400);
                                        $milestoneTitle = implode(' ', array_map('ucfirst', explode('_', strtolower($milestone['milestone_type']))));
                                        $displayMessage = $milestone['eng_name'] . ' - ' . $milestoneTitle . ' due in ' . max(0, $daysAway) . ' days';
                                    }
                                }
                            ?>
                            <div class="notification-item <?php echo $notif['is_read'] === 'N' ? 'unread' : ''; ?>" onclick="markNotificationAsRead(<?php echo $notif['notif_id']; ?>)">
                                <div class="notification-icon <?php echo $iconClass; ?>">
                                    <i class="bi <?php echo $icon; ?>"></i>
                                </div>
                                <div>
                                    <div class="notification-title"><?php echo htmlspecialchars($notif['notif_title']); ?></div>
                                    <div class="notification-message"><?php echo htmlspecialchars($displayMessage); ?></div>
                                    <div class="notification-time"><?php echo $timeAgo; ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="notification-empty">
                            <i class="bi bi-bell-slash" style="font-size: 32px; margin-bottom: 0.5rem; display: block; opacity: 0.5;"></i>
                            No notifications
                        </div>
                    <?php endif; ?>
                </div>
            </div>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
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

    async function markNotificationAsRead(notifId) {
        try {
            const response = await fetch('../api/mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notif_id: notifId })
            });
            const data = await response.json();
            if (data.success) location.reload();
        } catch (error) {
            console.error('Error:', error);
        }
    }

    // Close notification dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationBtn = document.querySelector('.icon-btn[title="Notifications"]');
        if (notificationDropdown && !notificationDropdown.contains(e.target) && !notificationBtn.contains(e.target)) {
            notificationDropdown.classList.remove('active');
        }
    });

    // Row click -> detail page; action buttons wired separately
    document.querySelectorAll('.reg-row').forEach(row => {
        row.addEventListener('click', () => { window.location.href = row.dataset.detailHref; });
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
                                <span style="font-size: 13px;">SOC 1</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="SOC 2" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px;">SOC 2</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="PCI" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px;">PCI</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="HITRUST" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px;">HITRUST</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="FISMA" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px;">FISMA</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="ISO" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px;">ISO</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" class="new-audit-type-checkbox" value="HIPAA" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px;">HIPAA</span>
                            </label>
                        </div>
                    </div>

                    <div id="new_soc_type_section" style="margin-bottom: 1.5rem; display: none; padding: 1.25rem; background: color-mix(in srgb, var(--ink) 8%, transparent); border-radius: 8px; border-left: 3px solid var(--ink);">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.3px;">SOC Type</label>
                        <div style="display: flex; gap: 1.5rem; margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="radio" name="new_soc_type" value="Type 1" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px;">Type 1</span>
                            </label>
                            <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 500;">
                                <input type="radio" name="new_soc_type" value="Type 2" style="width: 18px; height: 18px; cursor: pointer;">
                                <span style="font-size: 13px;">Type 2</span>
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
                            <span style="font-size: 13px;">Repeat Engagement</span>
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
