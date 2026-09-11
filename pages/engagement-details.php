<?php 
require_once '../auth/session_check.php';
require_once '../path.php';
require_once '../includes/functions.php';

// Get engagement ID from query parameter
$engagementId = isset($_GET['id']) ? htmlspecialchars($_GET['id']) : null;

if (!$engagementId) {
    header('Location: dashboard.php');
    exit;
}


// Get engagement data
$engagement = null;
$allEngagements = getAllEngagements($conn);
foreach ($allEngagements as $eng) {
    if ($eng['eng_idno'] === $engagementId) {
        $engagement = $eng;
        break;
    }
}

// Current engagement you are viewing
$currentEngagementId = $engagementId; // assume this is set somewhere

// Initialize
$timeline = null;

// Get all timelines
$allTimelineData = getAllTimelineData($conn);

// Find the one for the current engagement
foreach ($allTimelineData as $row) {
    if ($row['engagement_idno'] == $currentEngagementId) {
        $timeline = $row;
        break;
    }
}

$nextStepName = null;
$nextStepDate = null;
$daysDifference = null;
$isOverdue = false;

if ($timeline) {

    $timelineSteps = [
    [
        'label' => 'Internal Planning Call',
        'date' => 'internal_planning_call_date',
        'completed' => 'internal_planning_call_completed_at'
    ],
    [
        'label' => 'Planning Memo',
        'date' => 'planning_memo_date',
        'completed' => 'planning_memo_completed_at'
    ],
    [
        'label' => 'IRL Due',
        'date' => 'irl_due_date',
        'completed' => 'irl_completed_at'
    ],
    [
        'label' => 'Client Planning Call',
        'date' => 'client_planning_call_date',
        'completed' => 'client_planning_call_completed_at'
    ],
    [
        'label' => 'Fieldwork',
        'date' => 'fieldwork_date',
        'completed' => 'fieldwork_completed_at'
    ],
    [
        'label' => 'Leadsheet Due',
        'date' => 'leadsheet_date',
        'completed' => 'leadsheet_completed_at'
    ],
    [
        'label' => 'Conclusion Memo',
        'date' => 'conclusion_memo_date',
        'completed' => 'conclusion_memo_completed_at'
    ],
    [
        'label' => 'Draft Report',
        'date' => 'draft_report_due_date',
        'completed' => 'draft_report_completed_at'
    ],
    [
        'label' => 'Final Report',
        'date' => 'final_report_date',
        'completed' => 'final_report_completed_at'
    ],
    [
        'label' => 'Archive',
        'date' => 'archive_date',
        'completed' => 'archive_completed_at'
    ]
];

    foreach ($timelineSteps as $step) {

    $dateField = $step['date'];
    $completedField = $step['completed'];

    if (
        !empty($timeline[$dateField]) &&
        ($timeline[$completedField] === null || $timeline[$completedField] === '')
    ) {

        $nextStepName = $step['label'];
        $nextStepDate = $timeline[$dateField];

        $today = new DateTime();
        $target = new DateTime($nextStepDate);

        $interval = $today->diff($target);
        $daysDifference = $interval->days;

        if ($target < $today) {
            $isOverdue = true;
        }

        break;
    }
}
}

$criticalValue = "No upcoming dates";
$criticalStatus = "";
$badgeText = "";

if ($nextStepDate) {

    if ($isOverdue) {
        $criticalValue = $daysDifference . " days";
        $criticalStatus = "overdue";
        $badgeText = "Overdue";
    } else {
        $criticalValue = $daysDifference . " days";
        $criticalStatus = "upcoming";
    }
}


// ENGAGEMENT TEAM
$team = []; // start as empty array

$allTeamData = getAllTeamData($conn);

// Collect all members for the current engagement
foreach ($allTeamData as $row) {
    if ($row['engagement_idno'] == $currentEngagementId) {
        $team[] = $row;
    }
}


// MILESTONES
$milestones = []; // start as empty array

$allMilestonesData = getAllMilestones($conn);

// Collect all milestones for the current engagement
foreach ($allMilestonesData as $row) {
    if ($row['engagement_idno'] == $currentEngagementId) {
        $milestones[] = $row;
    }
}


if (!$engagement) {
    header('Location: dashboard.php');
    exit;
}

// Alias engagement data for easier access in forms
$engagementData = $engagement;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($engagement['eng_name']); ?> - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles/main.css?v=<?php echo time(); ?>">
    <style>
        :root {
            /* These four are aliased to the navy palette below via var()
               indirection, so they automatically track light/dark mode
               without needing separate dark-mode overrides. */
            --primary-blue: var(--ink);
            --success-green: var(--staff);
            --warning-orange: var(--intern);
            --danger-red: var(--critical);
            --info-purple: var(--senior);
            --teal: #4DBFB8;
            --gray-100: #F1F2F5;
            --gray-200: #D0D5DB;
            --gray-300: #6A7382;
            --text-dark: #1A1A1A;
            --bg-primary: #FAFBFC;
            --bg-secondary: #FFFFFF;
            --border-color: #D0D5DB;
            --text-primary: #1A1A1A;
            --text-secondary: #6A7382;

            --ink: #1B3A5C;
            --paper: #F4F6F8;
            --card: #FFFFFF;
            --line: #DCE1E7;
            --line-strong: #C2CAD3;
            --critical: #B3261E;
            --critical-tint: rgba(179, 38, 30, 0.07);
            --critical-tint-strong: rgba(179, 38, 30, 0.13);
            --manager: #1B3A5C;
            --senior: #7A4FB0;
            --staff: #1F7A54;
            --intern: #A66A00;
        }

        body.dark-mode {
            --gray-100: #1E2736;
            --gray-200: #2D3847;
            --gray-300: #8B95A6;
            --text-dark: #E8EAED;
            --bg-primary: #0f1419;
            --bg-secondary: #1A2332;
            --border-color: #2D3847;
            --text-primary: #E8EAED;
            --text-secondary: #8B95A6;

            --ink: #6E9FCB;
            --paper: #10161D;
            --card: #171F28;
            --line: #2A343E;
            --line-strong: #3C4854;
            --critical: #E5766F;
            --critical-tint: rgba(229, 118, 111, 0.1);
            --critical-tint-strong: rgba(229, 118, 111, 0.18);
            --manager: #6E9FCB;
            --senior: #B79AE0;
            --staff: #5FB98A;
            --intern: #D3A44E;
        }

        body {
            background-color: var(--bg-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* ========== MAIN CONTAINER ========== */
        .main-container {
            padding: 2rem;
            min-height: 100vh;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
            position: sticky;
            top: 0;
            background: var(--bg-primary);
            z-index: 40;
            padding: 1rem 2rem;
            margin: -2rem -2rem 0 -2rem;
            width: calc(100% + 4rem);
            transition: box-shadow 0.3s ease;
        }

        .page-header.scrolled {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        body.dark-mode .page-header.scrolled {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        /* ========== BACK BUTTON ========== */
        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding-top: 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }

        .back-button:hover {
            color: var(--text-primary);
        }

        /* ========== ENGAGEMENT HEADER ========== */
        .engagement-header {
            display: flex;
            justify-content: space-between;
            gap: 2rem;
            flex: 1;
        }

        .engagement-left {
            flex: 1;
        }

        .engagement-right {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            align-items: flex-end;
        }

        .top-actions {
            display: flex;
            gap: 0.5rem;
        }

        .engagement-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .engagement-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .engagement-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.9rem;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-status {
            background: color-mix(in srgb, var(--success-green) 15%, transparent);
            color: var(--success-green);
        }

        /* Status colors match the Engagements list page (dashboard.php):
           planning=intern(amber), in-progress=ink(navy), in-review=senior(purple), complete=staff(green) */
        .engagement-badge.complete {
            background: color-mix(in srgb, var(--staff) 12%, transparent);
            color: var(--staff);
        }

        .engagement-badge.planning {
            background: color-mix(in srgb, var(--intern) 14%, transparent);
            color: var(--intern);
        }

        .engagement-badge.in-review {
            background: color-mix(in srgb, var(--senior) 14%, transparent);
            color: var(--senior);
        }

        .engagement-badge.in-progress {
            background: color-mix(in srgb, var(--ink) 12%, transparent);
            color: var(--ink);
        }

        .badge-priority {
            background: color-mix(in srgb, var(--danger-red) 15%, transparent);
            color: var(--danger-red);
        }

        .badge-repeat {
            background: color-mix(in srgb, var(--primary-blue) 15%, transparent);
            color: var(--primary-blue);
        }

        .engagement-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem 2rem;
            margin-bottom: 1.5rem;
        }

        .engagement-info-item {
            display: flex;
            gap: 0.75rem;
            align-items: flex-start;
        }

        .engagement-info-icon {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background: var(--gray-100);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            font-size: 16px;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        .engagement-info-content {
            flex: 1;
        }

        .engagement-info-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
            font-weight: 600;
        }

        .engagement-info-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .engagement-meta {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .engagement-meta-tag {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.4rem 0.75rem;
            background: var(--gray-100);
            border-radius: 6px;
            font-size: 12px;
            color: var(--text-primary);
        }

        .engagement-meta-tag i {
            color: var(--text-secondary);
            font-size: 13px;
        }

        /* ========== SIDEBAR ========== */
        .engagement-sidebar {
            background: transparent;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            width: 280px;
            height: fit-content;
        }

        .sidebar-section {
            margin-bottom: 1.75rem;
        }

        .sidebar-section:last-child {
            margin-bottom: 0;
        }

        .sidebar-section-title {
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        /* Critical Date Container */
        .critical-date-wrapper {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 1.25rem;
            justify-content: space-between;
        }

        .critical-date-icon-wrapper {
            flex-shrink: 0;
        }

        .critical-date-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: color-mix(in srgb, var(--danger-red) 15%, transparent);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--danger-red);
            font-size: 24px;
        }

        .critical-date-icon.overdue {
            animation: none;
        }

        .critical-date-icon.remaining {
    background: color-mix(in srgb, var(--primary-blue) 15%, transparent);
    color: var(--primary-blue);
}

.critical-date-value.remaining {
    color: var(--primary-blue);
}

.critical-date-status.remaining {
    color: var(--primary-blue);
}

        .overdue-badge {
            display: inline-block;
            background: color-mix(in srgb, var(--danger-red) 15%, transparent);
            color: var(--danger-red);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .overdue-badge.pulse {
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            from {
                box-shadow: 0 0 0 0 color-mix(in srgb, var(--danger-red) 70%, transparent);
            }
            to {
                box-shadow: 0 0 0 8px color-mix(in srgb, var(--danger-red) 0%, transparent);
            }
        }

        .critical-date-label {
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .critical-date-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--danger-red);
            margin-bottom: 0.25rem;
            line-height: 1;
        }

        .critical-date-value.remaining {
            color: var(--primary-blue);
        }

        .critical-date-status {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .critical-date-status.overdue {
            color: var(--danger-red);
            font-weight: 600;
        }

        /* Critical Date Box */
        .critical-date {
            background: transparent;
            border-radius: 0;
            padding: 0;
            text-align: left;
        }

        .critical-date-inner {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            gap: 0.25rem;
        }

        /* Upcoming Item */
        .upcoming-item {
            padding: 1rem;
            background: var(--gray-100);
            border-radius: 10px;
            text-align: left;
        }

        .upcoming-label {
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
            font-weight: 600;
        }

        .upcoming-title {
            font-size: 15px;
            font-weight: 600;
            color: var(--text-primary);
        }

        /* ========== ACTION BUTTONS ========== */
        .btn-edit {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 0.40rem 1.25rem;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            transition: all 0.2s;
        }

        .btn-edit:hover {
            background: color-mix(in srgb, var(--primary-blue) 85%, black);
            box-shadow: 0 4px 12px color-mix(in srgb, var(--primary-blue) 30%, transparent);
        }

        .btn-icon {
            width: 36px;
            height: 36px;
            border: 1px solid var(--border-color);
            background: var(--bg-secondary);
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: all 0.2s;
            font-size: 16px;
        }

        .btn-icon:hover {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
            background: color-mix(in srgb, var(--primary-blue) 5%, transparent);
        }

        /* ========== SECTION STYLING ========== */
        .section-wrapper {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
        }

        /* ========== TEAM SECTION ========== */
        .team-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            height: fit-content;
        }

        .team-section .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .team-section .manage-btn {
            width: 100%;
            margin-bottom: 1.5rem;
            justify-content: center;
        }

        /* ========== TIMELINE SECTION ========== */
        .timeline-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .timeline-section .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        /* ========== MILESTONES SECTION ========== */
        .milestones-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .milestones-section .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-header-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .section-icon.team {
            background: color-mix(in srgb, var(--manager) 16%, transparent);
            color: var(--manager);
        }

        .section-icon.timeline {
            background: color-mix(in srgb, var(--senior) 16%, transparent);
            color: var(--senior);
        }

        .section-icon.milestones {
            background: color-mix(in srgb, var(--staff) 16%, transparent);
            color: var(--staff);
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .manage-btn {
            background: none;
            border: 1px solid var(--border-color);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            color: var(--text-primary);
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .manage-btn:hover {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }

        /* ========== TEAM SECTION ========== */
        .team-members {
            display: grid;
            margin-top: 1rem;
            gap: 1rem;
        }

        /* ========== TEAM CARD (v2) ========== */
        .team2-panel { background: var(--card); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
        .team2-warn-badge { font-size: 11px; font-weight: 700; color: var(--critical); background: var(--critical-tint); border: 1px solid color-mix(in srgb, var(--critical) 30%, var(--line)); padding: 3px 9px; border-radius: 20px; animation: pulse 1.5s infinite; }
        .team2-avatar { width: 34px; height: 34px; border-radius: 8px; color: #fff; font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .team2-name { font-weight: 600; font-size: 13.5px; color: var(--text-primary); }
        .team2-lead-row { display: flex; align-items: center; gap: 0.7rem; padding: 0.9rem; background: color-mix(in srgb, var(--manager) 6%, var(--card)); border-bottom: 1px solid var(--line); }
        .team2-lead-tag { margin-left: auto; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--manager); background: color-mix(in srgb, var(--manager) 14%, transparent); padding: 3px 8px; border-radius: 20px; }
        .team2-hours-badge { font-size: 10px; font-weight: 700; color: var(--text-secondary); background: var(--paper); border: 1px solid var(--line); padding: 2px 7px; border-radius: 20px; white-space: nowrap; flex-shrink: 0; }
        .team2-role-group-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--text-secondary); padding: 0.8rem 0.9rem 0.4rem; }
        .team2-compact-member { display: flex; align-items: center; gap: 0.65rem; padding: 0.55rem 0.9rem; border-bottom: 1px solid var(--line); flex-wrap: wrap; }
        .team2-compact-member:last-child { border-bottom: none; }
        .team2-compact-dol { flex: 1 1 auto; min-width: 160px; }
        .team2-dol-lines { display: flex; flex-direction: column; gap: 4px; margin-top: 2px; }
        .team2-dol-line { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
        .team2-dol-type-tag { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-secondary); width: 44px; flex-shrink: 0; }
        .team2-chip-row { display: flex; flex-wrap: wrap; gap: 4px; align-items: center; }
        .team2-chip { font-size: 10.5px; font-weight: 700; color: var(--ink); background: color-mix(in srgb, var(--ink) 12%, transparent); padding: 3px 8px 3px 6px; border-radius: 5px; border-left: 2px solid var(--ink); }
        .team2-chip.t-soc2 { color: var(--senior); background: color-mix(in srgb, var(--senior) 12%, transparent); border-left-color: var(--senior); }
        .team2-no-dol { font-size: 11px; color: var(--critical); font-weight: 600; }
        .team2-empty { padding: 2rem 1rem; text-align: center; color: var(--text-secondary); font-size: 13px; }

        /* ========== MANAGE TEAM MEMBERS MODAL (v2) ========== */
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
        .team2-role-label { font-size: 11px; font-weight: 700; text-transform: capitalize; color: var(--text-secondary); }
        .team2-stat-mini { padding: 0.7rem 0.8rem; border-left: 3px solid var(--ink); border-radius: 6px; background: color-mix(in srgb, var(--ink) 7%, var(--card)); margin-bottom: 0.6rem; }
        .team2-stat-mini .n { font-size: 18px; font-weight: 800; color: var(--text-primary); }
        .team2-stat-mini .l { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-secondary); }

        .team2-field { margin-bottom: 0.6rem; }
        .team2-field input[type="text"] {
            width: 100%; padding: 8px 10px; border: 1px solid var(--line); border-radius: 7px; background: var(--card); color: var(--text-primary); font-size: 13px;
        }
        .team2-field input:focus { outline: none; border-color: var(--ink); }
        .team2-btn { padding: 9px; border-radius: 7px; font-size: 13px; font-weight: 600; cursor: pointer; text-align: center; border: none; }
        .team2-btn-primary { background: var(--ink); color: var(--card); width: 100%; }
        .team2-btn-secondary { background: var(--card); color: var(--text-secondary); border: 1px solid var(--line); }

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

        /* ========== EDIT TEAM MEMBER MODAL (v2) ========== */
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
        .team3-item-dols { margin-top: 5px; }
        .team3-item-dols .team2-dol-line { margin-bottom: 2px; }
        .team3-item-dols .team2-dol-type-tag { width: 38px; font-size: 9px; }
        .team3-item-dols .team2-chip { font-size: 9.5px; padding: 1px 5px; border-left-width: 2px; }
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
        .team3-saved-flash { display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; font-weight: 700; color: var(--staff); opacity: 0; transition: opacity 0.2s; }
        .team3-saved-flash.show { opacity: 1; }

        /* ========== TIMELINE SECTION ========== */
        .timeline-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
        }

        .timeline-item {
            background: var(--gray-100);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            position: relative;
            transition: all 0.2s ease;
        }

        .timeline-item:hover {
            background: var(--gray-150);
            border-color: var(--primary-blue);
        }

        .timeline-checkbox-container i {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .timeline-item:hover .timeline-checkbox-container i {
            opacity: 1;
        }

        .timeline-label {
            font-size: 11px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .timeline-date {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .timeline-status {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .timeline-status.completed {
            color: var(--success-green);
        }

        .timeline-status.overdue {
            color: var(--danger-red);
        }

        /* ========== DETAILS & NOTES SECTIONS ========== */
        .details-section,
        .notes-section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
        }

        .details-section .section-header,
        .notes-section .section-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-icon.details {
            background: color-mix(in srgb, var(--ink) 16%, transparent);
            color: var(--ink);
        }

        .section-icon.notes {
            background: color-mix(in srgb, var(--intern) 16%, transparent);
            color: var(--intern);
        }

       .details-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

/* Scope takes full row */
.detail-item.scope {
    grid-column: 1 / -1;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--text-secondary);
    margin-bottom: 0.25rem;
}

.detail-value {
    font-size: 0.95rem;
    font-weight: 500;
    color: var(--text-primary);
}

        .detail-value.badge {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            width: fit-content;
            background: var(--primary-blue);
            color: white;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 12px;
        }

        .notes-content {
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ========== SWEETALERT2 STYLING ========== */
        .swal2-container {
            z-index: 2000;
        }

        .swal2-popup {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            max-width: 550px;
            width: calc(100vw - 2rem);
            overflow-x: hidden;
        }

        body.dark-mode .swal2-popup {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
        }

        .swal2-title {
            color: var(--text-primary);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.3;
            padding: 0;
        }

        .swal2-html-container {
            color: var(--text-primary);
            padding: 0;
            margin: 0;
        }

        .swal2-input {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            border-radius: 6px;
            padding: 0.5rem 0.6rem !important;
            font-size: 13px !important;
            transition: all 0.2s;
            width: 100% !important;
            box-sizing: border-box;
            margin: 0 !important;
        }

        .swal2-input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-blue) 10%, transparent);
            outline: none;
        }

        .swal2-input::placeholder {
            color: var(--text-secondary);
        }

        .swal2-actions {
            gap: 0.75rem;
            margin-top: 1.5rem;
            display: flex;
            justify-content: center;
            padding: 0;
            margin-left: 0;
            margin-right: 0;
            margin-bottom: 0;
        }

        .swal2-confirm,
        .swal2-cancel {
            flex: 1;
            max-width: 200px;
            margin: 0 !important;
            padding: 0.7rem 1.5rem !important;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.2s;
            min-width: 0;
            height: auto;
        }

        .swal2-confirm {
            background: var(--primary-blue);
            color: white;
            border: none;
        }

        .swal2-confirm:hover {
            background: color-mix(in srgb, var(--primary-blue) 85%, black);
            box-shadow: 0 4px 12px color-mix(in srgb, var(--primary-blue) 30%, transparent);
        }

        .swal2-confirm:focus {
            outline: none;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-blue) 20%, transparent);
        }

        .swal2-cancel {
            background: var(--border-color);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .swal2-cancel:hover {
            background: color-mix(in srgb, var(--primary-blue) 5%, transparent);
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }

        .swal2-cancel:focus {
            outline: none;
            box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary-blue) 10%, transparent);
        }

        /* Milestone Modal Styling */
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

        /* ========== CUSTOM TOAST STYLING ========== */
        .custom-toast {
            position: fixed;
            bottom: 2rem;
            left: 2rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 600;
            z-index: 9999;
            animation: slideInLeft 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            backdrop-filter: blur(10px);
            border-left: 4px solid var(--success-green);
        }

        body.dark-mode .custom-toast {
            background: color-mix(in srgb, var(--card) 95%, transparent);
            border-color: color-mix(in srgb, var(--line-strong) 80%, transparent);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border-left: 4px solid var(--success-green);
        }

        .custom-toast.hide {
            animation: slideOutLeft 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .custom-toast i {
            animation: scalePopIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-120%); }
            to   { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideOutLeft {
            from { opacity: 1; transform: translateX(0); }
            to   { opacity: 0; transform: translateX(-120%); }
        }

        @keyframes scalePopIn {
            0%   { transform: scale(0.3); opacity: 0; }
            50%  { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }

        /* ========== MILESTONES SECTION ========== */
        .milestones-header-right {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .milestone-stat {
            background: var(--gray-100);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 13px;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .milestone-progress {
            background: rgba(160, 77, 253, 0.15);
            color: #A04DFD;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        .milestones-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        .milestone-item {
            background: var(--gray-100);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
        }

        .milestone-checkbox {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            flex-shrink: 0;
            margin-top: 0.1rem;
        }

        .milestone-checkbox.completed {
            background: rgba(79, 198, 95, 0.2);
            color: var(--success-green);
        }

        .milestone-checkbox.pending {
            background: var(--border-color);
            color: var(--text-secondary);
            border: none;
        }

        .milestone-content {
            flex: 1;
        }

        .milestone-title {
            font-size: 13px;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            font-weight: 700;
            line-height: 1.4;
        }

        .milestone-title.completed {
            text-decoration: line-through;
            color: var(--text-secondary);
        }

        .milestone-due {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* ========== DARK MODE BUTTON ========== */
        .dark-mode-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 40px;
            height: 40px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-secondary);
            transition: all 0.2s;
            font-size: 18px;
            z-index: 50;
        }

        .dark-mode-btn:hover {
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }

        /* ========== TOP ROW: Details & Notes ========== */
        .top-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
            margin-bottom: 2rem;
        }

        /* ========== TWO COLUMN LAYOUT ========== */
        .two-column-wrapper {
            display: grid;
            grid-template-columns: 400px 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
            align-items: start;
        }

        .left-column {
            display: grid;
            grid-template-rows: auto;
            gap: 2rem;
        }

        .right-column {
            display: grid;
            grid-template-rows: auto auto;
            gap: 2rem;
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1400px) {
            .timeline-grid {
                grid-template-columns: 1fr;
            }
            .milestones-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 1200px) {
            .two-column-wrapper {
                grid-template-columns: 1fr !important;
            }
            .top-row {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 1.5rem;
            }
            .engagement-title {
                font-size: 24px;
            }
            .engagement-info-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .engagement-meta {
                flex-direction: column;
            }
            .top-actions {
                width: 100%;
                order: -1;
            }
            .btn-edit {
                flex: 1;
            }
            .two-column-wrapper {
                grid-template-columns: 1fr !important;
            }
            .top-row {
                grid-template-columns: 1fr !important;
            }
            .right-column {
                grid-template-rows: auto auto;
            }
        }

        hr {
            border: 2px solid var(--border-color);
            margin-top: -25px !important;
            margin-left: -2rem !important;
            width: 100% !important;
        }

        .no-team-members {
            padding: 1rem;
            background-color: var(--primary-color);
            border: 1px dashed #ccc;
            border-radius: 0.5rem;
            text-align: center;
            color: var(--text-secondary);
            font-size: 14px;
            font-style: italic;
            margin-top: 0.5rem;
        }

        @keyframes pulse {
            0%   { transform: scale(1); opacity: 1; }
            50%  { transform: scale(1.05); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
    </style>
</head>
<body>

<!-- ========== MAIN CONTAINER ========== -->
<div class="main-container">
    <!-- Sticky Header -->
    <div class="page-header" id="pageHeader">
        <a href="dashboard.php" class="back-button">
            <i class="bi bi-chevron-left"></i>
            Dashboard
        </a>

        <!-- Action Buttons -->
        <div class="top-actions">
            <button class="btn-edit" id="editEngagementBtn">
                <i class="bi bi-pencil"></i>
                Edit
            </button>
            <button class="btn-icon" title="Archive" onclick="event.stopPropagation(); archiveEngagement('<?php echo htmlspecialchars($eng['eng_idno']); ?>')">
                <i class="bi bi-archive"></i>
            </button>
            <button class="btn-icon" title="Delete" onclick="event.stopPropagation(); deleteEngagement('<?php echo htmlspecialchars($eng['eng_idno']); ?>')">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>

    <!-- Page Content -->
    <div style="margin-bottom: 3rem; margin-top: 1rem;">
        <!-- Left Side Content -->
        <div class="engagement-header">
            <div class="engagement-left">
                <!-- Title -->
                <h1 class="engagement-title"><?php echo htmlspecialchars($engagement['eng_name']); ?></h1>

                <!-- Badges -->
                <div class="engagement-badges">
                    <?php
                        $statusClass = strtolower($eng['eng_status'] ?? 'planning');
                        $statusText = str_replace('-', ' ', $engagement['eng_status']);
                        $statusIcon = match($engagement['eng_status']) {
                            'in-progress' => 'bi-play-circle-fill',
                            'planning'    => 'bi-clipboard-check',
                            'in-review'   => 'bi-search',
                            'complete'    => 'bi-check-circle-fill',
                            'archived'    => 'bi-archive',
                            default       => 'bi-circle'
                        };
                    ?>
                    <span class="engagement-badge <?php echo $statusClass; ?>">
                        <i class="bi <?php echo $statusIcon; ?>"></i>
                        <?php echo ucfirst($statusText); ?>
                    </span>
                    <!-- <span class="engagement-badge badge-priority">
                        <i class="bi bi-exclamation-circle"></i>
                        High Priority
                    </span> -->
                    <?php if ($engagement['eng_repeat'] === 'Y'): ?>
                    <span class="engagement-badge badge-repeat">
                        <i class="bi bi-arrow-repeat"></i>
                        Repeat
                    </span>
                    <?php endif; ?>
                </div>

                <!-- Info Grid -->
                <div class="engagement-info-grid">
                    <!-- Location -->
                    <div class="engagement-info-item">
                        <div class="engagement-info-icon">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div class="engagement-info-content">
                            <div class="engagement-info-label">Location</div>
                            <div class="engagement-info-value"><?php echo htmlspecialchars($engagement['eng_location'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- Audit Type -->
                    <div class="engagement-info-item">
                        <div class="engagement-info-icon" style="color: var(--info-purple);">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="engagement-info-content">
                            <div class="engagement-info-label">Audit Type</div>
                            <div class="engagement-info-value">
                                <?php 
                                $auditTypes = $engagement['eng_audit_type'] ?? 'N/A';
                                $auditTypes = str_replace(',', ', ', $auditTypes);
                                echo htmlspecialchars($auditTypes);
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Period -->
                    <div class="engagement-info-item">
                        <div class="engagement-info-icon" style="color: var(--warning-orange);">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="engagement-info-content">
                            <div class="engagement-info-label">Period</div>
                            <div class="engagement-info-value">
                                <?php
                                $start = $engagement['eng_start_period'] ?? null;
                                $end   = $engagement['eng_end_period'] ?? null;
                                $asOf  = $engagement['eng_as_of_date'] ?? null;

                                if ($start && $end) {
                                    echo date("M j, Y", strtotime($start)) . " - " . date("M j, Y", strtotime($end));
                                } elseif ($asOf) {
                                    echo "As of " . date("M j, Y", strtotime($asOf));
                                } else {
                                    echo "N/A";
                                }
                                ?>
                            </div>
                        </div>
                    </div>

                    <!-- Report Type (only show if SOC type exists) -->
                    <?php if ($engagement['eng_soc_type']): ?>
                    <div class="engagement-info-item">
                        <div class="engagement-info-icon" style="color: var(--primary-blue);">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="engagement-info-content">
                            <div class="engagement-info-label">Report Type</div>
                            <div class="engagement-info-value">
                                <?php 
                                $socType = $engagement['eng_soc_type'];
                                echo $socType === 'Type 1' ? 'Type I' : ($socType === 'Type 2' ? 'Type II' : htmlspecialchars($socType));
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Meta Tags -->
                <div class="engagement-meta">
                    <div class="engagement-meta-tag">
                        <i class="bi bi-hash"></i>
                        <?php echo htmlspecialchars($engagement['eng_idno']); ?>
                    </div>
                    <div class="engagement-meta-tag">
                        <i class="bi bi-person-circle"></i>
                        Manager: <?php echo htmlspecialchars($engagement['eng_manager'] ?? 'Unassigned'); ?>
                    </div>
                    <div class="engagement-meta-tag">
                        <i class="bi bi-person-check"></i>
                        POC: <?php echo htmlspecialchars($engagement['eng_poc'] ?? 'Unassigned'); ?>
                    </div>
                </div>
            </div>
            

            <!-- Right Side: Sidebar -->
            <div class="engagement-right">
                <div class="engagement-sidebar">
                    <!-- Critical Date Section -->
                    <div class="sidebar-section">
                        <div class="critical-date-wrapper">
                            <div class="critical-date-icon-wrapper">
                                <div class="critical-date-icon <?php echo $isOverdue ? 'overdue' : 'remaining'; ?>">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                            </div>
                            <?php if ($isOverdue): ?>
<span class="overdue-badge pulse">Overdue</span>
<?php endif; ?>
                        </div>
                        <div class="critical-date">
                            <div class="critical-date-inner">
                                <div class="critical-date-label">Next Critical Date</div>
                                <div class="critical-date-value <?php echo $isOverdue ? 'overdue' : 'remaining'; ?>">
                                   <?php echo htmlspecialchars($criticalValue); ?>
                                </div>
                                <div class="critical-date-status <?php echo $criticalStatus; ?>">
                                   <?php echo $isOverdue ? 'overdue' : 'remaining'; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Section -->
                    <div class="sidebar-section">
                        <div class="sidebar-section-title">Upcoming</div>
                        <div class="upcoming-item">
                            <div class="upcoming-label">Next Milestone</div>
                            <div class="upcoming-title">
                                <?php echo htmlspecialchars($nextStepName ?? 'None'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            
        </div>
    </div>


    <hr>

    <!-- ========== TOP ROW: Details & Notes ========== -->
    <div class="top-row">

        <!-- ========== DETAILS SECTION ========== -->
        <div class="details-section">
            <div class="section-header" style="margin-bottom: 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div class="section-header-left">
                    <div class="section-icon details">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <h2 class="section-title">Details</h2>
                </div>
            </div>

            <div class="details-grid" style="margin-top: 1.5rem;">
                <!-- Scope -->
                <div class="detail-item scope">
                    <div class="detail-label">Scope</div>
                    <div class="detail-value"><?php echo htmlspecialchars($engagement['eng_scope'] ?? 'N/A'); ?></div>
                </div>

                <!-- Created -->
                <div class="detail-item">
                    <div class="detail-label">Created</div>
                    <div class="detail-value">
                        <?php 
                        $created = $engagement['eng_created'] ?? null;
                        echo ($created && $created !== '0000-00-00') 
                            ? date("M d, Y", strtotime($created))
                            : 'N/A';
                        ?>
                    </div>
                </div>

                <!-- Last Updated -->
                <div class="detail-item">
                    <div class="detail-label">Last Updated</div>
                    <div class="detail-value">
                        <?php 
                        $updated = $engagement['eng_updated'] ?? null;
                        echo ($updated && $updated !== '0000-00-00') 
                            ? date("M d, Y", strtotime($updated))
                            : 'N/A';
                        ?>
                    </div>
                </div>

                <!-- Archive Date -->
                <div class="detail-item">
                    <div class="detail-label">Archive Date</div>
                    <div class="detail-value">
                        <?php 
                        $archiveDate = $engagement['eng_archive'] ?? null;
                        echo ($archiveDate && $archiveDate !== '0000-00-00') 
                            ? date("M d, Y", strtotime($archiveDate))
                            : 'N/A';
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== NOTES SECTION ========== -->
        <div class="notes-section">
            <div class="section-header" style="margin-bottom: 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div class="section-header-left">
                    <div class="section-icon notes">
                        <i class="bi bi-sticky"></i>
                    </div>
                    <h2 class="section-title">Notes</h2>
                </div>
            </div>

            <div class="notes-content" style="margin-top: 1.5rem;">
                <?php 
                $notes = $engagement['eng_notes'] ?? '';
                if (!empty($notes)) {
                    echo htmlspecialchars($notes);
                } else {
                    echo '<span style="color: var(--text-secondary); font-style: italic;">No notes added yet.</span>';
                }
                ?>
            </div>
        </div>

    </div><!-- /.top-row -->

    <!-- ========== TWO COLUMN LAYOUT ========== -->
    <div class="two-column-wrapper">

        <!-- ========== LEFT COLUMN: Team only ========== -->
        <div class="left-column">
            <div class="team-section" style="position: relative;">
                <div class="section-header" style="margin-bottom: 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div class="section-header-left">
                        <div class="section-icon team">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h2 class="section-title">Team</h2>
                    </div>

                    <?php
                    // Check if there is any DOL assigned
                    $hasDOL = false;
                    if (!empty($team)) {
                        foreach ($team as $member) {
                            if (!empty($member['emp_soc1_dol']) || !empty($member['emp_soc2_dol']) ||
                                !empty($member['emp_hipaa_dol']) || !empty($member['emp_hitrust_dol']) ||
                                !empty($member['emp_fisma_dol'])) {
                                $hasDOL = true;
                                break;
                            }
                        }
                    }
                    ?>

                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <?php if (!$hasDOL): ?>
                            <div class="team2-warn-badge">No DOL Set</div>
                        <?php endif; ?>
                        <button id="manageTeamIconBtn" class="btn-icon" style="margin: 0; padding: 0.5rem;" title="Manage team members">
                            <i class="bi bi-gear"></i>
                        </button>
                    </div>
                </div>

                <div class="team2-panel" style="border-top: none; border-radius: 0 0 12px 12px;">
                <?php if (!empty($team)): ?>

                    <?php
                    // Group members by employee name and role
                    $groupedTeam = [];
                    $dolFields = [
                        'SOC 1'   => 'emp_soc1_dol',
                        'SOC 2'   => 'emp_soc2_dol',
                        'HIPAA'   => 'emp_hipaa_dol',
                        'HITRUST' => 'emp_hitrust_dol',
                        'FISMA'   => 'emp_fisma_dol',
                    ];
                    $dolTypeClass = ['SOC 1' => '', 'SOC 2' => 't-soc2', 'HIPAA' => '', 'HITRUST' => 't-soc2', 'FISMA' => ''];
                    foreach ($team as $member) {
                        $key = $member['emp_name'] . '|' . $member['role'];
                        if (!isset($groupedTeam[$key])) {
                            $dolMap = [];
                            foreach ($dolFields as $auditType => $field) {
                                if (!empty($member[$field])) {
                                    $dolMap[$auditType] = array_filter(array_map('trim', explode(',', $member[$field])));
                                }
                            }
                            $groupedTeam[$key] = [
                                'emp_name'       => $member['emp_name'],
                                'role'           => strtolower($member['role'] ?? ''),
                                'audit_types'    => $dolMap,
                                'budgeted_hours' => $member['budgeted_hours'] ?? null
                            ];
                        }
                    }

                    $manager = null;
                    $bucketed = ['senior' => [], 'staff' => [], 'intern' => []];
                    foreach ($groupedTeam as $member) {
                        if ($member['role'] === 'manager') {
                            $manager = $manager ?? $member;
                        } elseif (isset($bucketed[$member['role']])) {
                            $bucketed[$member['role']][] = $member;
                        }
                    }
                    $initialsOf = function ($name) {
                        $initials = '';
                        foreach (explode(' ', $name) as $part) {
                            if ($part !== '') $initials .= strtoupper($part[0]);
                        }
                        return $initials;
                    };
                    // Budgeted hours: trim a whole-number ".00" but keep a
                    // half-hour like ".5" — same "don't show noise" idea as
                    // the DOL Generator's hours display.
                    $fmtHours = function ($hours) {
                        if ($hours === null || $hours === '') return null;
                        return rtrim(rtrim(number_format((float) $hours, 2, '.', ''), '0'), '.');
                    };
                    // Always shows duties in canonical order rather than
                    // whatever order they were typed in — SOC 2 uses this
                    // fixed list (9 Common Criteria numerically, then the 4
                    // additional Trust Services Categories); SOC 1 (CO1,
                    // CO2, ...) and anything else sorts numerically by
                    // whatever digits are in the tag, alphabetical as a
                    // fallback. Mirrors sortDolTags() in dashboard.php —
                    // same logic, PHP here since this page's Team card
                    // renders server-side instead of via drawerData.
                    $soc2DolOrder = ['CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9', 'Availability', 'Confidentiality', 'Processing Integrity', 'Privacy'];
                    $sortDolTags = function ($tags, $auditType) use ($soc2DolOrder) {
                        $tags = array_values($tags);
                        if ($auditType === 'SOC 2') {
                            usort($tags, function ($a, $b) use ($soc2DolOrder) {
                                $ia = array_search($a, $soc2DolOrder);
                                $ib = array_search($b, $soc2DolOrder);
                                if ($ia !== false && $ib !== false) return $ia <=> $ib;
                                if ($ia !== false) return -1;
                                if ($ib !== false) return 1;
                                return strcasecmp($a, $b);
                            });
                        } else {
                            usort($tags, function ($a, $b) {
                                $na = preg_replace('/\D/', '', $a);
                                $nb = preg_replace('/\D/', '', $b);
                                if ($na !== '' && $nb !== '') return ((int) $na) <=> ((int) $nb);
                                if ($na !== '') return -1;
                                if ($nb !== '') return 1;
                                return strcasecmp($a, $b);
                            });
                        }
                        return $tags;
                    };
                    ?>

                    <?php if ($manager): ?>
                        <div class="team2-lead-row">
                            <div class="team2-avatar" style="background:var(--manager)"><?php echo htmlspecialchars($initialsOf($manager['emp_name'])); ?></div>
                            <div class="team2-name"><?php echo htmlspecialchars($manager['emp_name']); ?></div>
                            <?php if ($fmtHours($manager['budgeted_hours']) !== null): ?>
                                <span class="team2-hours-badge"><?php echo htmlspecialchars($fmtHours($manager['budgeted_hours'])); ?> hrs budgeted</span>
                            <?php endif; ?>
                            <span class="team2-lead-tag">Manager</span>
                        </div>
                    <?php endif; ?>

                    <?php foreach (['senior' => 'Senior', 'staff' => 'Staff', 'intern' => 'Intern'] as $roleKey => $roleLabel): ?>
                        <?php if (empty($bucketed[$roleKey])) continue; ?>
                        <div class="team2-role-group-label"><?php echo $roleLabel; ?> (<?php echo count($bucketed[$roleKey]); ?>)</div>
                        <?php foreach ($bucketed[$roleKey] as $member): ?>
                            <div class="team2-compact-member">
                                <div class="team2-avatar" style="background:var(--<?php echo $roleKey; ?>)"><?php echo htmlspecialchars($initialsOf($member['emp_name'])); ?></div>
                                <div class="team2-name"><?php echo htmlspecialchars($member['emp_name']); ?></div>
                                <?php if ($fmtHours($member['budgeted_hours']) !== null): ?>
                                    <span class="team2-hours-badge"><?php echo htmlspecialchars($fmtHours($member['budgeted_hours'])); ?> hrs budgeted</span>
                                <?php endif; ?>
                                <div class="team2-compact-dol">
                                    <?php
                                        $dolGroups = array_filter($member['audit_types'], fn($tags) => !empty($tags));
                                    ?>
                                    <?php if (empty($dolGroups)): ?>
                                        <span class="team2-no-dol">No DOL assigned</span>
                                    <?php else: ?>
                                        <div class="team2-dol-lines">
                                            <?php foreach ($dolGroups as $auditType => $tags): ?>
                                                <?php $tags = $sortDolTags($tags, $auditType); ?>
                                                <div class="team2-dol-line">
                                                    <span class="team2-dol-type-tag"><?php echo htmlspecialchars($auditType); ?></span>
                                                    <div class="team2-chip-row">
                                                        <?php foreach ($tags as $tag): ?>
                                                            <span class="team2-chip <?php echo $dolTypeClass[$auditType] ?? ''; ?>"><?php echo htmlspecialchars($tag); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>

                <?php else: ?>
                    <div class="team2-empty">
                        <i class="bi bi-exclamation-triangle-fill" style="margin-right: 0.3rem;"></i>
                        No team assigned yet.
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div><!-- /.left-column -->

        <!-- ========== RIGHT COLUMN: Timeline & Milestones stacked ========== -->
        <div class="right-column">

            <!-- ========== TIMELINE & KEY DATES SECTION ========== -->
            <div class="timeline-section">
                <div class="section-header" style="margin-bottom: 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div class="section-header-left">
                        <div class="section-icon timeline">
                            <i class="bi bi-calendar2"></i>
                        </div>
                        <h2 class="section-title">Timeline & Key Dates</h2>
                    </div>
                    <button class="manage-btn" id="timelineManageBtn" style="margin: 0; padding: 0.5rem 1rem;">
                        <i class="bi bi-gear"></i> Manage
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">

                <?php
                function renderTimelineStatus($date, $completed) {
                    if (!$date) {
                        echo '<div class="timeline-date">—</div>';
                        echo '<div class="timeline-status">Not scheduled</div>';
                        return;
                    }

                    $formattedDate = date("M j, Y", strtotime($date));
                    $today   = new DateTime();
                    $dueDate = new DateTime($date);
                    $diff    = $today->diff($dueDate);
                    $days    = $diff->days;

                    echo '<div class="timeline-date">'.htmlspecialchars($formattedDate).'</div>';

                    if (!empty($completed)) {
                        echo '<div class="timeline-status completed">
                                <i class="bi bi-check-circle-fill"></i> Completed
                              </div>';
                    } else {
                        if ($today <= $dueDate) {
                            echo '<div class="timeline-status">'.$days.'d remaining</div>';
                        } else {
                            echo '<div class="timeline-status overdue">
                                    <i class="bi bi-x-circle-fill"></i> '.$days.'d overdue
                                  </div>';
                        }
                    }
                }

                $currentEngagementId = $engagementId;
                $timeline = null;
                $allTimelineData = getAllTimelineData($conn);

                foreach ($allTimelineData as $row) {
                    if ($row['engagement_idno'] == $currentEngagementId) {
                        $timeline = $row;
                        break;
                    }
                }

                if (!$timeline) {
                    $timeline = [
                        'internal_planning_call_date'         => null,
                        'internal_planning_call_completed_at' => null,
                        'planning_memo_date'                   => null,
                        'planning_memo_completed_at'           => null,
                        'irl_due_date'                         => null,
                        'irl_completed_at'                     => null,
                        'client_planning_call_date'            => null,
                        'client_planning_call_completed_at'    => null,
                        'fieldwork_date'                       => null,
                        'fieldwork_completed_at'               => null,
                        'leadsheet_date'                       => null,
                        'leadsheet_completed_at'               => null,
                        'conclusion_memo_date'                 => null,
                        'conclusion_memo_completed_at'         => null,
                        'draft_report_due_date'                => null,
                        'draft_report_completed_at'            => null,
                        'final_report_date'                    => null,
                        'final_report_completed_at'            => null,
                        'archive_date'                         => null,
                        'archive_completed_at'                 => null
                    ];
                }
                ?>

                    <!-- Internal Planning Call -->
                    <div class="timeline-item" data-timeline-field="internal_planning_call" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-info-circle" style="font-size: 12px;"></i> INTERNAL PLANNING CALL
                        </div>
                        <?php renderTimelineStatus($timeline['internal_planning_call_date'], $timeline['internal_planning_call_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['internal_planning_call_completed_at']) ? 'checked' : ''; ?>" data-field-date="internal_planning_call_date" data-field-completed="internal_planning_call_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                    <!-- Planning Memo -->
                    <div class="timeline-item" data-timeline-field="planning_memo" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-file-text" style="font-size: 12px;"></i> PLANNING MEMO
                        </div>
                        <?php renderTimelineStatus($timeline['planning_memo_date'], $timeline['planning_memo_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['planning_memo_completed_at']) ? 'checked' : ''; ?>" data-field-date="planning_memo_date" data-field-completed="planning_memo_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                    <!-- IRL Due -->
                    <div class="timeline-item" data-timeline-field="irl_due" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-info-circle" style="font-size: 12px;"></i> IRL DUE
                        </div>
                        <?php renderTimelineStatus($timeline['irl_due_date'], $timeline['irl_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['irl_completed_at']) ? 'checked' : ''; ?>" data-field-date="irl_due_date" data-field-completed="irl_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                    <!-- Client Planning Call -->
                    <div class="timeline-item" data-timeline-field="client_planning_call" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-calendar" style="font-size: 12px;"></i> CLIENT PLANNING CALL
                        </div>
                        <?php renderTimelineStatus($timeline['client_planning_call_date'], $timeline['client_planning_call_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['client_planning_call_completed_at']) ? 'checked' : ''; ?>" data-field-date="client_planning_call_date" data-field-completed="client_planning_call_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                    <!-- Fieldwork -->
                    <div class="timeline-item" data-timeline-field="fieldwork" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-bar-chart" style="font-size: 12px;"></i> FIELDWORK
                        </div>
                        <?php renderTimelineStatus($timeline['fieldwork_date'], $timeline['fieldwork_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['fieldwork_completed_at']) ? 'checked' : ''; ?>" data-field-date="fieldwork_date" data-field-completed="fieldwork_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                    <!-- Leadsheet Due -->
                    <div class="timeline-item" data-timeline-field="leadsheet" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-info-circle" style="font-size: 12px;"></i> LEADSHEET DUE
                        </div>
                        <?php renderTimelineStatus($timeline['leadsheet_date'], $timeline['leadsheet_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['leadsheet_completed_at']) ? 'checked' : ''; ?>" data-field-date="leadsheet_date" data-field-completed="leadsheet_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                    <!-- Conclusion Memo -->
                    <div class="timeline-item" data-timeline-field="conclusion_memo" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-file-text" style="font-size: 12px;"></i> CONCLUSION MEMO
                        </div>
                        <?php renderTimelineStatus($timeline['conclusion_memo_date'], $timeline['conclusion_memo_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['conclusion_memo_completed_at']) ? 'checked' : ''; ?>" data-field-date="conclusion_memo_date" data-field-completed="conclusion_memo_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                    <!-- Draft Report Due -->
                    <div class="timeline-item" data-timeline-field="draft_report" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-file-text" style="font-size: 12px;"></i> DRAFT REPORT DUE
                        </div>
                        <?php renderTimelineStatus($timeline['draft_report_due_date'], $timeline['draft_report_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['draft_report_completed_at']) ? 'checked' : ''; ?>" data-field-date="draft_report_due_date" data-field-completed="draft_report_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                    <!-- Final Report Due -->
                    <div class="timeline-item" data-timeline-field="final_report" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-file-earmark" style="font-size: 12px;"></i> FINAL REPORT DUE
                        </div>
                        <?php renderTimelineStatus($timeline['final_report_date'], $timeline['final_report_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['final_report_completed_at']) ? 'checked' : ''; ?>" data-field-date="final_report_date" data-field-completed="final_report_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                    <!-- Archive Date -->
                    <div class="timeline-item" data-timeline-field="archive" data-engagement-id="<?php echo htmlspecialchars($engagementId); ?>">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-archive" style="font-size: 12px;"></i> ARCHIVE DATE
                        </div>
                        <?php renderTimelineStatus($timeline['archive_date'], $timeline['archive_completed_at']); ?>
                        <div class="timeline-checkbox-container <?php echo !empty($timeline['archive_completed_at']) ? 'checked' : ''; ?>" data-field-date="archive_date" data-field-completed="archive_completed_at" style="position: absolute; bottom: 1rem; right: 1rem; cursor: pointer;">
                            <i class="bi bi-check-circle-fill" style="font-size: 24px; color: var(--success-green);"></i>
                        </div>
                    </div>

                </div>
            </div><!-- /.timeline-section -->

            <!-- ========== MILESTONES SECTION ========== -->
            <div class="milestones-section">
                <div class="section-header" style="margin-bottom: 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div class="section-header-left">
                        <div class="section-icon milestones">
                            <i class="bi bi-flag-fill"></i>
                        </div>
                        <h2 class="section-title">Milestones</h2>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <button class="manage-btn" id="milestonesManageBtn" style="margin: 0; padding: 0.5rem 1rem;">
                            <i class="bi bi-gear"></i> Manage
                        </button>
                        <?php
                        if (!empty($milestones)) {
                            $completedCount = 0;
                            foreach ($milestones as $milestone) {
                                if ($milestone['is_completed'] === 'Y') { $completedCount++; }
                            }
                            $totalCount      = count($milestones);
                            $percentComplete = round(($completedCount / $totalCount) * 100);
                        } else {
                            $completedCount  = 0;
                            $totalCount      = 0;
                            $percentComplete = 0;
                        }
                        ?>
                        <span class="milestone-stat"><?php echo htmlspecialchars($completedCount); ?>/<?php echo htmlspecialchars($totalCount); ?></span>
                        <span class="milestone-progress"><?php echo htmlspecialchars($percentComplete); ?>% Complete</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">
                    <?php if (!empty($milestones)): ?>
                        <?php
                        $completedCount = 0;
                        foreach ($milestones as $milestone) {
                            if (!empty($milestone['milestone_completed_at'])) { $completedCount++; }
                        }
                        $totalCount      = count($milestones);
                        $percentComplete = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                        ?>
                        <?php foreach ($milestones as $milestone): ?>
                            <?php
                                $isCompleted = ($milestone['is_completed'] === 'Y');
                                $dueDateRaw  = $milestone['due_date'] ?? null;
                                if ($dueDateRaw && $dueDateRaw !== '0000-00-00' && $dueDateRaw !== '0000-00-00 00:00:00') {
                                    $dueDate = date("M j, Y", strtotime($dueDateRaw));
                                } else {
                                    $dueDate = 'No due date';
                                }
                            ?>
                            <div class="milestone-item" data-milestone-id="<?php echo htmlspecialchars($milestone['ms_id']); ?>">
                                <div class="milestone-checkbox <?php echo $isCompleted ? 'completed' : 'pending'; ?>">
                                    <?php if ($isCompleted): ?>
                                        <i class="bi bi-check-circle-fill"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="milestone-content">
                                    <div class="milestone-title <?php echo $isCompleted ? 'completed' : ''; ?>">
                                        <?php 
                                        $milestoneTitle = $milestone['milestone_type'];
                                        $milestoneTitle = implode(' ', array_map(function($word) {
                                            return ucfirst(strtolower($word));
                                        }, explode('_', $milestoneTitle)));
                                        echo htmlspecialchars($milestoneTitle);
                                        ?>
                                    </div>
                                    <div class="milestone-due">Due: <?php echo htmlspecialchars($dueDate); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="grid-column: 1 / -1; padding: 2rem; text-align: center; color: var(--text-secondary);">
                            <i class="bi bi-flag" style="font-size: 32px; margin-bottom: 1rem; opacity: 0.5;"></i>
                            <p>No milestones created yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div><!-- /.milestones-section -->

        </div><!-- /.right-column -->

    </div><!-- /.two-column-wrapper -->

    <!-- Dark Mode Button -->
    <button class="dark-mode-btn" id="darkModeBtn" title="Toggle dark mode">
        <i class="bi bi-moon"></i>
    </button>

</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
    const BASE_URL = "<?= BASE_URL ?>";
</script>
<script src="../assets/js/activity_counter.js?v=<?php echo time(); ?>"></script>
<script>

    // Check if we should show the engagement deleted toast
    if (sessionStorage.getItem('showDeletedToast')) {
        sessionStorage.removeItem('showDeletedToast');
        showToast('Engagement deleted successfully');
    }


    // Delete engagement function
    async function deleteEngagement(engagementId) {
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        
        const result = await Swal.fire({
            title: 'Delete Engagement?',
            text: 'This action cannot be undone. The engagement and all related data will be permanently deleted.',
            icon: 'warning',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            confirmButtonColor: 'var(--danger-red)',
            background: 'var(--card)',
            color: 'var(--text-primary)',
            confirmButtonClass: isDarkMode ? 'swal-dark-btn' : '',
            cancelButtonClass: isDarkMode ? 'swal-dark-cancel-btn' : ''
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch('../api/delete-engagement.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        engagement_id: engagementId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Set flag to show toast after reload
                    sessionStorage.setItem('showDeletedToast', 'true');
                    window.location.href = "dashboard.php";
                } else {
                    Swal.fire('Error', data.message || 'Failed to delete engagement', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to delete engagement', 'error');
            }
        }
    }


    // Scroll detection for header shadow
    const pageHeader = document.getElementById('pageHeader');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 0) {
            pageHeader.classList.add('scrolled');
        } else {
            pageHeader.classList.remove('scrolled');
        }
    });

    // Dark mode toggle
    const darkModeBtn = document.getElementById('darkModeBtn');
    
    function updateDarkModeIcon(isDark) {
        const icon = darkModeBtn?.querySelector('i');
        if (icon) {
            if (isDark) {
                icon.classList.remove('bi-moon');
                icon.classList.add('bi-sun');
            } else {
                icon.classList.remove('bi-sun');
                icon.classList.add('bi-moon');
            }
        }
    }


    // Archive engagement function
    async function archiveEngagement(engagementId) {
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        
        const result = await Swal.fire({
            title: 'Archive Engagement?',
            text: 'Are you sure you want to archive this engagement? It will be moved to the archive and hidden from the main dashboard.',
            icon: 'warning',
            confirmButtonText: 'Archive',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            confirmButtonColor: 'var(--primary-blue)',
            background: 'var(--card)',
            color: 'var(--text-primary)',
            confirmButtonClass: isDarkMode ? 'swal-dark-btn' : '',
            cancelButtonClass: isDarkMode ? 'swal-dark-cancel-btn' : ''
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch('../api/archive-engagement.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        engagement_id: engagementId
                    })
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        title: 'Archived!',
                        text: 'Engagement has been archived successfully.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        background: 'var(--card)',
                        color: 'var(--text-primary)'
                    }).then(() => {
                        window.location.href = "archive.php";
                    });
                } else {
                    Swal.fire('Error', data.message || 'Failed to archive engagement', 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to archive engagement', 'error');
            }
        }
    }

    // Initialize dark mode from localStorage
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    if (isDarkMode) {
        document.body.classList.add('dark-mode');
    }
    updateDarkModeIcon(isDarkMode);

    // Check if we should show success toast after reload
    if (sessionStorage.getItem('showTimelineToast')) {
        sessionStorage.removeItem('showTimelineToast');
        showToast('Timeline updated successfully');
    }
    
    if (sessionStorage.getItem('showMilestoneToast')) {
        const message = sessionStorage.getItem('showMilestoneToast');
        sessionStorage.removeItem('showMilestoneToast');
        showToast(message);
    }

    if (sessionStorage.getItem('reopenTeamModal')) {
        sessionStorage.removeItem('reopenTeamModal');
        document.getElementById('manageTeamIconBtn').click();
    }

    darkModeBtn?.addEventListener('click', () => {
        const isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', isDark);
        updateDarkModeIcon(isDark);
    });

    // Milestone checkbox functionality
    document.querySelectorAll('.milestone-checkbox').forEach((checkbox, index) => {
        checkbox.style.cursor = 'pointer';
        checkbox.addEventListener('click', async function(e) {
            e.stopPropagation();
            
            const milestoneItem  = this.closest('.milestone-item');
            const milestoneId    = milestoneItem.getAttribute('data-milestone-id');
            if (!milestoneId) return;

            const hasCompletedClass = this.classList.contains('completed');
            const newStatus         = hasCompletedClass ? 'N' : 'Y';

            try {
                const response = await fetch('../api/update-milestone.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        engagement_id: '<?php echo $engagementId; ?>',
                        milestone_id: milestoneId,
                        is_completed: newStatus
                    })
                });

                if (!response.ok) {
                    console.error('HTTP Error:', response.status, response.statusText);
                    alert('HTTP Error: ' + response.status);
                    return;
                }

                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('JSON Parse Error:', parseError);
                    alert('Invalid response from server: ' + responseText.substring(0, 100));
                    return;
                }

                if (data.success) {
                    this.classList.remove('completed', 'pending');
                    if (newStatus === 'Y') {
                        this.classList.add('completed');
                    } else {
                        this.classList.add('pending');
                    }
                    
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.remove('bi-check-circle-fill');
                        if (newStatus === 'Y') { icon.classList.add('bi-check-circle-fill'); }
                    } else if (newStatus === 'Y') {
                        const newIcon = document.createElement('i');
                        newIcon.className = 'bi bi-check-circle-fill';
                        this.appendChild(newIcon);
                    }
                    
                    const milestoneTitle = milestoneItem.querySelector('.milestone-title');
                    if (milestoneTitle) {
                        if (newStatus === 'Y') {
                            milestoneTitle.classList.add('completed');
                        } else {
                            milestoneTitle.classList.remove('completed');
                        }
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to update milestone'));
                }
            } catch (error) {
                console.error('Error updating milestone:', error);
                alert('Error: ' + error.message);
            }
        });
    });

    // Timeline checkbox functionality
    document.querySelectorAll('.timeline-checkbox-container').forEach((container) => {
        container.style.cursor = 'pointer';
        container.addEventListener('click', async function(e) {
            e.stopPropagation();
            
            const timelineItem     = this.closest('.timeline-item');
            const engagementId     = timelineItem.getAttribute('data-engagement-id');
            const dateField        = this.getAttribute('data-field-date');
            const completedField   = this.getAttribute('data-field-completed');
            if (!engagementId || !dateField || !completedField) return;

            const isChecked           = this.classList.contains('checked');
            const completedDateTime   = !isChecked ? new Date().toISOString().slice(0, 19).replace('T', ' ') : null;

            try {
                const response = await fetch('../api/update-timeline-checkbox.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        engagement_id:      engagementId,
                        date_field:         dateField,
                        completed_field:    completedField,
                        completed_datetime: completedDateTime
                    })
                });

                if (!response.ok) {
                    console.error('HTTP Error:', response.status);
                    alert('Error updating timeline');
                    return;
                }

                const data = await response.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to update timeline'));
                }
            } catch (error) {
                console.error('Error updating timeline:', error);
                alert('Error: ' + error.message);
            }
        });
    });

    // Edit Engagement Button Handler
    document.getElementById('editEngagementBtn').addEventListener('click', function() {
        const engagementData = {
            eng_name:         '<?php echo htmlspecialchars($engagementData['eng_name'] ?? '', ENT_QUOTES); ?>',
            eng_location:     '<?php echo htmlspecialchars($engagementData['eng_location'] ?? '', ENT_QUOTES); ?>',
            eng_poc:          '<?php echo htmlspecialchars($engagementData['eng_poc'] ?? '', ENT_QUOTES); ?>',
            eng_status:       '<?php echo htmlspecialchars($engagementData['eng_status'] ?? '', ENT_QUOTES); ?>',
            eng_tsc:          '<?php echo htmlspecialchars($engagementData['eng_tsc'] ?? '', ENT_QUOTES); ?>',
            eng_audit_type:   '<?php echo htmlspecialchars($engagementData['eng_audit_type'] ?? '', ENT_QUOTES); ?>',
            eng_soc_type:     '<?php echo htmlspecialchars($engagementData['eng_soc_type'] ?? '', ENT_QUOTES); ?>',
            eng_scope:        '<?php echo htmlspecialchars($engagementData['eng_scope'] ?? '', ENT_QUOTES); ?>',
            eng_as_of_date:   '<?php echo htmlspecialchars($engagementData['eng_as_of_date'] ?? '', ENT_QUOTES); ?>',
            eng_start_period: '<?php echo htmlspecialchars($engagementData['eng_start_period'] ?? '', ENT_QUOTES); ?>',
            eng_end_period:   '<?php echo htmlspecialchars($engagementData['eng_end_period'] ?? '', ENT_QUOTES); ?>',
            eng_repeat:       '<?php echo htmlspecialchars($engagementData['eng_repeat'] ?? '', ENT_QUOTES); ?>',
            eng_notes:        '<?php echo htmlspecialchars($engagementData['eng_notes'] ?? '', ENT_QUOTES); ?>'
        };

        const htmlContent = `
            <div style="text-align: left; max-height: 600px; overflow-y: auto; padding: 1rem;">
                <!-- Basic Information Section -->
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Basic Information</h3>
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Engagement Name</label>
                        <input type="text" id="edit_eng_name" class="swal2-input" value="${engagementData.eng_name}" style="width: 100%;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Location</label>
                        <input type="text" id="edit_eng_location" class="swal2-input" value="${engagementData.eng_location}" style="width: 100%;">
                    </div>

                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Point of Contact</label>
                        <input type="text" id="edit_eng_poc" class="swal2-input" value="${engagementData.eng_poc}" style="width: 100%;">
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
                        <input type="text" id="edit_eng_tsc" class="swal2-input" value="${engagementData.eng_tsc}" style="width: 100%;">
                    </div>
                </div>

                <!-- Audit Details Section -->
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Audit Details</h3>
                    
                    <div style="margin-bottom: 2rem;">
                        <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Audit Details</h3>
                        
                        <div style="margin-bottom: 1.5rem;">
                            <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Audit Types (Select all that apply)</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" class="audit-type-checkbox" value="SOC 1" ${engagementData.eng_audit_type && engagementData.eng_audit_type.includes('SOC 1') ? 'checked' : ''}>
                                    <span style="font-size: 13px;">SOC 1</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" class="audit-type-checkbox" value="SOC 2" ${engagementData.eng_audit_type && engagementData.eng_audit_type.includes('SOC 2') ? 'checked' : ''}>
                                    <span style="font-size: 13px;">SOC 2</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" class="audit-type-checkbox" value="PCI" ${engagementData.eng_audit_type && engagementData.eng_audit_type.includes('PCI') ? 'checked' : ''}>
                                    <span style="font-size: 13px;">PCI</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" class="audit-type-checkbox" value="HITRUST" ${engagementData.eng_audit_type && engagementData.eng_audit_type.includes('HITRUST') ? 'checked' : ''}>
                                    <span style="font-size: 13px;">HITRUST</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" class="audit-type-checkbox" value="FISMA" ${engagementData.eng_audit_type && engagementData.eng_audit_type.includes('FISMA') ? 'checked' : ''}>
                                    <span style="font-size: 13px;">FISMA</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" class="audit-type-checkbox" value="ISO" ${engagementData.eng_audit_type && engagementData.eng_audit_type.includes('ISO') ? 'checked' : ''}>
                                    <span style="font-size: 13px;">ISO</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="checkbox" class="audit-type-checkbox" value="HIPAA" ${engagementData.eng_audit_type && engagementData.eng_audit_type.includes('HIPAA') ? 'checked' : ''}>
                                    <span style="font-size: 13px;">HIPAA</span>
                                </label>
                            </div>
                        </div>

                        <div id="soc_type_section" style="margin-bottom: 1.5rem; display: none; padding: 1rem; background: color-mix(in srgb, var(--primary-blue) 10%, transparent); border-radius: 8px; border-left: 3px solid var(--primary-blue);">
                            <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">SOC Audit Type</label>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="radio" name="soc_type" value="Type 1" ${engagementData.eng_soc_type === 'Type 1' ? 'checked' : ''}>
                                    <span style="font-size: 13px;">Type 1</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 500;">
                                    <input type="radio" name="soc_type" value="Type 2" ${engagementData.eng_soc_type === 'Type 2' ? 'checked' : ''}>
                                    <span style="font-size: 13px;">Type 2</span>
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
                            <textarea id="edit_eng_scope" class="swal2-input" style="width: 100%; min-height: 80px; resize: vertical; padding: 0.6rem;">${engagementData.eng_scope}</textarea>
                        </div>

                        <div style="margin-bottom: 1rem;">
                            <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" id="edit_eng_repeat" ${engagementData.eng_repeat === 'Y' ? 'checked' : ''}>
                                <span style="font-size: 13px;">Repeat Engagement</span>
                            </label>
                        </div>
                    </div>

                <!-- Hours & Notes Section -->
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 14px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Hours & Notes</h3>
                    
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Notes</label>
                        <textarea id="edit_eng_notes" class="swal2-input" style="width: 100%; min-height: 100px; resize: vertical; padding: 0.6rem;">${engagementData.eng_notes}</textarea>
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
                
                auditCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', updateFormVisibility);
                });
                socTypeRadios.forEach(radio => {
                    radio.addEventListener('change', updateDateFields);
                });
                updateFormVisibility();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const selectedAuditTypes = Array.from(document.querySelectorAll('.audit-type-checkbox'))
                    .filter(cb => cb.checked).map(cb => cb.value).join(',');
                const selectedSocType = document.querySelector('input[name="soc_type"]:checked')?.value || '';

                const updatedData = {
                    engagement_id:    '<?php echo $engagementId; ?>',
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
                        sessionStorage.setItem('showMilestoneToast', 'Engagement updated successfully');
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
    });

    // Timeline Manage Button Handler
    document.getElementById('timelineManageBtn').addEventListener('click', function() {
        const timelineData = {
            internal_planning_call_date: '<?php echo htmlspecialchars($timeline['internal_planning_call_date'] ?? ''); ?>',
            planning_memo_date:          '<?php echo htmlspecialchars($timeline['planning_memo_date'] ?? ''); ?>',
            irl_due_date:                '<?php echo htmlspecialchars($timeline['irl_due_date'] ?? ''); ?>',
            client_planning_call_date:   '<?php echo htmlspecialchars($timeline['client_planning_call_date'] ?? ''); ?>',
            fieldwork_date:              '<?php echo htmlspecialchars($timeline['fieldwork_date'] ?? ''); ?>',
            leadsheet_date:              '<?php echo htmlspecialchars($timeline['leadsheet_date'] ?? ''); ?>',
            conclusion_memo_date:        '<?php echo htmlspecialchars($timeline['conclusion_memo_date'] ?? ''); ?>',
            draft_report_due_date:       '<?php echo htmlspecialchars($timeline['draft_report_due_date'] ?? ''); ?>',
            final_report_date:           '<?php echo htmlspecialchars($timeline['final_report_date'] ?? ''); ?>',
            archive_date:                '<?php echo htmlspecialchars($timeline['archive_date'] ?? ''); ?>'
        };

        Swal.fire({
            title: 'Edit Timeline & Key Dates',
            html: `
                <div style="text-align: left; display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; width: 100%; box-sizing: border-box; margin: 1rem 0;">
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
                    <div style="width: 100%; box-sizing: border-box; min-width: 0;">
                        <label style="display: block; margin-bottom: 0.4rem; font-weight: 600; font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Fieldwork</label>
                        <input type="date" id="fieldwork_date" class="swal2-input" value="${timelineData.fieldwork_date}">
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
                    engagement_id:               '<?php echo $engagementId; ?>',
                    internal_planning_call_date: document.getElementById('internal_planning_call_date').value,
                    planning_memo_date:          document.getElementById('planning_memo_date').value,
                    irl_due_date:                document.getElementById('irl_due_date').value,
                    client_planning_call_date:   document.getElementById('client_planning_call_date').value,
                    fieldwork_date:              document.getElementById('fieldwork_date').value,
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
    });

    // Milestones Manage Button Handler
    document.getElementById('milestonesManageBtn').addEventListener('click', function() {
        const milestones = <?php echo json_encode($milestones); ?>;
        
        let milestonesHTML = `
            <div style="display: flex; flex-direction: column; height: 100%; gap: 0;">
                <button id="addMilestoneBtn" style="width: 100%; background: linear-gradient(135deg, var(--primary-blue), #3671E0); color: white; border: none; padding: 1rem; border-radius: 12px; cursor: pointer; font-weight: 600; margin-bottom: 1.25rem; display: flex; align-items: center; justify-content: center; gap: 0.6rem; font-size: 14px; box-shadow: 0 4px 12px rgba(68, 135, 252, 0.3); transition: all 0.2s; font-size: 15px;">
                    <i class="bi bi-plus-circle" style="font-size: 18px;"></i> Add New Milestone
                </button>
                
                <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.9rem; padding-right: 0.5rem;">
        `;
        
        if (milestones.length > 0) {
            milestones.forEach((milestone, index) => {
                const isCompleted = milestone.is_completed === 'Y';
                const milestoneTitle = milestone.milestone_type
                    .split('_')
                    .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
                    .join(' ');
                
                milestonesHTML += `
                    <div style="display: flex; gap: 1rem; padding: 1.1rem; background: var(--bg-primary); border: 1.5px solid var(--border-color); border-radius: 12px; align-items: center; transition: all 0.2s; cursor: default;">
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; font-size: 15px; margin-bottom: 0.5rem; word-break: break-word; color: var(--text-primary); text-align: left; ${isCompleted ? 'text-decoration: line-through; opacity: 0.7;' : ''}">${milestoneTitle}</div>
                            <div style="font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <span style="display: flex; align-items: center; gap: 0.4rem;">
                                    <i class="bi bi-calendar3" style="font-size: 12px;"></i>
                                    ${milestone.due_date || 'No due date'}
                                </span>
                                ${isCompleted ? '<span style="color: var(--success-green); font-weight: 600; display: flex; align-items: center; gap: 0.3rem;"><i class="bi bi-check-circle-fill" style="font-size: 14px;"></i>Completed</span>' : ''}
                            </div>
                        </div>
                        <div style="display: flex; gap: 0.5rem; flex-shrink: 0;">
                            <button class="edit-milestone-btn" data-index="${index}" style="background: var(--primary-blue); color: white; border: none; padding: 0.6rem 1rem; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 0.4rem;" title="Edit milestone">
                                <i class="bi bi-pencil-square" style="font-size: 14px;"></i>Edit
                            </button>
                            <button class="delete-milestone-btn" data-index="${index}" style="background: #C90012; color: white; border: none; padding: 0.6rem 1rem; border-radius: 8px; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 0.4rem;" title="Delete milestone">
                                <i class="bi bi-trash3" style="font-size: 14px;"></i>Delete
                            </button>
                        </div>
                    </div>
                `;
            });
        } else {
            milestonesHTML += '<div style="flex: 1; display: flex; align-items: center; justify-content: center; text-align: center; color: var(--text-secondary);"><div><i class="bi bi-calendar-check" style="font-size: 48px; display: block; margin-bottom: 1rem; opacity: 0.4;"></i><div style="font-size: 15px; font-weight: 600;">No milestones yet</div><div style="font-size: 13px; margin-top: 0.5rem; line-height: 1.5;">Start tracking your progress by<br/>adding your first milestone</div></div></div>';
        }
        
        milestonesHTML += `</div></div>`;
        
        Swal.fire({
            title: 'Manage Milestones',
            html: milestonesHTML,
            showConfirmButton: false,
            cancelButtonText: 'Close',
            width: '600px',
            heightAuto: false,
            customClass: { popup: 'milestone-modal-popup' },
            didOpen: () => {
                document.querySelectorAll('.edit-milestone-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const index     = this.dataset.index;
                        const milestone = milestones[index];
                        editMilestone(milestone, index);
                    });
                });
                
                document.querySelectorAll('.delete-milestone-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const index = this.dataset.index;
                        deleteMilestone(milestones[index]);
                    });
                });
                
                document.getElementById('addMilestoneBtn').addEventListener('click', function() {
                    addNewMilestone();
                });
            }
        });
    });

    function editMilestone(milestone, index) {
        Swal.fire({
            title: 'Edit Milestone',
            html: `
                <div style="text-align: left; display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Milestone Name</label>
                        <input type="text" id="edit_milestone_name" class="swal2-input" value="${milestone.milestone_type}" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Due Date</label>
                        <input type="date" id="edit_due_date" class="swal2-input" value="${milestone.due_date}" style="width: 100%;">
                    </div>
                </div>
            `,
            confirmButtonText: 'Save Changes',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            didOpen: () => { document.getElementById('edit_milestone_name').focus(); }
        }).then((result) => {
            if (result.isConfirmed) {
                const updatedMilestone = {
                    engagement_id:      '<?php echo $engagementId; ?>',
                    milestone_id:       milestone.ms_id,
                    old_milestone_type: milestone.milestone_type,
                    milestone_type:     document.getElementById('edit_milestone_name').value,
                    due_date:           document.getElementById('edit_due_date').value
                };
                
                fetch('../api/update-milestone-details.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updatedMilestone)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        sessionStorage.setItem('showMilestoneToast', 'Milestone updated successfully');
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update milestone', 'error');
                    }
                });
            }
        });
    }

    function addNewMilestone() {
        Swal.fire({
            title: 'Add New Milestone',
            html: `
                <div style="text-align: left; display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Milestone Name</label>
                        <input type="text" id="new_milestone_name" class="swal2-input" placeholder="Enter milestone name" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Due Date</label>
                        <input type="date" id="new_due_date" class="swal2-input" style="width: 100%;">
                    </div>
                </div>
            `,
            confirmButtonText: 'Add Milestone',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            didOpen: () => { document.getElementById('new_milestone_name').focus(); }
        }).then((result) => {
            if (result.isConfirmed) {
                const newMilestone = {
                    engagement_id:  '<?php echo $engagementId; ?>',
                    milestone_type: document.getElementById('new_milestone_name').value,
                    due_date:       document.getElementById('new_due_date').value
                };
                
                fetch('../api/add-milestone.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(newMilestone)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        sessionStorage.setItem('showMilestoneToast', 'Milestone added successfully');
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to add milestone', 'error');
                    }
                });
            }
        });
    }

    function deleteMilestone(milestone) {
        Swal.fire({
            title: 'Delete Milestone?',
            text: `Are you sure you want to delete "${milestone.milestone_type}"? This action cannot be undone.`,
            icon: 'warning',
            confirmButtonText: 'Delete',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            confirmButtonColor: '#C90012'
        }).then((result) => {
            if (result.isConfirmed) {
                const deleteData = {
                    engagement_id: '<?php echo $engagementId; ?>',
                    milestone_id:  milestone.ms_id
                };
                
                fetch('../api/delete-milestone.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(deleteData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        sessionStorage.setItem('showMilestoneToast', 'Milestone deleted successfully');
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to delete milestone', 'error');
                    }
                });
            }
        });
    }

    function showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'custom-toast success';
        toast.innerHTML = `
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <i class="bi bi-check-circle-fill" style="font-size: 20px; color: var(--success-green);"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 300);
        }, 5000);
    }
</script>


<script>
// Team Management Modal Handler
document.getElementById('manageTeamIconBtn').addEventListener('click', function() {
    let currentTeam = <?php echo json_encode($team); ?>;
    const engagementId = '<?php echo $engagementId; ?>';

    const engagementAuditTypes = '<?php echo htmlspecialchars($engagement['eng_audit_type'] ?? '', ENT_QUOTES); ?>';
    const auditTypesArray = engagementAuditTypes.split(',').map(t => t.trim()).filter(t => t);

    const supportedAuditTypes = {
        'SOC 1':   'emp_soc1_dol',
        'SOC 2':   'emp_soc2_dol',
        'HIPAA':   'emp_hipaa_dol',
        'HITRUST': 'emp_hitrust_dol',
        'FISMA':   'emp_fisma_dol'
    };
    const relevantAuditTypes = auditTypesArray.filter(type => supportedAuditTypes.hasOwnProperty(type));
    // Always shows duties in canonical order rather than whatever order
    // they were typed in — mirrors sortDolTags() in dashboard.php and the
    // $sortDolTags closure above (this page renders its Team card
    // server-side, but this modal's own list is JS-rendered, so it needs
    // its own copy). SOC 2 uses the fixed list below; SOC 1 (CO1, CO2, ...)
    // and anything else sorts numerically by whatever digits are in the
    // tag, alphabetical as a fallback.
    const SOC2_DOL_ORDER = ['CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9', 'Availability', 'Confidentiality', 'Processing Integrity', 'Privacy'];
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
    const roleColorVar = { manager: 'var(--manager)', senior: 'var(--senior)', staff: 'var(--staff)', intern: 'var(--intern)' };
    const roleLabels = { manager: 'Manager', senior: 'Senior', staff: 'Staff', intern: 'Intern' };
    // Trims a whole-number ".00" but keeps a half-hour like ".5" — same
    // formatting the read-only Team card uses.
    function fmtHours(hours) {
        if (hours === null || hours === undefined || hours === '') return null;
        const n = parseFloat(hours);
        if (isNaN(n)) return null;
        return n.toFixed(2).replace(/\.?0+$/, '');
    }

    // Split-workspace redesign (see pages/dashboard.php's openManageTeamModal
    // for the fuller write-up — this is that same design, ported here with
    // this page's own local variable names since the two files don't share
    // a module). Roster stays visible on the left; selecting someone (or
    // hitting "Add Team Member") opens their editor on the right, in place.
    // Add/Edit/Remove all patch currentTeam and re-render without a reload —
    // only closing the modal itself reloads, to refresh the page's Team card.
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
            listEl.innerHTML = `<div class="team3-empty-list">No one matches "${filterQuery}".</div>`;
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
        const memberInitials = member.emp_name.split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('');
        const isSelected = mode === 'detail' && String(selectedEmpId) === String(member.emp_id);
        // One line per audit type, each with its own label — matches the
        // server-rendered read-only Team card above rather than flowing
        // every type's chips into one row, which made it hard to tell a
        // SOC 1 duty from a SOC 2 one at a glance (per Garrett).
        const dolLines = roleKey === 'manager' ? '' : relevantAuditTypes.map(auditType => {
            const fieldName = supportedAuditTypes[auditType];
            const duties = (member[fieldName] || '').split(',').map(d => d.trim()).filter(Boolean);
            if (!duties.length) return '';
            const typeClass = auditType === 'SOC 2' || auditType === 'HITRUST' ? 't-soc2' : '';
            const chipsHtml = sortDolTags(duties, auditType).map(d => `<span class="team2-chip ${typeClass}">${d}</span>`).join('');
            return `<div class="team2-dol-line"><span class="team2-dol-type-tag">${auditType}</span><div class="team2-chip-row">${chipsHtml}</div></div>`;
        }).join('');

        return `
            <div class="team3-item ${isSelected ? 'selected' : ''}" data-select="${member.emp_id}">
                <div class="team2-avatar" style="width:30px;height:30px;font-size:11px;background:${roleColorVar[roleKey] || 'var(--ink)'}">${memberInitials}</div>
                <div class="team3-item-info">
                    <div class="team3-item-name">${member.emp_name}</div>
                    <div class="team3-item-sub">${roleLabels[roleKey] || member.role}${fmtHours(member.budgeted_hours) !== null ? ` &middot; ${fmtHours(member.budgeted_hours)} hrs` : ''}</div>
                    ${dolLines ? `<div class="team3-item-dols">${dolLines}</div>` : ''}
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
        const memberInitials = member.emp_name.split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('');
        const dolColumnsHtml = relevantAuditTypes.map(auditType => `
            <div>
                <div class="team2-dol-col-label">${auditType}</div>
                <div class="team2-tag-input-box" data-field="${supportedAuditTypes[auditType]}" data-audit-type="${auditType}">
                    <div class="tags"></div>
                    <input type="text" placeholder="Add duty…">
                </div>
            </div>
        `).join('');
        const isConfirmingRemove = confirmingRemoveId !== null && String(confirmingRemoveId) === String(member.emp_id);

        return `
            <div class="team3-detail-head">
                <div class="team2-avatar-lg" id="team3_detail_avatar" style="background:${roleColorVar[roleKey] || 'var(--ink)'}">${memberInitials}</div>
                <div>
                    <div class="team2-edit-name">${member.emp_name}</div>
                    <div class="team2-edit-role-badge ${roleKey}" id="team3_detail_role_badge">${roleLabels[roleKey] || member.role}</div>
                </div>
            </div>
            <div class="team3-field">
                <label class="team2-edit-label">Role on this Engagement</label>
                <div class="team2-segmented" id="team3_role_segment">
                    ${roleOrder.map(role =>
                        `<button type="button" data-role="${role}" class="${role === roleKey ? 'active' : ''}">${roleLabels[role]}</button>`
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
                        Remove <b>${member.emp_name}</b> from this engagement?
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
            const fieldName = supportedAuditTypes[auditType];
            tagState[fieldName] = (member[fieldName] || '').split(',').map(t => t.trim()).filter(Boolean);
        });

        document.querySelectorAll('#team3_right .team2-tag-input-box').forEach(box => {
            const fieldName = box.dataset.field;
            const auditType = box.dataset.auditType;
            const tagsEl = box.querySelector('.tags');
            const input = box.querySelector('input');

            // Re-sorts into canonical order every render (not just on add)
            // so removing a tag can't leave a stale order behind either —
            // reassigns tagState itself, not just a display copy, so the
            // delete button's index-based splice below stays correct.
            function render() {
                tagState[fieldName] = sortDolTags(tagState[fieldName], auditType);
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

        document.querySelectorAll('#team3_role_segment button').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('#team3_role_segment button').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedRole = btn.dataset.role;
                document.getElementById('team3_detail_avatar').style.background = roleColorVar[selectedRole] || 'var(--ink)';
                const badge = document.getElementById('team3_detail_role_badge');
                badge.className = 'team2-edit-role-badge ' + selectedRole;
                badge.textContent = roleLabels[selectedRole];
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
                const fieldName = supportedAuditTypes[auditType];
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
                    <div class="team2-ac-item" data-emp-name="${e.emp_name}" data-emp-role="${e.emp_role}">
                        <div class="team2-avatar" style="width:22px;height:22px;font-size:9px;background:${roleColorVar[e.emp_role] || 'var(--ink)'}">${e.emp_name.split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('')}</div>
                        ${e.emp_name}
                        <span class="role">${roleLabels[e.emp_role] || e.emp_role}</span>
                    </div>
                `).join('') + `</div>`;
            } else if (onTeamAlready.length) {
                html += `<div class="team2-ac-empty">${onTeamAlready.map(e => e.emp_name).join(', ')} — already on this team.</div>`;
            } else {
                html += `<div class="team2-ac-empty">No employee named "${query}" in the roster.</div>`;
            }
            // Only offer "add as new" when nobody by this name exists at all — if they're
            // already in the roster (whether on this team or not), that'd create a confusing duplicate.
            if (!matches.length && !onTeamAlready.length) {
                html += `<div class="team2-ac-newbtn" id="team3_ac_new_btn">+ Add "${query}" as a new employee…</div>`;
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

        // Renders in the same results area rather than a nested Swal — a
        // second Swal.fire() while "Manage Team Members" is open replaces
        // the shared popup instance, which would close this whole modal
        // and reload the page.
        function renderNewEmployeeRolePicker(name) {
            let selectedRole = 'staff';
            results.innerHTML = `
                <div class="team2-new-emp-picker" style="padding:0;">
                    <div class="team2-ac-empty" style="padding:0 0 8px;">Role for "${name}"?</div>
                    <div class="role-pick-grid" id="team3_new_emp_role_segment">
                        ${roleOrder.map(role =>
                            `<button type="button" data-role="${role}" class="role-pick-btn ${role === selectedRole ? 'active' : ''}">${roleLabels[role]}</button>`
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
});
</script>

</body>
</html>