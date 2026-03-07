<?php 
require_once '../path.php';
require_once '../includes/functions.php';

// Get all engagements data
$allEngagements = getAllEngagements($conn);

// Filter to only show active engagements
$engagements = array_filter($allEngagements, fn($e) => $e['eng_status'] !== 'archived');

$archivedCount = count(array_filter($allEngagements, fn($e) => $e['eng_status'] === 'archived'));

// Calculate status counts
$totalCount = count($allEngagements);
$inProgressCount = count(array_filter($allEngagements, fn($e) => $e['eng_status'] === 'in-progress'));
$planningCount = count(array_filter($allEngagements, fn($e) => $e['eng_status'] === 'planning'));
$reviewCount = count(array_filter($allEngagements, fn($e) => $e['eng_status'] === 'in-review'));
$completeCount = count(array_filter($allEngagements, fn($e) => $e['eng_status'] === 'complete'));

// Get unread notifications
$notifications = [];
$unreadNotificationCount = 0;

// Check if table exists before querying
$tableCheckQuery = "SHOW TABLES LIKE 'engagement_notifications'";
$tableCheckResult = $conn->query($tableCheckQuery);

if ($tableCheckResult && $tableCheckResult->num_rows > 0) {
    $notificationQuery = "SELECT * FROM engagement_notifications WHERE is_read = 'N' ORDER BY notif_timestamp DESC LIMIT 10";
    $notificationResult = $conn->query($notificationQuery);
    $notifications = $notificationResult ? $notificationResult->fetch_all(MYSQLI_ASSOC) : [];
    $unreadNotificationCount = count($notifications);
}

// Helper function for time ago display
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Engagement Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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
            --bg-primary: #0F1419;
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

        .notification-dropdown {
            position: absolute;
            top: 100%;
            right: -100px;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            min-width: 380px;
            margin-top: 0.75rem;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
            opacity: 0;
            visibility: hidden;
            transform: translateY(-8px);
            transition: all 0.2s;
            z-index: 1000;
            padding: 0;
            overflow: hidden;
            max-height: 500px;
            overflow-y: auto;
        }

        .notification-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .notification-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: var(--bg-secondary);
        }

        .notification-header h3 {
            margin: 0;
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            gap: 0.75rem;
        }

        .notification-item:hover {
            background-color: var(--gray-100);
        }

        .notification-item.unread {
            background-color: rgba(68, 135, 252, 0.05);
        }

        .notification-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .notification-icon.upcoming {
            background: rgba(241, 115, 19, 0.1);
            color: var(--warning-orange);
        }

        .notification-icon.milestone {
            background: rgba(160, 77, 253, 0.1);
            color: var(--info-purple);
        }

        .notification-icon.archive {
            background: rgba(128, 128, 128, 0.1);
            color: #6b7280;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 600;
            color: var(--text-primary);
            font-size: 13px;
            margin-bottom: 0.25rem;
        }

        .notification-message {
            color: var(--text-secondary);
            font-size: 12px;
            line-height: 1.4;
        }

        .notification-time {
            color: var(--text-secondary);
            font-size: 11px;
            margin-top: 0.5rem;
        }

        .notification-empty {
            padding: 2rem;
            text-align: center;
            color: var(--text-secondary);
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

        /* ========== STATUS CARDS ========== */
        .status-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .status-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
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
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .status-card-value {
            font-size: 28px;
            font-weight: 700;
            color: var(--text-primary);
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
            border: 1px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            transition: all 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(68, 135, 252, 0.1);
        }

        .search-input::placeholder {
            color: var(--text-secondary);
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .filter-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            background: var(--bg-secondary) !important;
            border: 1px solid var(--border-color) !important;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-primary) !important;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn i {
            color: var(--text-primary) !important;
        }

        .filter-btn:hover {
            border-color: var(--primary-blue) !important;
            background: rgba(68, 135, 252, 0.08) !important;
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
            background-color: var(--bg-secondary);
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
            background-color: var(--gray-100) !important;
        }

        .table tbody {
            background-color: var(--bg-secondary);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-color: var(--border-color);
            color: var(--text-primary);
            background-color: var(--bg-secondary) !important;
            word-break: break-word;
        }

        .table tbody tr {
            transition: background-color 0.2s;
            cursor: pointer;
        }

        .table tbody tr:hover td {
            background-color: var(--gray-100) !important;
        }

        /* ========== TABLE CELLS ========== */
        .engagement-id {
            font-weight: 500;
            color: var(--text-secondary);
            font-size: 13px;
        }

        .engagement-name {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .engagement-avatar {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: white;
            font-size: 14px;
        }

        .engagement-avatar.color-1 { background: linear-gradient(135deg, #4487FC, #4DA6FF); }
        .engagement-avatar.color-2 { background: linear-gradient(135deg, #A04DFD, #D67FFF); }
        .engagement-avatar.color-3 { background: linear-gradient(135deg, #4DBFB8, #6FD9D2); }
        .engagement-avatar.color-4 { background: linear-gradient(135deg, #F17313, #FFB347); }
        .engagement-avatar.color-5 { background: linear-gradient(135deg, #4FC65F, #7FDD8A); }

        .engagement-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .engagement-subtitle {
            font-size: 13px;
            color: var(--text-secondary);
            margin-top: 0.25rem;
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

        .badge-audit-type {
            background: rgba(68, 135, 252, 0.15);
            color: var(--primary-blue);
            display: inline-block;
            margin-right: 0.25rem;
            margin-bottom: 0.25rem;
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
            color: var(--text-secondary);
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
            background: #1A2332 !important;
            color: #E8EAED !important;
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

        body.dark-mode .swal2-title {
            color: #E8EAED !important;
        }

        .swal2-html-container {
            color: var(--text-primary);
            padding: 0;
            margin: 0;
        }

        body.dark-mode .swal2-html-container {
            color: #E8EAED !important;
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
            box-shadow: 0 0 0 3px rgba(68, 135, 252, 0.1);
            outline: none;
        }

        .swal2-input::placeholder {
            color: var(--text-secondary);
        }

        body.dark-mode .swal2-input,
        body.dark-mode .swal2-textarea {
            background: #2D3847 !important;
            border: 1px solid #2D3847 !important;
            color: #E8EAED !important;
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
            background: #3671E0;
            box-shadow: 0 4px 12px rgba(68, 135, 252, 0.3);
        }

        .swal2-confirm:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(68, 135, 252, 0.2);
        }

        .swal2-cancel {
            background: var(--border-color);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }

        .swal2-cancel:hover {
            background: rgba(68, 135, 252, 0.05);
            border-color: var(--primary-blue);
            color: var(--primary-blue);
        }

        .swal2-cancel:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(68, 135, 252, 0.1);
        }

        body.dark-mode .swal-dark-btn {
            background: #4487FC !important;
        }

        body.dark-mode .swal-dark-cancel-btn {
            background: #2D3847 !important;
            color: #E8EAED !important;
        }

        /* ========== SWEETALERT2 DARK MODE ========== */
        /* (Already included above in the complete swal2 styling section) */

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
            <a href="dashboard.php" class="header-logo">
                <div class="logo-icon">
                    <i class="bi bi-bar-chart-fill"></i>
                </div>
                <div>
                    <div class="header-title">Engagement Tracker</div>
                    <div class="header-subtitle">Manage your audit engagements</div>
                </div>
            </a>

            <div class="header-nav">
                <a href="dashboard.php" class="nav-item active">Dashboard</a>
                <a href="analytics.php" class="nav-item">Analytics</a>
                <a href="archive.php" class="nav-item">Archive <span class="badge"><?php echo $archivedCount; ?></span></a>
            </div>
        </div>

        <div class="header-right">
            <div class="header-icons">
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
                                    
                                    // For key dates and milestones, dynamically calculate days away
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
                                    <div class="notification-content">
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
                <button class="icon-btn" title="Settings">
                    <i class="bi bi-gear"></i>
                </button>
            </div>

            <div class="profile-section">
                <div class="profile-wrapper" id="profileToggle">
                    <button class="profile-btn" title="Profile">
                        <?php
                            $initials = '';
                    
                            if (!empty($_SESSION['name'])) {
                                $nameParts = explode(' ', trim($_SESSION['name']));
                                
                                foreach ($nameParts as $part) {
                                    if (!empty($part)) {
                                        $initials .= strtoupper($part[0]);
                                    }
                                }
                            }
                    
                            echo $initials;
                        ?>
                    </button>
                    <button class="profile-dropdown-toggle">
                        <i class="bi bi-chevron-down"></i>
                    </button>
                </div>

                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                        <div class="profile-dropdown-avatar">
                            <?php echo $initials; ?>
                        </div>
                        <div class="profile-dropdown-info">
                            <div class="profile-dropdown-name"><?php echo $_SESSION['name'] ?? ''; ?></div>
                            <div class="profile-dropdown-email"><?php echo $_SESSION['email'] ?? ''; ?></div>
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
                            $statusClass = strtolower($eng['eng_status'] ?? 'planning');
                        ?>
                        <tr style="cursor: pointer;" onclick="window.location.href='engagement-details.php?id=<?php echo htmlspecialchars($eng['eng_idno']); ?>'">
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
                                <?php 
                                    $auditTypes = explode(',', $eng['eng_audit_type']);
                                    foreach ($auditTypes as $type) {
                                        $type = trim($type);
                                        if (!empty($type)) {
                                            echo '<span class="badge badge-audit-type">' . htmlspecialchars($type) . '</span> ';
                                        }
                                    }
                                ?>
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
                                    <button class="action-icon" title="Edit" onclick="event.stopPropagation(); window.location.href='engagement-details.php?id=<?php echo htmlspecialchars($eng['eng_idno']); ?>&action=edit'">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button class="action-icon" title="Archive" onclick="event.stopPropagation(); archiveEngagement('<?php echo htmlspecialchars($eng['eng_idno']); ?>')">
                                        <i class="bi bi-archive"></i>
                                    </button>
                                    <button class="action-icon" title="Delete" onclick="event.stopPropagation(); alert('Delete functionality coming soon')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                            No engagements found. Create one to get started.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script>
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
            confirmButtonColor: '#4487FC',
            background: isDarkMode ? '#1A2332' : '#FFFFFF',
            color: isDarkMode ? '#E8EAED' : '#1A1A1A',
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
                        background: isDarkMode ? '#1A2332' : '#FFFFFF',
                        color: isDarkMode ? '#E8EAED' : '#1A1A1A'
                    }).then(() => {
                        location.reload();
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

    // Mark notification as read
    async function markNotificationAsRead(notifId) {
        try {
            const response = await fetch('../api/mark-notification-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ notif_id: notifId })
            });
            
            const data = await response.json();
            if (data.success) {
                // Reload to update unread count
                location.reload();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }

    // Close notification dropdown when clicking outside
    document.addEventListener('click', (e) => {
        const notificationDropdown = document.getElementById('notificationDropdown');
        const notificationBtn = document.querySelector('.icon-btn[title="Notifications"]');
        
        if (notificationDropdown && 
            !notificationDropdown.contains(e.target) && 
            !notificationBtn.contains(e.target)) {
            notificationDropdown.classList.remove('active');
        }
    });

    // Table row click handler
    document.querySelectorAll('.table tbody tr').forEach(row => {
        row.addEventListener('click', (e) => {
            // Don't navigate if clicking on action icons
            if (e.target.closest('.action-icon') || e.target.closest('.action-icons')) {
                return;
            }
            
            // Get the engagement ID from the first cell
            const engagementId = row.querySelector('.engagement-id').textContent.trim();
            if (engagementId) {
                window.location.href = `engagement-details.php?id=${engagementId}`;
            }
        });
    });

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
        // New engagement form data
        const htmlContent = `
            <div style="text-align: left; max-height: 600px; overflow-y: auto;">
                <!-- Basic Information Section -->
                <div style="margin-bottom: 2.5rem;">
                    <h3 style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.5px;">Basic Information</h3>
                    
                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">Engagement Name <span style="color: #C90012;">*</span></label>
                        <input type="text" id="new_eng_name" class="swal2-input" placeholder="Enter engagement name" style="width: 100%; padding: 0.75rem;">
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">Location</label>
                        <input type="text" id="new_eng_location" class="swal2-input" placeholder="Enter location" style="width: 100%; padding: 0.75rem;">
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">Point of Contact</label>
                        <input type="text" id="new_eng_poc" class="swal2-input" placeholder="Enter point of contact" style="width: 100%; padding: 0.75rem;">
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">Status</label>
                        <select id="new_eng_status" class="swal2-input" style="width: 100%; padding: 0.75rem;">
                            <option value="planning" selected>Planning</option>
                            <option value="in-progress">In Progress</option>
                            <option value="in-review">In Review</option>
                            <option value="complete">Complete</option>
                        </select>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">Trusted Service Criteria</label>
                        <input type="text" id="new_eng_tsc" class="swal2-input" placeholder="Enter TSC" style="width: 100%; padding: 0.75rem;">
                    </div>
                </div>

                <!-- Audit Details Section -->
                <div style="margin-bottom: 2.5rem;">
                    <h3 style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.5px;">Audit Details</h3>
                    
                    <div style="margin-bottom: 1.5rem;">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">Audit Types (Select all that apply)</label>
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

                    <!-- SOC Type Selector -->
                    <div id="new_soc_type_section" style="margin-bottom: 1.5rem; display: none; padding: 1.25rem; background: rgba(68, 135, 252, 0.1); border-radius: 8px; border-left: 3px solid var(--primary-blue);">
                        <label style="display: block; margin-bottom: 0.75rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">SOC Type</label>
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

                        <!-- Type 1: As Of Date -->
                        <div id="new_soc_type1_dates" style="display: none;">
                            <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">As Of Date</label>
                            <input type="date" id="new_soc_as_of_date" class="swal2-input" style="width: 100%; padding: 0.75rem;">
                        </div>

                        <!-- Type 2: Start and End Dates -->
                        <div id="new_soc_type2_dates" style="display: none;">
                            <div style="margin-bottom: 0.9rem;">
                                <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">Start Period</label>
                                <input type="date" id="new_soc_start_period" class="swal2-input" style="width: 100%; padding: 0.75rem;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">End Period</label>
                                <input type="date" id="new_soc_end_period" class="swal2-input" style="width: 100%; padding: 0.75rem;">
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">Scope</label>
                        <textarea id="new_eng_scope" class="swal2-input" style="width: 100%; min-height: 80px; resize: vertical; padding: 0.75rem;" placeholder="Enter scope"></textarea>
                    </div>

                    <div style="margin-bottom: 1.25rem;">
                        <label style="display: flex; align-items: center; gap: 0.75rem; cursor: pointer; font-weight: 500;">
                            <input type="checkbox" id="new_eng_repeat" style="width: 18px; height: 18px; cursor: pointer;">
                            <span style="font-size: 13px;">Repeat Engagement</span>
                        </label>
                    </div>
                </div>

                <!-- Notes Section -->
                <div>
                    <h3 style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 0.5px;">Notes</h3>
                    
                    <div>
                        <label style="display: block; margin-bottom: 0.6rem; font-weight: 600; font-size: 11px; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px;">Notes</label>
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
            didOpen: () => {
                // Handle audit type checkboxes and SOC type visibility
                const auditCheckboxes = document.querySelectorAll('.new-audit-type-checkbox');
                const socTypeSection = document.getElementById('new_soc_type_section');
                const socTypeRadios = document.querySelectorAll('input[name="new_soc_type"]');
                const socType1Dates = document.getElementById('new_soc_type1_dates');
                const socType2Dates = document.getElementById('new_soc_type2_dates');
                
                function updateFormVisibility() {
                    const selectedTypes = Array.from(auditCheckboxes)
                        .filter(cb => cb.checked)
                        .map(cb => cb.value);
                    
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
                
                auditCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', updateFormVisibility);
                });
                
                socTypeRadios.forEach(radio => {
                    radio.addEventListener('change', updateDateFields);
                });

                // Focus on engagement name
                document.getElementById('new_eng_name').focus();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Get engagement name first
                const engName = document.getElementById('new_eng_name').value?.trim();
                if (!engName) {
                    Swal.fire('Error', 'Engagement Name is required', 'error');
                    return;
                }

                // Get selected audit types
                const selectedAuditTypes = Array.from(document.querySelectorAll('.new-audit-type-checkbox:checked'))
                    .map(cb => cb.value)
                    .join(', ');

                // Collect all form data
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

                // Send to API
                fetch('../api/create-engagement.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(newEngagementData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Engagement created successfully');
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        Swal.fire('Error', data.message || 'Failed to create engagement', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to create engagement', 'error');
                });
            }
        });
    });
</script>

</body>
</html>