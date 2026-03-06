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

function getTimelineByEngagement($conn, $engagementId) {
    $stmt = $conn->prepare("
        SELECT *
        FROM engagement_timeline
        WHERE engagement_idno = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $engagementId);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

$timeline = getTimelineByEngagement($conn, $engagementId);

echo $timeline['engagement_idno'];


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
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.25rem;
        }

        .milestone-item {
            background: var(--gray-100);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .milestone-checkbox {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .milestone-checkbox.completed {
            background: rgba(79, 198, 95, 0.2);
            color: var(--success-green);
        }

        .milestone-checkbox.pending {
            background: rgba(220, 220, 220, 0.3);
            color: var(--text-secondary);
            border: 2px solid var(--border-color);
        }

        .milestone-content {
            flex: 1;
        }

        .milestone-title {
            font-size: 12px;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .milestone-title.completed {
            text-decoration: line-through;
            color: var(--text-secondary);
        }

        .milestone-due {
            font-size: 12px;
            color: var(--text-secondary);
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
            body div[style*="grid-template-columns: 1fr 1.5fr 1.5fr"] {
                grid-template-columns: 1fr !important;
            }
        }

        hr {
            margin-left: -2rem !important;
            width: calc(100vw - 1rem) !important;
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
                            <div class="engagement-info-value"><?php echo htmlspecialchars($engagement['eng_audit_type'] ?? 'N/A'); ?></div>
                        </div>
                    </div>

                    <!-- Period -->
                    <div class="engagement-info-item">
                        <div class="engagement-info-icon" style="color: var(--warning-orange);">
                            <i class="bi bi-calendar-event"></i>
                        </div>
                        <div class="engagement-info-content">
                            <div class="engagement-info-label">Period</div>
                            <div class="engagement-info-value">FY 2024</div>
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
                        POC: Tom Wilson
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
    <div style="display: grid; grid-template-columns: 380px 1fr; gap: 2rem; margin-bottom: 3rem; margin-top: 2rem;">

        <!-- ========== TEAM SECTION (LEFT - FULL HEIGHT) ========== -->
        <div class="team-section">
            <div class="section-header" style="margin-bottom: 0; padding-bottom: 1.5rem; border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                <div class="section-header-left">
                    <div class="section-icon team">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <h2 class="section-title">Team</h2>
                </div>
            </div>
            <button class="manage-btn" style="width: 100%; margin-bottom: 1.5rem; margin-top: 1.5rem; justify-content: center;">
                <i class="bi bi-gear"></i> Manage DOL
            </button>

            <div class="team-members">
                <!-- John Smith -->
                <div class="team-member">
                    <div class="team-member-avatar" style="background: linear-gradient(135deg, #4487FC, #4DA6FF);">JS</div>
                    <div class="team-member-info">
                        <div class="team-member-name">John Smith</div>
                        <div class="team-member-title">Manager</div>
                    </div>
                </div>

                <!-- Sarah Johnson -->
                <div class="team-member">
                    <div class="team-member-avatar" style="background: linear-gradient(135deg, #A04DFD, #D67FFF);">SJ</div>
                    <div class="team-member-info">
                        <div class="team-member-name">Sarah Johnson</div>
                        <div class="team-member-title">Senior 1</div>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 0.5rem;">
                            <div style="margin-bottom: 0.3rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Division of Labor</div>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <span class="team-member-tag">CC1</span>
                                <span class="team-member-tag">CC2</span>
                                <span class="team-member-tag">CC3</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- David Martinez -->
                <div class="team-member">
                    <div class="team-member-avatar" style="background: linear-gradient(135deg, #4DBFB8, #6FD9D2);">DM</div>
                    <div class="team-member-info">
                        <div class="team-member-name">David Martinez</div>
                        <div class="team-member-title">Senior 2</div>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 0.5rem;">
                            <div style="margin-bottom: 0.3rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Division of Labor</div>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <span class="team-member-tag">CC4</span>
                                <span class="team-member-tag">CC5</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mike Davis -->
                <div class="team-member">
                    <div class="team-member-avatar" style="background: linear-gradient(135deg, #F17313, #FFB347);">MD</div>
                    <div class="team-member-info">
                        <div class="team-member-name">Mike Davis</div>
                        <div class="team-member-title">Staff 1</div>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 0.5rem;">
                            <div style="margin-bottom: 0.3rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Division of Labor</div>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <span class="team-member-tag">CC6</span>
                                <span class="team-member-tag">CC7</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jennifer White -->
                <div class="team-member">
                    <div class="team-member-avatar" style="background: linear-gradient(135deg, #4FC65F, #7FDD8A);">JW</div>
                    <div class="team-member-info">
                        <div class="team-member-name">Jennifer White</div>
                        <div class="team-member-title">Staff 2</div>
                        <div style="font-size: 12px; color: var(--text-secondary); margin-top: 0.5rem;">
                            <div style="margin-bottom: 0.3rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Division of Labor</div>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <span class="team-member-tag">CC8</span>
                                <span class="team-member-tag">CC9</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== RIGHT COLUMN (Timeline & Milestones Stacked) ========== -->
        <div style="display: grid; grid-template-rows: auto auto; gap: 2rem;">

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
?>
                    <!-- Internal Planning -->
                    <!-- <div class="timeline-item">
    <div class="timeline-title">
        <i class="bi bi-calendar"></i> INTERNAL PLANNING CALL
    </div>

    <?php
    // renderTimelineStatus(
    //     $timeline['internal_planning_call_date'],
    //     $timeline['internal_planning_call_completed_at']
    // );
    ?>
</div> -->

                    <div class="timeline-item">
                        <div style="font-size: 10px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 600; display: flex; align-items: center; gap: 0.4rem;">
                            <i class="bi bi-info-circle" style="font-size: 12px;"></i> INTERNAL PLANNING CALL
                        </div>
                        <!-- <div class="timeline-date">Mar 14, 2026</div>
                        <div class="timeline-status">9d remaining</div> -->
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
                        <span class="milestone-stat">3/6</span>
                        <span class="milestone-progress">50% Complete</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-top: 1.5rem;">
                    <!-- Kickoff meeting completed -->
                    <div class="milestone-item">
                        <div class="milestone-checkbox completed">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="milestone-content">
                            <div class="milestone-title completed">Kickoff meeting completed</div>
                            <div class="milestone-due">Due: Oct 31, 2025</div>
                        </div>
                    </div>

                    <!-- Risk assessment finalized -->
                    <div class="milestone-item">
                        <div class="milestone-checkbox completed">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="milestone-content">
                            <div class="milestone-title completed">Risk assessment finalized</div>
                            <div class="milestone-due">Due: Nov 14, 2025</div>
                        </div>
                    </div>

                    <!-- Fieldwork completed -->
                    <div class="milestone-item">
                        <div class="milestone-checkbox completed">
                            <i class="bi bi-check-circle-fill"></i>
                        </div>
                        <div class="milestone-content">
                            <div class="milestone-title completed">Fieldwork completed</div>
                            <div class="milestone-due">Due: Dec 14, 2025</div>
                        </div>
                    </div>

                    <!-- Draft report prepared -->
                    <div class="milestone-item">
                        <div class="milestone-checkbox pending">
                        </div>
                        <div class="milestone-content">
                            <div class="milestone-title">Draft report prepared</div>
                            <div class="milestone-due">Due: Feb 19, 2026</div>
                        </div>
                    </div>

                    <!-- Management response received -->
                    <div class="milestone-item">
                        <div class="milestone-checkbox pending">
                        </div>
                        <div class="milestone-content">
                            <div class="milestone-title">Management response received</div>
                            <div class="milestone-due">Due: Feb 28, 2026</div>
                        </div>
                    </div>

                    <!-- Final report issued -->
                    <div class="milestone-item">
                        <div class="milestone-checkbox pending">
                        </div>
                        <div class="milestone-content">
                            <div class="milestone-title">Final report issued</div>
                            <div class="milestone-due">Due: Mar 14, 2026</div>
                        </div>
                    </div>
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
</script>

</body>
</html>