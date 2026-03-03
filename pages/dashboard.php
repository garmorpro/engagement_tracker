<?php 
require_once '../path.php';
require_once '../includes/functions.php';

// Get engagements data
$engagements = getAllEngagements($conn);

// Calculate status counts
$totalCount = count($engagements);
$inProgressCount = count(array_filter($engagements, fn($e) => $e['eng_status'] === 'in-progress'));
$planningCount = count(array_filter($engagements, fn($e) => $e['eng_status'] === 'planning'));
$reviewCount = count(array_filter($engagements, fn($e) => $e['eng_status'] === 'in-review'));
$completeCount = count(array_filter($engagements, fn($e) => $e['eng_status'] === 'complete'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Engagement Pro</title>
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
            --primary-blue: #4487FC;
            --success-green: #4FC65F;
            --warning-orange: #F17313;
            --danger-red: #C90012;
            --info-purple: #A04DFD;
            --teal: #4DBFB8;
            --gray-100: #1E2736;
            --gray-200: #2D3847;
            --gray-300: #8B95A6;
            --text-dark: #E8EAED;
            --bg-primary: #0F1419;
            --bg-secondary: #1A2332;
            --border-color: #2D3847;
            --text-primary: #E8EAED;
            --text-secondary: #8B95A6;
        }

        body {
            background-color: var(--bg-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            color: var(--text-primary);
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
            gap: 2.5rem;
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
            border-right: 1px solid var(--gray-200);
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
            color: var(--gray-300);
            cursor: pointer;
            font-size: 16px;
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.2s;
        }

        .profile-dropdown-toggle:hover {
            color: var(--text-dark);
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

        .profile-dropdown-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
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
            max-width: 1400px;
            margin: 0 auto;
        }

        /* ========== STATUS CARDS ========== */
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .status-card {
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .status-card:hover {
            border-color: var(--primary-blue);
            box-shadow: 0 4px 12px rgba(68, 135, 252, 0.08);
        }

        .status-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }

        .status-card-icon.total {
            background: rgba(68, 135, 252, 0.1);
            color: var(--primary-blue);
        }

        .status-card-icon.in-progress {
            background: rgba(79, 198, 95, 0.1);
            color: var(--success-green);
        }

        .status-card-icon.planning {
            background: rgba(241, 115, 19, 0.1);
            color: var(--warning-orange);
        }

        .status-card-icon.review {
            background: rgba(160, 77, 253, 0.1);
            color: var(--info-purple);
        }

        .status-card-icon.complete {
            background: rgba(77, 191, 184, 0.1);
            color: var(--teal);
        }

        .status-card-content {
            flex: 1;
        }

        .status-card-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--gray-300);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .status-card-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-dark);
        }

        /* ========== SEARCH & FILTERS BAR ========== */
        .search-bar-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            align-items: center;
            justify-content: space-between;
        }

        .search-filter-group {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-input-wrapper {
            position: relative;
            width: 280px;
        }

        .search-input {
            width: 100%;
            padding: 0.65rem 1rem 0.65rem 2.5rem;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            font-size: 14px;
            background: white;
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(68, 135, 252, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-300);
        }

        .filter-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-dark);
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover {
            border-color: var(--primary-blue);
            background: rgba(68, 135, 252, 0.02);
        }

        .view-toggle {
            display: flex;
            gap: 0.25rem;
            background: var(--gray-100);
            padding: 0.25rem;
            border-radius: 8px;
        }

        .view-btn {
            width: 36px;
            height: 36px;
            border: none;
            background: transparent;
            color: var(--text-secondary);
            cursor: pointer;
            border-radius: 6px;
            font-size: 16px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .view-btn.active {
            background: var(--bg-secondary);
            color: var(--primary-blue);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .btn-new-engagement {
            background: var(--primary-blue);
            color: white;
            border: none;
            padding: 0.75rem 1.25rem;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-new-engagement:hover {
            background: #3671E0;
            box-shadow: 0 4px 12px rgba(68, 135, 252, 0.3);
        }

        /* ========== TABLE ========== */
        .table-container {
            background: var(--bg-secondary);
            border-radius: 12px;
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: background-color 0.3s ease, border-color 0.3s ease;
        }

        .table {
            margin-bottom: 0;
            font-size: 14px;
            color: var(--text-primary);
        }

        .table thead {
            background: var(--gray-100);
            border-bottom: 1px solid var(--border-color);
        }

        .table thead th {
            padding: 1rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            border: none;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: var(--border-color);
            color: var(--text-primary);
        }

        .table tbody tr {
            transition: background-color 0.2s;
        }

        .table tbody tr:hover {
            background-color: var(--gray-100);
        }

        /* ========== TABLE CELLS ========== */
        .engagement-id {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 13px;
        }

        .engagement-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .engagement-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .badge {
            display: inline-block;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-soc1 {
            background: rgba(68, 135, 252, 0.15);
            color: var(--primary-blue);
        }

        .badge-soc2 {
            background: rgba(68, 135, 252, 0.15);
            color: var(--primary-blue);
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.in-progress {
            background: rgba(79, 198, 95, 0.15);
            color: var(--success-green);
        }

        .status-badge.planning {
            background: rgba(241, 115, 19, 0.15);
            color: var(--warning-orange);
        }

        .status-badge.in-review {
            background: rgba(160, 77, 253, 0.15);
            color: var(--info-purple);
        }

        .status-badge.on-hold {
            background: rgba(192, 0, 24, 0.15);
            color: var(--danger-red);
        }

        .status-badge.complete {
            background: rgba(77, 191, 184, 0.15);
            color: var(--teal);
        }

        .action-icons {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        .action-icon {
            width: 32px;
            height: 32px;
            border: none;
            background: none;
            color: var(--gray-300);
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .action-icon:hover {
            background: var(--gray-100);
            color: var(--primary-blue);
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .header-nav {
                display: none;
            }

            .search-bar-section {
                flex-direction: column;
                align-items: stretch;
            }

            .search-filter-group {
                flex-direction: column;
            }

            .search-input-wrapper {
                width: 100%;
            }

            .action-buttons {
                width: 100%;
                justify-content: flex-start;
            }

            .btn-new-engagement {
                flex: 1;
            }

            .table-container {
                overflow-x: auto;
            }
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
                <a href="#" class="nav-item active">Dashboard</a>
                <a href="#" class="nav-item">Analytics</a>
                <a href="#" class="nav-item">Archive <span class="badge">1</span></a>
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

    <!-- STATUS CARDS -->
    <div class="status-cards">
        <div class="status-card">
            <div class="status-card-icon total">
                <i class="bi bi-briefcase"></i>
            </div>
            <div class="status-card-content">
                <div class="status-card-label">Total</div>
                <div class="status-card-value"><?php echo $totalCount; ?></div>
            </div>
        </div>

        <div class="status-card">
            <div class="status-card-icon in-progress">
                <i class="bi bi-play-circle"></i>
            </div>
            <div class="status-card-content">
                <div class="status-card-label">In Progress</div>
                <div class="status-card-value"><?php echo $inProgressCount; ?></div>
            </div>
        </div>

        <div class="status-card">
            <div class="status-card-icon planning">
                <i class="bi bi-clipboard-check"></i>
            </div>
            <div class="status-card-content">
                <div class="status-card-label">Planning</div>
                <div class="status-card-value"><?php echo $planningCount; ?></div>
            </div>
        </div>

        <div class="status-card">
            <div class="status-card-icon review">
                <i class="bi bi-search"></i>
            </div>
            <div class="status-card-content">
                <div class="status-card-label">Review</div>
                <div class="status-card-value"><?php echo $reviewCount; ?></div>
            </div>
        </div>

        <div class="status-card">
            <div class="status-card-icon complete">
                <i class="bi bi-check-circle"></i>
            </div>
            <div class="status-card-content">
                <div class="status-card-label">Complete</div>
                <div class="status-card-value"><?php echo $completeCount; ?></div>
            </div>
        </div>
    </div>

    <!-- SEARCH & FILTERS -->
    <div class="search-bar-section">
        <div class="search-filter-group">
            <div class="search-input-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search...">
            </div>
            <button class="filter-btn">
                <i class="bi bi-funnel"></i> Filters
            </button>
        </div>

        <div class="action-buttons">
            <div class="view-toggle">
                <button class="view-btn active" title="List view">
                    <i class="bi bi-list-ul"></i>
                </button>
                <button class="view-btn" title="Grid view">
                    <i class="bi bi-grid-3x3-gap"></i>
                </button>
            </div>
            <button class="btn-new-engagement">
                <i class="bi bi-plus"></i> New Engagement
            </button>
        </div>
    </div>

    <!-- TABLE -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Manager</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Location</th>
                    <th>Due Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($engagements)): ?>
                    <?php foreach ($engagements as $index => $eng): ?>
                        <?php
                            $colorClass = 'color-' . (($index % 5) + 1);
                            $initials = strtoupper(substr($eng['eng_name'], 0, 1) . substr(explode(' ', $eng['eng_name'])[1] ?? '', 0, 1));
                            $dueDate = $eng['eng_final_due'] ? date('Y-m-d', strtotime($eng['eng_final_due'])) : 'N/A';
                            $statusClass = strtolower(str_replace('-', '', $eng['eng_status'] ?? 'planning'));
                        ?>
                        <tr>
                            <td>
                                <div class="engagement-id"><?php echo htmlspecialchars($eng['eng_idno']); ?></div>
                            </td>
                            <td>
                                <div class="engagement-name">
                                    <div class="engagement-avatar <?php echo $colorClass; ?>">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>
                                    <div>
                                        <div class="engagement-title"><?php echo htmlspecialchars($eng['eng_name']); ?></div>
                                        <div class="engagement-subtitle"><?php echo htmlspecialchars($eng['eng_idno']); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($eng['eng_manager'] ?? 'Unassigned'); ?></td>
                            <td>
                                <?php if (strpos($eng['eng_audit_type'], 'SOC 1') !== false): ?>
                                    <span class="badge badge-soc1">SOC 1</span>
                                <?php endif; ?>
                                <?php if (strpos($eng['eng_audit_type'], 'SOC 2') !== false): ?>
                                    <span class="badge badge-soc2">SOC 2</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php 
                                        $statusText = str_replace('-', ' ', $eng['eng_status']);
                                        echo ucfirst($statusText);
                                    ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($eng['eng_location'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($dueDate); ?></td>
                            <td>
                                <div class="action-icons">
                                    <button class="action-icon" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="action-icon" title="Duplicate">
                                        <i class="bi bi-files"></i>
                                    </button>
                                    <button class="action-icon" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--gray-300);">
                            No engagements found. Create one to get started.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize dark mode from localStorage
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    if (isDarkMode) {
        document.body.classList.add('dark-mode');
    }

    // Dark mode toggle
    const darkModeBtn = document.querySelector('.icon-btn[title="Dark mode"]');
    darkModeBtn?.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        const isNowDark = document.body.classList.contains('dark-mode');
        localStorage.setItem('darkMode', isNowDark);
        
        // Update icon
        const icon = darkModeBtn.querySelector('i');
        if (isNowDark) {
            icon.classList.remove('bi-moon');
            icon.classList.add('bi-sun');
        } else {
            icon.classList.remove('bi-sun');
            icon.classList.add('bi-moon');
        }
    });

    // Profile dropdown toggle
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');

    profileToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        profileDropdown?.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!profileToggle?.contains(e.target) && !profileDropdown?.contains(e.target)) {
            profileDropdown?.classList.remove('active');
        }
    });

    // Search functionality
    document.querySelector('.search-input')?.addEventListener('input', (e) => {
        const query = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('table tbody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });

    // View toggle
    document.querySelectorAll('.view-btn').forEach((btn, idx) => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            // TODO: Implement view switching logic
        });
    });

    // Action buttons
    document.querySelector('.btn-new-engagement')?.addEventListener('click', () => {
        // TODO: Open new engagement modal
        alert('Open new engagement modal');
    });
</script>

</body>
</html>