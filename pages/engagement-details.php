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
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            margin-bottom: 1.5rem;
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
            padding: 0.65rem 1.25rem;
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
        @media (max-width: 1024px) {
            .page-header {
                flex-direction: column;
            }

            .engagement-header {
                flex-direction: column;
            }

            .engagement-right {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                width: 100%;
            }

            .engagement-sidebar {
                width: 100%;
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
        }

   hr {
    margin: 0 !important;
    padding: 0 !important;
    width: 100vw !important;
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

    <p>
        Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam rhoncus euismod enim, ut commodo orci commodo venenatis. Nam erat tortor, mollis vitae ex interdum, egestas tincidunt turpis. Fusce aliquet est quis ante euismod ultricies. Morbi nibh ligula, porta et fermentum ac, dignissim in lacus. Donec quis accumsan ante, eget feugiat ante. Mauris efficitur odio magna, at auctor libero rhoncus quis. Aliquam erat volutpat. Proin bibendum elit at ligula sagittis, id interdum velit porttitor. Praesent venenatis arcu ac metus ultrices rutrum. Suspendisse potenti. Donec orci neque, auctor in metus et, mollis lacinia purus. Maecenas a augue eros. Praesent ullamcorper ultricies felis eget facilisis. Donec sodales est sed ipsum elementum tristique. Aenean at diam consectetur, luctus risus nec, fringilla ligula. Nam sit amet leo lectus. Integer pulvinar erat orci, sed tempus dui faucibus sit amet. Nam in nibh consectetur ex vehicula luctus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Curabitur ornare consequat lorem, eu aliquet magna sollicitudin ut. Sed elementum, nibh a dapibus condimentum, sapien magna maximus odio, eget scelerisque dolor erat eget augue. Aliquam consequat ligula et felis imperdiet blandit. Morbi vehicula arcu vitae felis eleifend, ac ornare ligula luctus. Vivamus vehicula lectus sed eleifend sollicitudin. Etiam vel odio eu ligula sollicitudin tristique ut nec metus. In fringilla sit amet purus at gravida. Vestibulum in tortor vitae mi sagittis cursus sed eget purus. Aenean ut augue dui. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Suspendisse vitae quam finibus metus posuere consequat. Curabitur at egestas lacus. Nam vel quam ante. Aliquam non justo vestibulum, ultricies velit a, tempor metus. Vestibulum ante ipsum primis in faucibus orci luctus et ultrices posuere cubilia curae; Suspendisse bibendum vulputate tortor, non sollicitudin nisi facilisis in. Donec bibendum sed diam venenatis tempus. Nam dictum tempor lectus sed feugiat. Fusce sollicitudin, purus nec scelerisque dapibus, lectus orci ornare metus, ut faucibus massa eros nec mauris. Sed volutpat est nec aliquam sodales. Curabitur tincidunt sodales tellus et varius. Nulla ex lacus, commodo nec turpis quis, egestas imperdiet nibh. Integer vel tortor libero. Fusce id sollicitudin felis. Proin sapien est, vehicula in libero quis, malesuada sollicitudin lacus. Maecenas augue enim, rhoncus vel eleifend ut, molestie nec quam. Nunc ullamcorper facilisis augue ac tincidunt. Aliquam at volutpat sem. Vestibulum finibus, felis sed ultrices convallis, ligula erat finibus elit, vitae accumsan risus sapien non felis. Curabitur aliquam arcu a elementum ultricies. Phasellus auctor mi sit amet pellentesque vehicula. Vivamus vitae vestibulum augue. Aenean rhoncus et odio ac malesuada. Vestibulum vitae leo a dolor iaculis semper. Nunc auctor tellus id quam luctus, eget tempus arcu elementum. Ut sodales lorem at quam luctus convallis. Phasellus a mattis erat, at accumsan velit. In nec molestie orci, vel malesuada velit. Phasellus id bibendum est, in vestibulum nunc. Etiam tincidunt tellus ut turpis vulputate eleifend. Fusce malesuada posuere nunc et vestibulum. Vivamus sed cursus erat, vitae laoreet lorem. In eu iaculis velit. Donec tristique tellus eu nunc pretium, consequat pharetra lacus pharetra. Donec ultricies enim ut nulla lobortis, tempor vehicula urna convallis. Aenean ut facilisis tellus. Sed faucibus lorem a egestas consequat. Quisque sagittis aliquet pellentesque. In rutrum nulla dui, nec mattis mauris pharetra et. Sed et nunc tristique, facilisis elit ut, pretium urna. Proin pretium tortor metus, sed varius lorem interdum eget. Mauris sem felis, fringilla vel condimentum sed, interdum sed ex. Nullam egestas varius massa, eu aliquam enim aliquam sed. Maecenas faucibus odio sed lectus laoreet auctor. In molestie velit a rhoncus cursus. Nulla lacinia nisl ipsum, eget dictum elit efficitur non. Vivamus fringilla, dui luctus consequat tempor, nisl risus cursus massa, a ullamcorper eros libero in ante. Nulla scelerisque scelerisque risus eu laoreet. Nullam consequat sapien enim, eget tempor odio imperdiet et. Fusce blandit sagittis vulputate. Praesent vel arcu id velit varius volutpat in ac magna. Fusce vitae fringilla tortor. Mauris tincidunt posuere lacus vitae placerat. Curabitur ut mauris vitae ex tincidunt scelerisque non vel mi. Vestibulum auctor nisl venenatis, sodales elit et, egestas lectus. Duis suscipit pellentesque lorem, nec posuere ipsum tincidunt a. Nunc vulputate ornare justo quis volutpat. In nec felis varius nisi mollis fermentum finibus vitae nunc. Nam vel nibh fermentum, molestie tortor vel, volutpat metus. Donec commodo, nibh nec tristique rhoncus, urna nisi aliquet dui, id congue tellus enim eleifend diam. Suspendisse vehicula nisi eu arcu lobortis pellentesque. Praesent sed cursus mi. Donec bibendum metus eget sodales tincidunt. Mauris quis erat et tortor sodales semper. Donec tincidunt, sem eget dignissim sollicitudin, dolor magna blandit enim, eget pellentesque dui nisi in nisi. Maecenas a ullamcorper nulla, at rhoncus libero. Pellentesque commodo elit vel diam egestas commodo. Ut quis mi placerat, dictum urna sed, congue tellus. Mauris interdum pretium augue sit amet molestie. Etiam vel metus vitae nisl sagittis malesuada eget sed lacus. Aenean quis congue arcu. Phasellus feugiat quis tortor quis finibus. Nulla semper ultricies sapien non dapibus. Fusce pulvinar consectetur urna, a mattis orci condimentum et. Vivamus magna nulla, aliquam a ullamcorper vulputate, semper vel magna. Fusce et imperdiet mauris. Sed volutpat pellentesque ex, ac lacinia dui consectetur vel. Donec a urna quam. In in diam sit amet odio cursus congue sit amet id ante. Praesent at facilisis est. Etiam cursus egestas dictum. Donec venenatis dignissim enim, eu tempus dolor volutpat non. Donec dictum euismod risus, vel tristique diam pharetra id. Sed pellentesque, ligula dignissim euismod tristique, lectus orci venenatis mi, id mattis nisl mi ac risus. Aenean dapibus rutrum est nec dignissim. Nulla suscipit at diam sed condimentum. Integer dictum commodo ultricies. Class aptent taciti sociosqu ad litora torquent per conubia nostra, per inceptos himenaeos. Nullam ultrices, diam a luctus tempor, arcu orci condimentum justo, in dapibus nunc tellus laoreet erat. Cras hendrerit bibendum hendrerit. Sed gravida lorem eget lacus fringilla, vitae faucibus lorem pharetra. Sed non velit ac est finibus lobortis nec vitae purus. Suspendisse potenti. Nullam augue justo, feugiat non dapibus at, convallis at turpis. Proin ultricies suscipit feugiat. Donec efficitur feugiat enim sit amet venenatis. Donec molestie quis eros et pellentesque. Vestibulum in suscipit eros, nec pretium quam. Curabitur scelerisque leo ut dolor varius placerat. Pellentesque massa libero, aliquet ut lectus pharetra, pretium pulvinar eros. Proin condimentum turpis eu pulvinar blandit. Sed vel mi pulvinar, tristique purus sit amet, varius risus. Phasellus et nunc neque. Sed hendrerit condimentum orci vitae auctor. Nulla consequat sagittis tortor, sed auctor quam interdum ut. Suspendisse sit amet nisi fringilla tellus lacinia vulputate. Proin sodales tellus risus, non dapibus metus ultricies id. Morbi mollis vitae neque aliquam convallis. Mauris dictum nisi elit. Sed non congue tellus, consequat posuere libero. Proin efficitur aliquam turpis at elementum. In ligula erat, egestas in mattis quis, commodo ut neque. Suspendisse pellentesque mattis lectus. Nam vel eros viverra erat interdum auctor eget eget dolor. Donec nec lectus vel nisl efficitur blandit. Nullam vitae nibh id mi tempus commodo ac quis nibh. Vestibulum pellentesque ex in egestas sodales. Donec varius, odio a pulvinar hendrerit, quam elit tempor sem, et pellentesque nunc lorem sit amet lectus. Proin auctor pretium tellus, vitae dignissim nisl hendrerit in. Praesent fermentum eu metus in dapibus. Sed ligula purus, fermentum ac libero in, placerat auctor lacus. Nunc pharetra est pharetra aliquam gravida. Sed at fermentum dui. Phasellus ac quam est. Mauris sollicitudin dolor eget erat tempus viverra. Sed venenatis risus et convallis faucibus. Aliquam scelerisque erat lectus, in efficitur tellus luctus quis. Nulla neque justo, lacinia eget enim nec, imperdiet venenatis ante. In ornare tellus nec dolor blandit porttitor. Nulla pretium neque sed semper efficitur. Nam molestie, est lobortis porttitor ultrices, lorem leo luctus felis, eu porta nunc diam in elit. Nulla vulputate, dui quis tempus facilisis, risus augue placerat odio, eu maximus libero felis blandit dolor. Sed auctor maximus sem ac sodales. Sed vulputate eget erat ut convallis. Phasellus porta neque quis vulputate tincidunt. Donec accumsan nulla molestie commodo feugiat. Proin commodo, risus non porta viverra, libero lectus aliquam velit, in accumsan quam leo eget risus. Nulla tristique urna eget ligula varius, at mollis enim lacinia. In hac habitasse platea dictumst. Donec posuere dui eu neque auctor, vel fermentum arcu varius. Nullam vel ex congue, varius felis ac, euismod erat. Donec finibus vitae orci id ultrices. Praesent ut eros rutrum, elementum enim ut, euismod est. Donec sagittis faucibus augue. Praesent suscipit laoreet cursus. Donec bibendum augue vel cursus pharetra. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Cras ut justo lectus. Donec sed orci rutrum, finibus justo sed, vehicula libero. Sed at ex augue. Duis blandit tincidunt sapien sed mattis. Pellentesque non tristique magna. Vivamus purus dui, pretium non quam vitae, laoreet ornare dolor. Sed vitae risus orci. In malesuada ex ut nibh commodo, id imperdiet elit sollicitudin. Orci varius natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. In iaculis, odio luctus tristique porta, felis ligula varius diam, nec lacinia enim ligula non enim. Curabitur a massa sed risus molestie fermentum quis id quam. Maecenas vulputate aliquet porttitor. Vivamus et porta nunc, at ultrices velit. Mauris at ante nibh. Donec sit amet ipsum aliquet eros pulvinar feugiat at a quam. Nunc tincidunt tortor in tortor vestibulum, vitae feugiat mi condimentum. Donec cursus ipsum sed massa faucibus maximus. Ut suscipit tellus sit amet mi elementum interdum. Sed justo eros, bibendum eget pharetra efficitur, sodales tempus diam. Etiam venenatis, risus vel viverra facilisis, arcu ipsum venenatis erat, eu ullamcorper urna sapien accumsan metus. Curabitur efficitur purus quis mattis euismod. Nullam tincidunt, odio quis scelerisque fringilla, nibh mi facilisis dolor, et vulputate leo elit eget orci. Vivamus est nulla, posuere ac eleifend quis, sodales et neque. Quisque feugiat, nulla in faucibus tincidunt, velit ante porta turpis, nec hendrerit lacus tortor semper ipsum. Donec sed tortor tincidunt nunc molestie congue sed et nisl. Etiam nec fermentum dolor, vitae porttitor ex. Nullam facilisis tempor dui eu posuere. Donec sollicitudin arcu vel efficitur mollis. Aenean aliquam nisl sit amet efficitur pretium. Suspendisse suscipit nunc facilisis, iaculis nibh id, suscipit sapien. Donec ipsum mauris, pharetra bibendum sem at, volutpat viverra nisl. Nullam mauris turpis, commodo nec tellus quis, ullamcorper varius ipsum. Mauris pharetra sem ante, posuere euismod eros iaculis id. Nullam sit amet facilisis lectus. Praesent dignissim viverra nisl, vel laoreet est faucibus at. Donec at nulla consequat, posuere est non, molestie massa. Duis nec eleifend turpis. Maecenas vulputate ipsum vitae risus feugiat maximus. Aenean vitae bibendum nisl. Nullam quis lacus quis dolor ultricies dictum. Nunc id enim molestie, efficitur leo id, porta lacus. Mauris volutpat mi vitae lorem porta dignissim. Donec feugiat, odio non eleifend laoreet, orci dui rhoncus ipsum, eget fermentum turpis eros eu massa. Vestibulum ut dolor eu dui fringilla scelerisque. Nullam iaculis nulla sed est ullamcorper sodales. Pellentesque sem velit, commodo id faucibus ac, porttitor id sapien. Nunc efficitur sem sit amet lacinia blandit. Aenean euismod felis ut vehicula euismod. Cras enim lorem, venenatis quis sodales quis, ultrices in augue. Mauris tortor magna, luctus eget pellentesque a, mattis at eros. Donec in nunc luctus, tincidunt nisl at, vulputate diam. Sed vehicula pulvinar elit id consequat. Vivamus malesuada tristique elit vel blandit. Fusce quis lorem id lectus lobortis semper at pretium urna. Integer in dolor vitae mauris auctor accumsan quis non magna. Morbi justo massa, condimentum quis vulputate id, imperdiet et purus. Fusce accumsan mi orci, nec tristique quam commodo a. Vestibulum lobortis id elit sed tempus. Sed luctus malesuada metus cursus sodales. Fusce tempus nisl dolor, ac accumsan ante vestibulum sed. Nullam in pharetra orci, id scelerisque metus. Fusce massa mi, venenatis sed viverra sit amet, tristique in sapien. Mauris eros nisi, lobortis ut orci sit amet, aliquam ornare nisi. Nulla euismod lacus eu mollis fringilla. Quisque eget massa vitae erat tincidunt ultricies. Proin a ligula et eros facilisis luctus. Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. Mauris elit augue, dictum ut iaculis suscipit, mattis a urna. Mauris placerat, mi a vestibulum auctor, tellus leo sagittis metus, a rhoncus risus enim sed dui. Nam faucibus sem sed justo tristique bibendum. Cras et ipsum eget leo consequat pharetra. Cras nec vehicula magna. Vivamus malesuada purus ac pellentesque commodo. Proin ac neque vel massa gravida cursus id ac ante. Vivamus dictum euismod justo quis sagittis. Nunc vehicula erat at sapien lobortis, a interdum mi semper. Morbi eu enim blandit, maximus sapien vitae, consectetur augue. Curabitur varius lacinia ipsum ac imperdiet. Morbi non tincidunt nibh. Aenean tincidunt, velit non aliquam mollis, massa tellus sollicitudin elit, a pretium velit arcu in eros. Aliquam facilisis, lacus ac feugiat laoreet, enim libero commodo nibh, vitae imperdiet dolor nisl et metus. Duis finibus augue non magna rutrum ultricies. Ut finibus erat at cursus iaculis. Ut sit amet tellus id nulla efficitur malesuada. Donec mattis ipsum non mauris blandit, eget tincidunt quam pretium. Sed luctus porta velit, non lacinia dolor iaculis a. Integer et odio non tellus dapibus placerat. Nunc eget orci in augue ultrices scelerisque. Sed eget placerat ipsum. Aliquam nec metus vel enim mollis elementum ut eu nulla. Curabitur tempus eros vitae interdum luctus. Suspendisse potenti. Morbi a nibh auctor, feugiat ante non, laoreet nisi. Mauris iaculis turpis luctus ipsum varius imperdiet. Nulla facilisi. Cras posuere suscipit elit a malesuada. In ac dolor eget diam rutrum porttitor a at nulla. Quisque ultrices ante eu eros fringilla dapibus. Nulla placerat diam id sem porta consequat. Maecenas ut ex at est egestas laoreet ut id justo. Integer et auctor urna. Vestibulum tellus ex, commodo sed est vitae, fermentum suscipit arcu. Nam non elementum diam. Pellentesque sed erat risus. Donec sit amet nibh et odio suscipit euismod quis ac tellus. Aenean placerat dolor in quam dignissim, a tempor urna commodo. Nullam lectus turpis, faucibus sit amet dui a, blandit gravida metus. In imperdiet ac odio in mollis. Ut et cursus ligula, quis sodales odio. Proin vitae sem eu quam mattis commodo.
    </p>

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