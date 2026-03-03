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

// Calculate archive count
$archiveCount = count(array_filter($allEngagements, fn($e) => $e['eng_status'] === 'archived'));
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

        /* ========== HEADER ========== */
        .top-header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 0.75rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 56px;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 3rem;
            flex: 1;
        }

        .header-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
            text-decoration: none;
            white-space: nowrap;
        }

        .logo-icon {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--info-purple) 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            flex-shrink: 0;
        }

        .header-title {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.2;
        }

        .header-subtitle {
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.2;
        }

        .header-nav {
            display: flex;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-item {
            font-size: 14px;
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s;
            position: relative;
            white-space: nowrap;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            border-bottom: none;
        }

        .nav-item:hover {
            color: var(--text-primary);
        }

        .nav-item.active {
            color: var(--primary-blue);
            background-color: rgba(68, 135, 252, 0.1);
            border-bottom: none;
        }

        .nav-item .badge {
            background: var(--gray-200) !important;
            color: var(--text-dark) !important;
            margin-left: 0.5rem;
            padding: 0 !important;
            font-size: 11px !important;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            font-weight: 700;
            min-width: 20px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-left: auto;
        }

        .header-icons {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            padding-right: 1.5rem;
            border-right: 1px solid var(--border-color);
        }

        .profile-section {
            display: flex;
            align-items: center;
            position: relative;
        }

        .icon-btn {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 18px;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s;
            position: relative;
        }

        .icon-btn:hover {
            color: var(--text-primary);
        }

        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            width: 20px;
            height: 20px;
            background: #F33;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 700;
        }

        .profile-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--info-purple) 100%);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            position: relative;
        }

        .profile-btn:hover {
            box-shadow: 0 4px 12px rgba(68, 135, 252, 0.3);
        }

        .profile-wrapper {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            position: relative;
        }

        .profile-dropdown-toggle {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }

        .profile-dropdown-toggle:hover {
            color: var(--text-primary);
        }

        .profile-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            min-width: 240px;
            margin-top: 0.75rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s;
            z-index: 1000;
            padding: 0;
            overflow: hidden;
        }

        .profile-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-dropdown-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
        }

        .profile-dropdown-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--info-purple) 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }

        .profile-dropdown-info {
            flex: 1;
        }

        .profile-dropdown-name {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            margin-bottom: 0.25rem;
        }

        .profile-dropdown-email {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .profile-dropdown-menu {
            padding: 0.75rem 0;
        }

        .profile-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 1rem;
            color: var(--text-primary);
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.2s;
            cursor: pointer;
            background: none;
            border: none;
            width: 100%;
            text-align: left;
        }

        .profile-dropdown-item:hover {
            background-color: var(--gray-100);
        }

        .profile-dropdown-item.logout {
            color: var(--danger-red);
            border-top: 1px solid var(--border-color);
            margin-top: 0.25rem;
            padding-top: 0.65rem;
        }

        .profile-dropdown-item.logout:hover {
            background-color: rgba(201, 0, 18, 0.05);
        }

        .profile-dropdown-item i {
            font-size: 16px;
            color: var(--text-secondary);
            width: 18px;
        }

        .profile-dropdown-item.logout i {
            color: var(--danger-red);
        }

        /* ========== MAIN CONTENT ========== */
        .main-container {
            padding: 2rem;
            margin: 0 auto;
        }

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
            align-items: flex-start;
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .engagement-header-left {
            flex: 1;
        }

        .engagement-header-right {
            width: 300px;
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
            gap: 2rem 3rem;
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

        /* ========== RIGHT SIDEBAR ========== */
        .engagement-sidebar {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1.25rem;
            position: sticky;
            top: 100px;
        }

        .sidebar-action-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .btn-edit {
            flex: 1;
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 0.65rem 1rem;
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

        .sidebar-section {
            margin-bottom: 1.25rem;
        }

        .sidebar-section:last-child {
            margin-bottom: 0;
        }

        .sidebar-section-title {
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.6rem;
            font-weight: 600;
        }

        .critical-date {
            text-align: center;
            padding: 1rem;
            background: var(--gray-100);
            border-radius: 8px;
        }

        .critical-date-icon {
            width: 44px;
            height: 44px;
            margin: 0 auto 0.5rem;
            border-radius: 50%;
            background: rgba(201, 0, 18, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--danger-red);
            font-size: 22px;
        }

        .critical-date-label {
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }

        .critical-date-value {
            font-size: 22px;
            font-weight: 700;
            color: var(--danger-red);
            margin-bottom: 0.2rem;
        }

        .critical-date-status {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .upcoming-item {
            padding: 0.9rem;
            background: var(--gray-100);
            border-radius: 8px;
            text-align: center;
        }

        .upcoming-label {
            font-size: 10px;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }

        .upcoming-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
        }
    </style>
</head>
<body>

<!-- ========== HEADER ========== -->
<div class="top-header">
    <div class="header-content">
        <div class="header-left">
            <a href="#" class="header-logo">
                <div class="logo-icon">
                    <i class="bi bi-bar-chart-fill"></i>
                </div>
                <div>
                    <div class="header-title">Engagement Pro</div>
                    <div class="header-subtitle">Light Edition</div>
                </div>
            </a>

            <div class="header-nav">
                <a href="dashboard.php" class="nav-item">Dashboard</a>
                <a href="#" class="nav-item">Analytics</a>
                <a href="archive.php" class="nav-item">Archive <span class="badge"><?php echo $archiveCount; ?></span></a>
            </div>
        </div>

        <div class="header-right">
            <div class="header-icons">
                <button class="icon-btn" title="Dark mode">
                    <i class="bi bi-moon"></i>
                </button>
                <button class="icon-btn" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <div class="notification-badge">2</div>
                </button>
                <button class="icon-btn" title="Settings">
                    <i class="bi bi-gear"></i>
                </button>
            </div>

            <div class="profile-section">
                <div class="profile-wrapper" id="profileToggle">
                    <button class="profile-btn" title="Profile">A</button>
                    <button class="profile-dropdown-toggle">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>

                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                        <div class="profile-dropdown-avatar">JD</div>
                        <div class="profile-dropdown-info">
                            <div class="profile-dropdown-name">John Doe</div>
                            <div class="profile-dropdown-email">john.doe@company.com</div>
                        </div>
                    </div>

                    <div class="profile-dropdown-menu">
                        <a href="#" class="profile-dropdown-item">
                            <i class="bi bi-person-circle"></i> My Profile
                        </a>
                        <a href="#" class="profile-dropdown-item">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        <a href="#" class="profile-dropdown-item">
                            <i class="bi bi-question-circle"></i> Help & Support
                        </a>
                        <a href="#" class="profile-dropdown-item logout">
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
    <!-- Back Button -->
    <a href="dashboard.php" class="back-button">
        <i class="bi bi-chevron-left"></i>
        Dashboard
    </a>

    <!-- Engagement Header -->
    <div class="engagement-header">
        <!-- Left Side -->
        <div class="engagement-header-left">
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

        <!-- Right Sidebar -->
        <div class="engagement-header-right">
            <div class="engagement-sidebar">
                <!-- Action Buttons -->
                <div class="sidebar-action-buttons">
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

                <!-- Critical Date Section -->
                <div class="sidebar-section">
                    <div class="sidebar-section-title">Next Critical Date</div>
                    <div class="critical-date">
                        <div class="critical-date-icon">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div class="critical-date-value">37 days</div>
                        <div class="critical-date-status">overdue</div>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Dark mode toggle
    const darkModeBtn = document.querySelector('.icon-btn[title="Dark mode"]');
    
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

    // Profile dropdown toggle
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');

    profileToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown?.classList.toggle('active');
    });

        /* ========== RESPONSIVE ========== */
        @media (max-width: 1024px) {
            .engagement-header {
                flex-direction: column;
            }

            .engagement-header-right {
                width: 100%;
                position: static;
            }

            .engagement-sidebar {
                position: static;
            }
        }

        @media (max-width: 768px) {
            .engagement-title {
                font-size: 24px;
            }

            .engagement-info-grid {
                grid-template-columns: 1fr;
            }

            .engagement-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .sidebar-action-buttons {
                flex-direction: column;
            }

            .btn-edit {
                width: 100%;
            }
        }