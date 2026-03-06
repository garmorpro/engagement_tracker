<?php 
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($engagement['eng_name']); ?> - Engagement Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles/main.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --primary-blue: #4487FC;
            --success-green: #4FC65F;
            --warning-orange: #F17313;
            --danger-red: #C90012;
            --info-purple: #A04DFD;
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
            background: rgba(79, 198, 95, 0.15);
            color: var(--success-green);
        }

        .badge-priority {
            background: rgba(201, 0, 18, 0.15);
            color: var(--danger-red);
        }

        .badge-repeat {
            background: rgba(68, 135, 252, 0.15);
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
            margin-bottom: 1.5rem;
        }

        .sidebar-section:last-child {
            margin-bottom: 0;
        }

        .sidebar-section-title {
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            font-weight: 600;
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
            background: rgba(201, 0, 18, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--danger-red);
            font-size: 24px;
        }

        .critical-date-icon.overdue {
            animation: none;
        }

        .overdue-badge {
            display: inline-block;
            background: rgba(201, 0, 18, 0.15);
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
                box-shadow: 0 0 0 0 rgba(201, 0, 18, 0.7);
            }
            to {
                box-shadow: 0 0 0 8px rgba(201, 0, 18, 0);
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
            background: #3671E0;
            box-shadow: 0 4px 12px rgba(68, 135, 252, 0.3);
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
            background: rgba(68, 135, 252, 0.05);
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
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }

        .section-icon.timeline {
            background: rgba(33, 150, 243, 0.2);
            color: #2196F3;
        }

        .section-icon.milestones {
            background: rgba(160, 77, 253, 0.2);
            color: #A04DFD;
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
            gap: 1rem;
        }

        .team-member {
            background: var(--gray-100);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            gap: 1rem;
        }

        .team-member-avatar {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            flex-shrink: 0;
        }

        .team-member-info {
            flex: 1;
        }

        .team-member-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .team-member-title {
            font-size: 13px;
            color: var(--text-secondary);
        }

        .team-member-tags {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 0.5rem;
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .team-member-tag {
            background: rgba(68, 135, 252, 0.15);
            color: var(--primary-blue);
            padding: 0.3rem 0.6rem;
            border-radius: 4px;
            font-weight: 600;
        }

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

        /* ========== TWO COLUMN LAYOUT FIX ========== */
        .two-column-wrapper {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 2rem;
            margin-bottom: 3rem;
            margin-top: 2rem;
            align-items: start;
        }

        .left-column {
            /* Team section will naturally be on the left */
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
            /* Change 3-column to 2-column layout */
            body div[style*="grid-template-columns: 1fr 1.5fr 1.5fr"] {
                grid-template-columns: 1fr 1fr !important;
            }
        }

        @media (max-width: 768px) {
            main-container {
                padding: 1.5rem;
            }

            engagement-title {
                font-size: 24px;
            }

            engagement-info-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            engagement-meta {
                flex-direction: column;
            }

            top-actions {
                width: 100%;
                order: -1;
            }

            btn-edit {
                flex: 1;
            }

            /* Stack sections vertically on mobile */
            .two-column-wrapper {
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
            width: calc(100vw - 1rem) !important;
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
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.8; }
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
            <button class="btn-edit">
                <i class="bi bi-pencil"></i>
                Edit
            </button>
            <button class="btn-icon">
                <i class="bi bi-archive"></i>
            </button>
            <button class="btn-icon">
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
                        $statusText = str_replace('-', ' ', $engagement['eng_status']);
                        $statusIcon = match($engagement['eng_status']) {
                            'in-progress' => 'bi-play-circle-fill',
                            'planning' => 'bi-clipboard-check',
                            'in-review' => 'bi-search',
                            'complete' => 'bi-check-circle-fill',
                            'archived' => 'bi-archive',
                            default => 'bi-circle'
                        };
                    ?>
                    <span class="engagement-badge badge-status">
                        <i class="bi <?php echo $statusIcon; ?>"></i>
                        <?php echo strtoupper($statusText); ?>
                    </span>
                    <span class="engagement-badge badge-priority">
                        <i class="bi bi-exclamation-circle"></i>
                        High Priority
                    </span>
                    <span class="engagement-badge badge-repeat">
                        <i class="bi bi-arrow-repeat"></i>
                        Repeat
                    </span>
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
                                $end = $engagement['eng_end_period'] ?? null;
                                $asOf = $engagement['eng_as_of_date'] ?? null;

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

                    <!-- Report Type -->
                    <div class="engagement-info-item">
                        <div class="engagement-info-icon" style="color: var(--primary-blue);">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <div class="engagement-info-content">
                            <div class="engagement-info-label">Report Type</div>
                            <div class="engagement-info-value">Type II</div>
                        </div>
                    </div>
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
                <!-- Sidebar -->
                <div class="engagement-sidebar">
                    <!-- Critical Date Section -->
                    <div class="sidebar-section">
                        <!-- Icon and Overdue Badge Row -->
                        <div class="critical-date-wrapper">
                            <div class="critical-date-icon-wrapper">
                                <div class="critical-date-icon">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                            </div>
                            <span class="overdue-badge pulse">Overdue</span>
                        </div>

                        <!-- Main Content Box -->
                        <div class="critical-date">
                            <div class="critical-date-inner">
                                <div class="critical-date-label">Next Critical Date</div>
                                <div class="critical-date-value">37 days</div>
                                <div class="critical-date-status overdue">overdue</div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Section -->
                    <div class="sidebar-section">
                        <div class="sidebar-section-title">Upcoming</div>
                        <div class="upcoming-item">
                            <div class="upcoming-label">Next Milestone</div>
                            <div class="upcoming-title">Leadsheet Due</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr>

    <!-- ========== TWO COLUMN LAYOUT ========== -->
    <div class="two-column-wrapper">

        <!-- ========== TEAM SECTION (LEFT COLUMN) ========== -->
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
                            if (!empty($member['emp_dol'])) {
                                $hasDOL = true;
                                break;
                            }
                        }
                    }
                    ?>

                    <?php if (!$hasDOL): ?>
                        <div style="
                            background-color: #ff4d4f;
                            color: white;
                            font-weight: 600;
                            padding: 0.25rem 0.6rem;
                            border-radius: 0.5rem;
                            font-size: 12px;
                            animation: pulse 1.5s infinite;
                        ">
                            No DOL Set
                        </div>
                    <?php endif; ?>
                </div>

                <button class="manage-btn" style="width: 100%; margin-bottom: 1.5rem; margin-top: 1.5rem; justify-content: center;">
                    <i class="bi bi-gear"></i> Manage DOL
                </button>

                <div class="team-members">
                <?php if (!empty($team)): ?>

                    <?php
                    // -----------------------------
                    // Safely count unique audit types (ignore null/empty)
                    $auditTypes = array_filter(array_column($team, 'audit_type'), fn($v) => !is_null($v) && $v !== '');

                    // Group members by employee name and role
                    $groupedTeam = [];
                    foreach ($team as $member) {
                        $key = $member['emp_name'] . '|' . $member['role'];
                        if (!isset($groupedTeam[$key])) {
                            $groupedTeam[$key] = [
                                'emp_name' => $member['emp_name'],
                                'role' => $member['role'],
                                'audit_types' => []
                            ];
                        }

                        // Only explode emp_dol if it's not empty
                        $dolArray = !empty($member['emp_dol']) ? explode(',', $member['emp_dol']) : [];
                        $auditType = $member['audit_type'] ?? 'General';
                        $groupedTeam[$key]['audit_types'][$auditType] = $dolArray;
                    }

                    // -----------------------------
                    // Sort grouped team by role priority
                    $roleOrder = ['manager' => 1, 'senior' => 2, 'staff' => 3];
                    uasort($groupedTeam, function($a, $b) use ($roleOrder) {
                        $roleA = strtolower($a['role'] ?? '');
                        $roleB = strtolower($b['role'] ?? '');
                        return ($roleOrder[$roleA] ?? 999) <=> ($roleOrder[$roleB] ?? 999);
                    });
                    ?>

                    <?php foreach ($groupedTeam as $member): ?>
                        <?php
                            // Get initials
                            $nameParts = explode(' ', $member['emp_name']);
                            $initials = '';
                            foreach ($nameParts as $part) { $initials .= strtoupper($part[0]); }

                            // Gradient colors by role
                            $gradient = 'linear-gradient(135deg, #4487FC, #4DA6FF)'; // default
                            switch (strtolower($member['role'] ?? '')) {
                                case 'manager': $gradient = 'linear-gradient(135deg, #4487FC, #4DA6FF)'; break;
                                case 'senior': $gradient = 'linear-gradient(135deg, #A04DFD, #D67FFF)'; break;
                                case 'staff': $gradient = 'linear-gradient(135deg, #4FC65F, #7FDD8A)'; break;
                            }
                        ?>
                        <div class="team-member" style="display: flex; gap: 0.75rem; align-items: flex-start;">
                            <div class="team-member-avatar" style="background: <?php echo $gradient; ?>; flex-shrink: 0;"><?php echo htmlspecialchars($initials); ?></div>
                            <div class="team-member-info" style="flex: 1; min-width: 0;">
                                <div class="team-member-name"><?php echo htmlspecialchars($member['emp_name']); ?></div>
                                <div class="team-member-title"><?php echo htmlspecialchars($member['role']); ?></div>

                                <?php if (!empty($member['audit_types'])): ?>
                                    <?php foreach ($member['audit_types'] as $auditType => $tags): ?>
                                        <?php if (!empty($tags)): ?>
                                            <div style="font-size: 12px; color: var(--text-secondary); margin-top: 0.5rem; overflow-wrap: break-word; word-wrap: break-word;">
                                                <div style="margin-bottom: 0.3rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                                    <?php echo htmlspecialchars($auditType); ?> DOL
                                                </div>
                                                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                                                    <?php foreach ($tags as $tag): ?>
                                                        <span class="team-member-tag" style="white-space: normal; overflow-wrap: break-word;"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <div class="no-team-members">
                        <i class="bi bi-exclamation-triangle-fill" style="margin-right: 0.3rem;"></i>
                        No team assigned yet.
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- ========== RIGHT COLUMN (Timeline & Milestones Stacked) ========== -->
        <div class="right-column">

            <!-- ========== TIMELINE & KEY DATES SECTION (TOP RIGHT) ========== -->
            <div class="timeline-section">
                <div class="section-header" style="margin-bottom: 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div class="section-header-left">
                        <div class="section-icon timeline">
                            <i class="bi bi-calendar2"></i>
                        </div>
                        <h2 class="section-title">Timeline & Key Dates</h2>
                    </div>
                    <button class="manage-btn" style="margin: 0; padding: 0.5rem 1rem;">
                        <i class="bi bi-gear"></i> Manage
                    </button>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; margin-top: 1.5rem;">

                <?php
// Your existing renderTimelineStatus function stays exactly the same
function renderTimelineStatus($date, $completed) {

    if (!$date) {
        echo '<div class="timeline-date">—</div>';
        echo '<div class="timeline-status">Not scheduled</div>';
        return;
    }

    $formattedDate = date("M j, Y", strtotime($date));

    $today = new DateTime();
    $dueDate = new DateTime($date);

    $diff = $today->diff($dueDate);
    $days = $diff->days;

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
        'internal_planning_call_date' => null,
        'internal_planning_call_completed_at' => null,
        'irl_due_date' => null,
        'irl_completed_at' => null,
        'client_planning_call_date' => null,
        'client_planning_call_completed_at' => null,
        'fieldwork_date' => null,
        'fieldwork_completed_at' => null,
        'leadsheet_date' => null,
        'leadsheet_completed_at' => null,
        'draft_report_due_date' => null,
        'draft_report_completed_at' => null,
        'final_report_date' => null,
        'final_report_completed_at' => null,
        'archive_date' => null,
        'archive_completed_at' => null
    ];
}

?>

                    <div class="timeline-item">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-info-circle" style="font-size: 12px;"></i> INTERNAL PLANNING CALL
                        </div>
                        <?php
                        renderTimelineStatus(
                            $timeline['internal_planning_call_date'],
                            $timeline['internal_planning_call_completed_at']
                        );
                        ?>
                    </div>

                    <!-- URL Due -->
                    <div class="timeline-item">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-info-circle" style="font-size: 12px;"></i> IRL DUE
                        </div>
                        <?php
                        renderTimelineStatus(
                            $timeline['irl_due_date'],
                            $timeline['irl_completed_at']
                        );
                        ?>
                    </div>

                    <!-- Client Planning Call -->
                    <div class="timeline-item">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-calendar" style="font-size: 12px;"></i> CLIENT PLANNING CALL
                        </div>
                        <?php
                        renderTimelineStatus(
                            $timeline['client_planning_call_date'],
                            $timeline['client_planning_call_completed_at']
                        );
                        ?>
                    </div>

                    <!-- Fieldwork -->
                    <div class="timeline-item">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-bar-chart" style="font-size: 12px;"></i> FIELDWORK
                        </div>
                        <?php
                        renderTimelineStatus(
                            $timeline['fieldwork_date'],
                            $timeline['fieldwork_completed_at']
                        );
                        ?>
                    </div>

                    <!-- Leadsheet Due -->
                    <div class="timeline-item">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-info-circle" style="font-size: 12px;"></i> LEADSHEET DUE
                        </div>
                        <?php
                        renderTimelineStatus(
                            $timeline['leadsheet_date'],
                            $timeline['leadsheet_completed_at']
                        );
                        ?>
                    </div>

                    <!-- Draft Report Due -->
                    <div class="timeline-item">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-file-text" style="font-size: 12px;"></i> DRAFT REPORT DUE
                        </div>
                        <?php
                        renderTimelineStatus(
                            $timeline['draft_report_due_date'],
                            $timeline['draft_report_completed_at']
                        );
                        ?>
                    </div>

                    <!-- Final Report Due -->
                    <div class="timeline-item">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-file-earmark" style="font-size: 12px;"></i> FINAL REPORT DUE
                        </div>
                        <?php
                        renderTimelineStatus(
                            $timeline['final_report_date'],
                            $timeline['final_report_completed_at']
                        );
                        ?>
                    </div>

                    <!-- Archive Date -->
                    <div class="timeline-item">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-archive" style="font-size: 12px;"></i> ARCHIVE DATE
                        </div>
                        <?php
                        renderTimelineStatus(
                            $timeline['archive_date'],
                            $timeline['archive_completed_at']
                        );
                        ?>
                    </div>
                </div>
            </div>

            <!-- ========== MILESTONES SECTION (BOTTOM RIGHT) ========== -->
            <div class="milestones-section">
                <div class="section-header" style="margin-bottom: 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <div class="section-header-left">
                        <div class="section-icon milestones">
                            <i class="bi bi-flag-fill"></i>
                        </div>
                        <h2 class="section-title">Milestones</h2>
                    </div>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <button class="manage-btn" style="margin: 0; padding: 0.5rem 1rem;">
                            <i class="bi bi-gear"></i> Manage
                        </button>
                        <?php
                        if (!empty($milestones)) {
                            $completedCount = 0;
                            foreach ($milestones as $milestone) {
                                if ($milestone['is_completed'] === 'Y') {
                                    $completedCount++;
                                }
                            }
                            $totalCount = count($milestones);
                            $percentComplete = round(($completedCount / $totalCount) * 100);
                        } else {
                            $completedCount = 0;
                            $totalCount = 0;
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
                        // Calculate completed milestones
                        $completedCount = 0;
                        foreach ($milestones as $milestone) {
                            if (!empty($milestone['milestone_completed_at'])) {
                                $completedCount++;
                            }
                        }
                        $totalCount = count($milestones);
                        $percentComplete = $totalCount > 0 ? round(($completedCount / $totalCount) * 100) : 0;
                        ?>
                        <?php foreach ($milestones as $milestone): ?>
                            <?php
                                $isCompleted = ($milestone['is_completed'] === 'Y');
                                
                                // Safely handle the date - check for null, empty, or invalid dates
                                $dueDateRaw = $milestone['due_date'] ?? null;
                                if ($dueDateRaw && $dueDateRaw !== '0000-00-00' && $dueDateRaw !== '0000-00-00 00:00:00') {
                                    $dueDate = date("M j, Y", strtotime($dueDateRaw));
                                } else {
                                    $dueDate = 'No due date';
                                }
                            ?>
                            <div class="milestone-item">
                                <div class="milestone-checkbox <?php echo $isCompleted ? 'completed' : 'pending'; ?>">
                                    <?php if ($isCompleted): ?>
                                        <i class="bi bi-check-circle-fill"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="milestone-content">
                                    <div class="milestone-title <?php echo $isCompleted ? 'completed' : ''; ?>">
                                        <?php echo htmlspecialchars($milestone['milestone_type']); ?>
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
            </div>

        </div>

    </div>

    <!-- Dark Mode Button -->
    <button class="dark-mode-btn" id="darkModeBtn" title="Toggle dark mode">
        <i class="bi bi-moon"></i>
    </button>

</div>

<script>
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

    // Initialize dark mode from localStorage
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    if (isDarkMode) {
        document.body.classList.add('dark-mode');
    }
    updateDarkModeIcon(isDarkMode);

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
            
            const milestoneItem = this.closest('.milestone-item');
            const milestoneTitle = milestoneItem?.querySelector('.milestone-title')?.textContent?.trim();
            
            if (!milestoneTitle) return;

            // Get current state (if 'completed' class exists, it's currently completed)
            const isCurrentlyCompleted = this.classList.contains('completed');
            const newStatus = isCurrentlyCompleted ? 'N' : 'Y';

            try {
                const response = await fetch('../api/update-milestone.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        engagement_id: '<?php echo htmlspecialchars($engagementId); ?>',
                        milestone_type: milestoneTitle,
                        is_completed: newStatus
                    })
                });

                const data = await response.json();

                if (data.success) {
                    // Toggle the visual state
                    this.classList.toggle('completed');
                    this.classList.toggle('pending');
                    
                    // Toggle the icon
                    const icon = this.querySelector('i');
                    if (icon) {
                        if (newStatus === 'Y') {
                            icon.classList.add('bi-check-circle-fill');
                        } else {
                            icon.classList.remove('bi-check-circle-fill');
                        }
                    }

                    // Update milestone title strikethrough
                    const title = milestoneItem?.querySelector('.milestone-title');
                    if (title) {
                        if (newStatus === 'Y') {
                            title.classList.add('completed');
                        } else {
                            title.classList.remove('completed');
                        }
                    }

                    // Refresh the page to update completion stats
                    location.reload();
                } else {
                    alert('Error updating milestone: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update milestone');
            }
        });
    });
</script>

</body>
</html>