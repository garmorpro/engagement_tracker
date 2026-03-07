<?php
require_once '../path.php';
require_once '../includes/functions.php';

$eng_idno = $_GET['id'] ?? null;

if (!$eng_idno) {
    header('Location: mobile-engagement-list.php');
    exit;
}

// Fetch engagement
$query = "SELECT * FROM engagements WHERE eng_idno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $eng_idno);
$stmt->execute();
$result = $stmt->get_result();
$engagement = $result->fetch_assoc();
$stmt->close();

if (!$engagement) {
    header('Location: mobile-engagement-list.php');
    exit;
}

// Fetch milestones
$query = "SELECT * FROM engagement_milestones WHERE engagement_idno = ? ORDER BY due_date ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $eng_idno);
$stmt->execute();
$result = $stmt->get_result();
$milestones = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch timeline
$query = "SELECT * FROM engagement_timeline WHERE engagement_idno = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $eng_idno);
$stmt->execute();
$result = $stmt->get_result();
$timeline = $result->fetch_assoc();
$stmt->close();

// Fetch team members
$query = "SELECT * FROM engagement_team WHERE engagement_idno = ? ORDER BY emp_name ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $eng_idno);
$stmt->execute();
$result = $stmt->get_result();
$team = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Dark mode
$isDarkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true';

// Timeline key dates
$timelineFields = [
    'internal_planning_call_date' => 'Internal Planning Call',
    'planning_memo_date' => 'Planning Memo',
    'irl_due_date' => 'IRL Due',
    'client_planning_call_date' => 'Client Planning Call',
    'fieldwork_date' => 'Fieldwork',
    'leadsheet_date' => 'Leadsheet Due',
    'conclusion_memo_date' => 'Conclusion Memo',
    'draft_report_due_date' => 'Draft Report Due',
    'final_report_date' => 'Final Report',
    'archive_date' => 'Archive Date'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($engagement['eng_name']); ?> - Engagement Tracker</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #4487FC;
            --success-green: #4FC65F;
            --warning-orange: #F17313;
            --info-purple: #A04DFD;
            --teal: #4DBFB8;
            --danger-red: #C90012;

            --bg-primary: #0F1419;
            --bg-secondary: #1A2332;
            --bg-tertiary: #2A3447;
            --border-color: #3A4557;
            --text-primary: #E8EAED;
            --text-secondary: #9CA3AF;
        }

        body.light-mode {
            --bg-primary: #FFFFFF;
            --bg-secondary: #F5F7FA;
            --bg-tertiary: #E8ECEF;
            --border-color: #D1D5DB;
            --text-primary: #1A1A1A;
            --text-secondary: #6B7280;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }

        /* ========== HEADER ========== */
        .mobile-header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .mobile-header-back {
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 24px;
            cursor: pointer;
        }

        .mobile-header h1 {
            font-size: 16px;
            font-weight: 700;
            flex: 1;
            margin-left: 0.75rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* ========== CONTENT ========== */
        .mobile-content {
            padding: 1rem;
            max-width: 100%;
        }

        .section {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-icon {
            width: 32px;
            height: 32px;
            background: rgba(68, 135, 252, 0.15);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            font-size: 16px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-primary);
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
            font-size: 14px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .info-value {
            color: var(--text-primary);
            font-weight: 500;
            text-align: right;
            flex: 1;
            margin-left: 1rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-badge.planning {
            background: rgba(241, 115, 19, 0.2);
            color: var(--warning-orange);
        }

        .status-badge.in-progress {
            background: rgba(79, 198, 95, 0.2);
            color: var(--success-green);
        }

        .status-badge.in-review {
            background: rgba(160, 77, 253, 0.2);
            color: var(--info-purple);
        }

        .status-badge.complete {
            background: rgba(77, 191, 184, 0.2);
            color: var(--teal);
        }

        .badge-audit-type {
            display: inline-block;
            padding: 0.3rem 0.5rem;
            background: rgba(68, 135, 252, 0.15);
            color: var(--primary-blue);
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 0.3rem;
            margin-bottom: 0.3rem;
        }

        /* ========== TEAM MEMBERS ========== */
        .team-member {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .team-avatar {
            width: 36px;
            height: 36px;
            background: var(--primary-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 12px;
            flex-shrink: 0;
        }

        .team-info {
            flex: 1;
        }

        .team-name {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .team-role {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 0.2rem;
        }

        .team-lead-badge {
            font-size: 10px;
            background: rgba(241, 115, 19, 0.2);
            color: var(--warning-orange);
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-weight: 600;
        }

        /* ========== TIMELINE GRID ========== */
        .timeline-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .timeline-item {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
            text-align: center;
        }

        .timeline-item-name {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .timeline-item-date {
            font-size: 13px;
            color: var(--text-secondary);
            word-break: break-word;
        }

        .timeline-item.completed {
            border-color: var(--success-green);
            background: rgba(79, 198, 95, 0.05);
        }

        .timeline-item.completed .timeline-item-date {
            color: var(--success-green);
            font-weight: 600;
        }

        /* ========== MILESTONE ========== */
        .milestone-item {
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
        }

        .milestone-content {
            flex: 1;
        }

        .milestone-type {
            font-size: 13px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .milestone-due {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .milestone-item.completed {
            opacity: 0.7;
        }

        .milestone-item.completed .milestone-type {
            text-decoration: line-through;
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            color: var(--text-secondary);
            padding: 1rem;
            font-size: 13px;
        }

        /* ========== DARK MODE TOGGLE ========== */
        .dark-mode-toggle {
            position: fixed;
            bottom: 1.5rem;
            right: 1rem;
            width: 50px;
            height: 50px;
            background: var(--primary-blue);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            z-index: 50;
        }

        .dark-mode-toggle:active {
            transform: scale(0.95);
        }

        .bottom-padding {
            height: 5rem;
        }
    </style>
</head>
<body <?php echo $isDarkMode ? '' : 'class="light-mode"'; ?>>

    <!-- HEADER -->
    <div class="mobile-header">
        <button class="mobile-header-back" onclick="history.back()">
            <i class="bi bi-chevron-left"></i>
        </button>
        <h1><?php echo htmlspecialchars($engagement['eng_name']); ?></h1>
    </div>

    <!-- CONTENT -->
    <div class="mobile-content">
        <!-- BASIC INFO -->
        <div class="section">
            <div class="section-header">
                <div class="section-icon">
                    <i class="bi bi-briefcase"></i>
                </div>
                <div class="section-title">Basic Information</div>
            </div>
            <div class="info-row">
                <span class="info-label">ID</span>
                <span class="info-value"><?php echo htmlspecialchars($engagement['eng_idno']); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Status</span>
                <span class="info-value">
                    <span class="status-badge <?php echo htmlspecialchars($engagement['eng_status']); ?>">
                        <?php echo ucfirst(str_replace('-', ' ', $engagement['eng_status'])); ?>
                    </span>
                </span>
            </div>
            <?php if (!empty($engagement['eng_location'])): ?>
                <div class="info-row">
                    <span class="info-label">Location</span>
                    <span class="info-value"><?php echo htmlspecialchars($engagement['eng_location']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($engagement['eng_manager'])): ?>
                <div class="info-row">
                    <span class="info-label">Manager</span>
                    <span class="info-value"><?php echo htmlspecialchars($engagement['eng_manager']); ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($engagement['eng_poc'])): ?>
                <div class="info-row">
                    <span class="info-label">Point of Contact</span>
                    <span class="info-value"><?php echo htmlspecialchars($engagement['eng_poc']); ?></span>
                </div>
            <?php endif; ?>
        </div>

        <!-- AUDIT DETAILS -->
        <?php if (!empty($engagement['eng_audit_type']) || !empty($engagement['eng_soc_type'])): ?>
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="section-title">Audit Details</div>
                </div>
                <?php if (!empty($engagement['eng_audit_type'])): ?>
                    <div class="info-row">
                        <span class="info-label">Audit Types</span>
                        <span class="info-value">
                            <?php
                            $auditTypes = explode(',', $engagement['eng_audit_type']);
                            foreach ($auditTypes as $type) {
                                $type = trim($type);
                                if (!empty($type)) {
                                    echo '<span class="badge-audit-type">' . htmlspecialchars($type) . '</span>';
                                }
                            }
                            ?>
                        </span>
                    </div>
                <?php endif; ?>
                <?php if (!empty($engagement['eng_soc_type'])): ?>
                    <div class="info-row">
                        <span class="info-label">SOC Type</span>
                        <span class="info-value"><?php echo htmlspecialchars($engagement['eng_soc_type']); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- TEAM MEMBERS -->
        <?php if (!empty($team)): ?>
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="section-title">Team Members</div>
                </div>
                <?php foreach ($team as $member): ?>
                    <div class="team-member">
                        <div class="team-avatar">
                            <?php echo strtoupper(substr($member['emp_name'], 0, 1)); ?>
                        </div>
                        <div class="team-info">
                            <div class="team-name"><?php echo htmlspecialchars($member['emp_name']); ?></div>
                            <div class="team-role">
                                <?php echo htmlspecialchars($member['role']); ?>
                                <?php if (!empty($member['emp_dol'])): ?>
                                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 0.3rem;">
                                        <i class="bi bi-calendar"></i> DOL: <?php echo date('M d, Y', strtotime($member['emp_dol'])); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- KEY DATES -->
        <?php if (!empty($timeline)): ?>
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div class="section-title">Key Dates</div>
                </div>
                <div class="timeline-grid">
                    <?php foreach ($timelineFields as $dbField => $displayName): ?>
                        <?php 
                            $fieldValue = $timeline[$dbField] ?? null;
                            $completedField = str_replace('_date', '_completed_at', $dbField);
                            $isCompleted = !empty($timeline[$completedField]);
                        ?>
                        <div class="timeline-item <?php echo $isCompleted ? 'completed' : ''; ?>">
                            <div class="timeline-item-name"><?php echo $displayName; ?></div>
                            <div class="timeline-item-date">
                                <?php 
                                    if (!empty($fieldValue)) {
                                        echo date('M d, Y', strtotime($fieldValue));
                                    } else {
                                        echo 'Not set';
                                    }
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- MILESTONES -->
        <?php if (!empty($milestones)): ?>
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-target"></i>
                    </div>
                    <div class="section-title">Milestones</div>
                </div>
                <?php foreach ($milestones as $milestone): ?>
                    <div class="milestone-item <?php echo $milestone['is_completed'] === 'Y' ? 'completed' : ''; ?>">
                        <div style="flex-shrink: 0;">
                            <i class="bi bi-check-circle<?php echo $milestone['is_completed'] === 'Y' ? '-fill' : ''; ?>" style="color: var(--success-green); font-size: 18px;"></i>
                        </div>
                        <div class="milestone-content">
                            <div class="milestone-type"><?php echo htmlspecialchars($milestone['milestone_type']); ?></div>
                            <div class="milestone-due">
                                <i class="bi bi-calendar"></i>
                                <?php echo date('M d, Y', strtotime($milestone['due_date'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- NOTES -->
        <?php if (!empty($engagement['eng_notes'])): ?>
            <div class="section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-chat-left-text"></i>
                    </div>
                    <div class="section-title">Notes</div>
                </div>
                <p style="font-size: 14px; line-height: 1.6; color: var(--text-primary); white-space: pre-wrap;">
                    <?php echo htmlspecialchars($engagement['eng_notes']); ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="bottom-padding"></div>
    </div>

    <!-- DARK MODE TOGGLE -->
    <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
        <i class="bi" id="darkModeIcon"></i>
    </button>

    <script>
        // Dark mode functionality
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        updateDarkMode(isDarkMode);

        function updateDarkMode(isDark) {
            if (isDark) {
                document.body.classList.remove('light-mode');
                document.getElementById('darkModeIcon').className = 'bi bi-sun-fill';
            } else {
                document.body.classList.add('light-mode');
                document.getElementById('darkModeIcon').className = 'bi bi-moon-fill';
            }
        }

        function toggleDarkMode() {
            const isDark = localStorage.getItem('darkMode') === 'true';
            localStorage.setItem('darkMode', !isDark ? 'true' : 'false');
            updateDarkMode(!isDark);
            document.cookie = `darkMode=${!isDark ? 'true' : 'false'}; path=/`;
        }
    </script>

</body>
</html>