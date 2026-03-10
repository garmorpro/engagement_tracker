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

// Alias engagement data for easier access in forms
$engagementData = $engagement;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($engagement['eng_name']); ?> - Engagement Pro</title>
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
            margin-top: 1rem;
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
            background: rgba(68, 135, 252, 0.2);
            color: #2196F3;
        }

        .section-icon.notes {
            background: rgba(255, 152, 0, 0.2);
            color: #FF9800;
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
            box-shadow: 0 0 0 3px rgba(68, 135, 252, 0.1);
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
            background: rgba(26, 35, 50, 0.95);
            border-color: rgba(45, 56, 71, 0.8);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border-left: 4px solid #4FC65F;
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
                            'planning'    => 'bi-clipboard-check',
                            'in-review'   => 'bi-search',
                            'complete'    => 'bi-check-circle-fill',
                            'archived'    => 'bi-archive',
                            default       => 'bi-circle'
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
                                <div class="critical-date-icon">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                            </div>
                            <span class="overdue-badge pulse">Overdue</span>
                        </div>
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
                        <button id="manageTeamIconBtn" class="btn-icon" style="margin: 0; padding: 0.5rem;" title="Manage team members">
                            <i class="bi bi-gear"></i>
                        </button>
                    </div>
                </div>

                <div class="team-members">
                <?php if (!empty($team)): ?>

                    <?php
                    // Group members by employee name and role
                    $groupedTeam = [];
                    foreach ($team as $member) {
                        $key = $member['emp_name'] . '|' . $member['role'];
                        if (!isset($groupedTeam[$key])) {
                            $dolMap = [];
                            $dolFields = [
                                'SOC 1'   => 'emp_soc1_dol',
                                'SOC 2'   => 'emp_soc2_dol',
                                'HIPAA'   => 'emp_hipaa_dol',
                                'HITRUST' => 'emp_hitrust_dol',
                                'FISMA'   => 'emp_fisma_dol',
                            ];
                            foreach ($dolFields as $auditType => $field) {
                                if (!empty($member[$field])) {
                                    $dolMap[$auditType] = array_map('trim', explode(',', $member[$field]));
                                }
                            }
                            $groupedTeam[$key] = [
                                'emp_name'    => $member['emp_name'],
                                'role'        => $member['role'],
                                'audit_types' => $dolMap
                            ];
                        }
                    }

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
                            $initials  = '';
                            foreach ($nameParts as $part) { $initials .= strtoupper($part[0]); }

                            // Gradient colors by role
                            $gradient = 'linear-gradient(135deg, #4487FC, #4DA6FF)';
                            switch (strtolower($member['role'] ?? '')) {
                                case 'manager': $gradient = 'linear-gradient(135deg, #4487FC, #4DA6FF)'; break;
                                case 'senior':  $gradient = 'linear-gradient(135deg, #A04DFD, #D67FFF)'; break;
                                case 'staff':   $gradient = 'linear-gradient(135deg, #4FC65F, #7FDD8A)'; break;
                            }
                        ?>
                        <div class="team-member" style="display: flex; gap: 0.75rem; align-items: flex-start;">
                            <div class="team-member-avatar" style="background: <?php echo $gradient; ?>; flex-shrink: 0;"><?php echo htmlspecialchars($initials); ?></div>
                            <div class="team-member-info" style="flex: 1; min-width: 0;">
                                <div class="team-member-name"><?php echo htmlspecialchars($member['emp_name']); ?></div>
                                <div class="team-member-title"><?php echo htmlspecialchars(ucfirst($member['role'])); ?></div>

                                <?php
                                $dolOrder   = ['SOC 1', 'SOC 2', 'HIPAA', 'HITRUST', 'FISMA'];
                                $hasDolData = false;
                                foreach ($dolOrder as $auditType) {
                                    if (isset($member['audit_types'][$auditType]) && !empty($member['audit_types'][$auditType])) {
                                        $hasDolData = true;
                                        break;
                                    }
                                }
                                ?>

                                <?php if ($hasDolData && strtolower($member['role']) !== 'manager'): ?>
                                    <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                                        <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.6rem;">
                                            <i class="bi bi-briefcase" style="font-size: 11px; color: var(--text-secondary);"></i>
                                            <span style="font-size: 10px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.5px;">Division of Labor</span>
                                        </div>
                                        <?php foreach ($dolOrder as $auditType): ?>
                                            <?php if (isset($member['audit_types'][$auditType]) && !empty($member['audit_types'][$auditType])): ?>
                                                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                                                    <span style="font-size: 11px; font-weight: 700; color: var(--text-secondary); min-width: 55px;"><?php echo htmlspecialchars($auditType); ?></span>
                                                    <div style="display: flex; gap: 0.35rem; flex-wrap: wrap;">
                                                        <?php foreach ($member['audit_types'][$auditType] as $tag): ?>
                                                            <?php if (!empty(trim($tag))): ?>
                                                                <span style="background: rgba(68, 135, 252, 0.15); color: var(--primary-blue); border: 1px solid rgba(68, 135, 252, 0.3); padding: 0.2rem 0.55rem; border-radius: 5px; font-size: 11px; font-weight: 700;"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
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

                        <div id="soc_type_section" style="margin-bottom: 1.5rem; display: none; padding: 1rem; background: rgba(68, 135, 252, 0.1); border-radius: 8px; border-left: 3px solid var(--primary-blue);">
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
    
    let teamHTML = `
        <div style="display: flex; gap: 1.5rem; height: 100%; min-height: 500px;">
            <!-- LEFT SIDE: Team List -->
            <div style="flex: 1.3; display: flex; flex-direction: column; overflow: hidden; min-width: 300px;">
                <h3 style="font-size: 13px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Team Members</h3>
                <div id="team-list" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 0.75rem; padding-right: 0.5rem;">
                </div>
            </div>

            <!-- RIGHT SIDE: Add Member + Stats Panel -->
            <div style="width: 320px; display: flex; flex-direction: column; gap: 1.5rem; padding-left: 1.5rem; border-left: 1px solid var(--border-color); flex-shrink: 0;">
                <div>
                    <h4 style="font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Add Member</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                        <input type="text" id="add_emp_name" class="swal2-input" placeholder="Name" style="width: 100%; font-size: 12px; padding: 0.6rem; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 6px;">
                        <select id="add_emp_role" class="swal2-input" style="width: 100%; font-size: 12px; padding: 0.6rem; background: var(--input-bg); border: 1px solid var(--border-color); color: var(--text-primary); border-radius: 6px;">
                            <option value="">Role</option>
                            <option value="manager">Manager</option>
                            <option value="senior">Senior</option>
                            <option value="staff">Staff</option>
                        </select>
                        <button id="addTeamMemberBtn" style="background: linear-gradient(135deg, var(--primary-blue), #3671E0); color: white; border: none; padding: 0.7rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px; transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 0.3rem;">
                            <i class="bi bi-plus-circle"></i> Add
                        </button>
                    </div>
                </div>

                <div>
                    <h4 style="font-size: 11px; font-weight: 700; color: var(--text-secondary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Overview</h4>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <div style="padding: 0.75rem; background: rgba(68, 135, 252, 0.1); border-left: 3px solid var(--primary-blue); border-radius: 6px;">
                            <div style="font-size: 10px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; margin-bottom: 0.3rem; letter-spacing: 0.4px;">Managers</div>
                            <div style="font-size: 20px; font-weight: 700; color: var(--primary-blue);" id="manager-count">0</div>
                        </div>
                        <div style="padding: 0.75rem; background: rgba(160, 77, 253, 0.1); border-left: 3px solid #A04DFD; border-radius: 6px;">
                            <div style="font-size: 10px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; margin-bottom: 0.3rem; letter-spacing: 0.4px;">Seniors</div>
                            <div style="font-size: 20px; font-weight: 700; color: #A04DFD;" id="senior-count">0</div>
                        </div>
                        <div style="padding: 0.75rem; background: rgba(79, 198, 95, 0.1); border-left: 3px solid #4FC65F; border-radius: 6px;">
                            <div style="font-size: 10px; color: var(--text-secondary); font-weight: 700; text-transform: uppercase; margin-bottom: 0.3rem; letter-spacing: 0.4px;">Staff</div>
                            <div style="font-size: 20px; font-weight: 700; color: #4FC65F;" id="staff-count">0</div>
                        </div>
                    </div>
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
            
            document.getElementById('addTeamMemberBtn').addEventListener('click', () => {
                const empName = document.getElementById('add_emp_name').value.trim();
                const empRole = document.getElementById('add_emp_role').value;
                
                if (!empName || !empRole) {
                    Swal.showValidationMessage('Please fill in name and role');
                    return;
                }
                addTeamMember(empName, empRole);
            });
        },
        willClose: () => {
            location.reload();
        }
    });

    function renderTeamList() {
        const teamListElement = document.getElementById('team-list');
        if (!teamListElement) return;
        
        teamListElement.innerHTML = '';

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

        currentTeam.forEach((member, idx) => {
            const roleColor = {
                'manager': '#4487FC',
                'senior':  '#A04DFD',
                'staff':   '#4FC65F'
            }[member.role] || '#4487FC';

            const nameParts = member.emp_name.split(' ');
            const initials  = nameParts.map(p => p[0].toUpperCase()).join('');
            const memberId  = member.emp_id;

            teamListElement.innerHTML += `
                <div class="team-member-card" data-emp-id="${memberId}" style="padding: 1rem; background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 10px; transition: all 0.2s ease;">
                    <div style="display: flex; align-items: flex-start; gap: 1rem;">
                        <div style="width: 44px; height: 44px; border-radius: 8px; background: linear-gradient(135deg, ${roleColor}, ${roleColor}dd); display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; flex-shrink: 0; font-size: 14px;">
                            ${initials}
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 600; font-size: 14px; color: var(--text-primary); margin-bottom: 0.15rem; word-break: break-word; line-height: 1.3;">${member.emp_name}</div>
                            <div style="font-size: 11px; color: var(--text-secondary); text-transform: capitalize;">${member.role}</div>
                        </div>
                        <div style="display: flex; gap: 0.4rem; flex-shrink: 0;">
                            <button class="edit-team-btn" data-emp-id="${memberId}" style="background: var(--primary-blue); color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px;">
                                <i class="bi bi-pencil" style="font-size: 13px;"></i>
                            </button>
                            <button class="delete-team-btn" data-emp-id="${memberId}" style="background: #C90012; color: white; border: none; padding: 0.5rem; border-radius: 6px; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; justify-content: center; width: 32px; height: 32px;">
                                <i class="bi bi-trash3" style="font-size: 13px;"></i>
                            </button>
                        </div>
                    </div>
                    ${member.role !== 'manager' ? getDOLByAuditType(member) : ''}
                    <style>
                        .team-member-card:hover { background: var(--bg-secondary); border-color: var(--primary-blue); box-shadow: 0 4px 12px rgba(68, 135, 252, 0.1); }
                        .edit-team-btn:hover   { background: #2560d9; transform: translateY(-1px); }
                        .delete-team-btn:hover { background: #a60010; transform: translateY(-1px); }
                    </style>
                </div>
            `;
        });

        document.querySelectorAll('.edit-team-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const empId  = this.getAttribute('data-emp-id');
                const member = currentTeam.find(m => String(m.emp_id) === String(empId));
                if (member) { editTeamMember(member); }
                else { Swal.fire('Error', 'Member not found', 'error'); }
            });
        });

        document.querySelectorAll('.delete-team-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const empId  = this.getAttribute('data-emp-id');
                const member = currentTeam.find(m => String(m.emp_id) === String(empId));
                if (member) { deleteTeamMember(member); }
                else { Swal.fire('Error', 'Member not found', 'error'); }
            });
        });

        updateRoleCounts();
    }

    function getDOLByAuditType(member) {
        const dolSections = [];
        
        relevantAuditTypes.forEach(auditType => {
            const fieldName = supportedAuditTypes[auditType];
            const dolValue  = member[fieldName];
            
            if (dolValue) {
                const duties   = dolValue.split(',').map(d => d.trim()).filter(d => d);
                const pillsHTML = duties.map(duty => 
                    `<span style="display: inline-block; background: var(--primary-blue); color: white; padding: 0.25rem 0.6rem; border-radius: 5px; font-size: 10px; font-weight: 600; margin-left: 0.4rem;">${duty}</span>`
                ).join('');
                
                dolSections.push(`
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--border-color);">
                        <span style="font-size: 10px; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.3px; min-width: 60px;">${auditType}</span>
                        <div style="display: flex; gap: 0.3rem; flex-wrap: wrap;">${pillsHTML}</div>
                    </div>
                `);
            }
        });
        
        if (dolSections.length > 0) {
            return dolSections.join('');
        } else {
            return '<div style="font-size: 10px; color: var(--danger-red); font-weight: 600; margin-top: 0.75rem;">No DOL assigned</div>';
        }
    }

    function updateRoleCounts() {
        const managerCount = document.getElementById('manager-count');
        const seniorCount  = document.getElementById('senior-count');
        const staffCount   = document.getElementById('staff-count');
        
        if (managerCount) managerCount.textContent = currentTeam.filter(m => m.role === 'manager').length;
        if (seniorCount)  seniorCount.textContent  = currentTeam.filter(m => m.role === 'senior').length;
        if (staffCount)   staffCount.textContent   = currentTeam.filter(m => m.role === 'staff').length;
    }

    function addTeamMember(empName, empRole) {
        const newMember = {
            engagement_idno: engagementId,
            emp_name:        empName,
            role:            empRole
        };

        fetch('../api/add-team-member.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newMember)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                currentTeam.push(data.member);
                document.getElementById('add_emp_name').value = '';
                document.getElementById('add_emp_role').value = '';
                renderTeamList();
            } else {
                Swal.showValidationMessage('Error: ' + (data.message || 'Failed to add team member'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.showValidationMessage('Error: ' + error.message);
        });
    }

    function editTeamMember(member) {
        let dolSections = '';
        
        relevantAuditTypes.forEach(auditType => {
            const fieldName  = supportedAuditTypes[auditType];
            const currentDol = member[fieldName] || '';
            dolSections += `
                <div style="margin-bottom: 1rem;">
                    <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">DOL - ${auditType}</label>
                    <input type="text" class="audit-dol-input" data-field-name="${fieldName}" placeholder="e.g., CC1, CC2, CC3" value="${currentDol}" style="width: 100%; padding: 0.5rem 0.6rem; border: 1px solid var(--border-color); border-radius: 6px; font-size: 12px;">
                </div>
            `;
        });

        Swal.fire({
            title: 'Edit Team Member',
            html: `
                <div style="text-align: left; display: flex; flex-direction: column; gap: 1rem; margin-top: 1rem;">
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Employee Name</label>
                        <input type="text" id="edit_emp_name" class="swal2-input" value="${member.emp_name}" style="width: 100%;">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 12px; color: var(--text-secondary); text-transform: uppercase;">Role</label>
                        <select id="edit_emp_role" class="swal2-input" style="width: 100%; padding: 0.6rem;">
                            <option value="manager" ${member.role === 'manager' ? 'selected' : ''}>Manager</option>
                            <option value="senior"  ${member.role === 'senior'  ? 'selected' : ''}>Senior</option>
                            <option value="staff"   ${member.role === 'staff'   ? 'selected' : ''}>Staff</option>
                        </select>
                    </div>
                    <div id="dol_section" style="display: ${member.role === 'manager' ? 'none' : 'block'};">
                        <h4 style="font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 1rem; text-transform: uppercase; letter-spacing: 0.5px;">Duties and Responsibilities</h4>
                        ${dolSections}
                    </div>
                </div>
            `,
            confirmButtonText: 'Save Changes',
            cancelButtonText: 'Cancel',
            showCancelButton: true,
            didOpen: () => {
                document.getElementById('edit_emp_name').focus();
                const roleSelect = document.getElementById('edit_emp_role');
                roleSelect.addEventListener('change', function() {
                    const dolSection = document.getElementById('dol_section');
                    dolSection.style.display = this.value === 'manager' ? 'none' : 'block';
                });
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const newRole    = document.getElementById('edit_emp_role').value;
                const updateData = {
                    engagement_idno: engagementId,
                    emp_id:          member.emp_id,
                    emp_name:        document.getElementById('edit_emp_name').value,
                    role:            newRole
                };

                if (newRole !== 'manager') {
                    const dolInputs = document.querySelectorAll('.audit-dol-input');
                    dolInputs.forEach(input => {
                        const fieldName = input.getAttribute('data-field-name');
                        updateData[fieldName] = input.value.trim();
                    });
                } else {
                    relevantAuditTypes.forEach(auditType => {
                        const fieldName = supportedAuditTypes[auditType];
                        updateData[fieldName] = '';
                    });
                }

                fetch('../api/update-team-member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(updateData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        sessionStorage.setItem('reopenTeamModal', 'true');
                        location.reload();
                    } else {
                        Swal.fire('Error', data.message || 'Failed to update team member', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Error', 'Failed to update team member: ' + error.message, 'error');
                });
            }
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
            confirmButtonColor: '#C90012'
        }).then((result) => {
            if (result.isConfirmed) {
                const deleteData = {
                    engagement_idno: engagementId,
                    emp_id:          member.emp_id
                };

                fetch('../api/delete-team-member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(deleteData)
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
});
</script>

</body>
</html>