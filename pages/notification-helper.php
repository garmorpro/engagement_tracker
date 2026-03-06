<?php
// Use absolute path instead of relative path
$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';

/**
 * Create a notification in the database
 * 
 * @param string $engagement_idno - Engagement ID
 * @param string $notif_type - Type: upcoming_key_date, upcoming_milestone, ready_to_archive
 * @param string $notif_title - Notification title
 * @param string $notif_message - Notification message
 * @return bool - Success status
 */
function createNotification($engagement_idno, $notif_type, $notif_title, $notif_message) {
    global $conn;
    
    // Check if table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'engagement_notifications'";
    $tableCheckResult = $conn->query($tableCheckQuery);
    
    if (!$tableCheckResult || $tableCheckResult->num_rows === 0) {
        return false; // Table doesn't exist yet
    }
    
    $query = "INSERT INTO engagement_notifications 
              (engagement_idno, notif_type, notif_title, notif_message, is_read, notif_timestamp) 
              VALUES (?, ?, ?, ?, 'N', NOW())";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param('ssss', $engagement_idno, $notif_type, $notif_title, $notif_message);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Convert timeline column name to readable title
 */
function getTimelineTitle($columnName) {
    $titles = [
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
    
    return $titles[$columnName] ?? $columnName;
}

/**
 * Check for upcoming key dates from engagement_timeline
 * Notifies when the date is exactly 7 days away (meaning 7 days before the event)
 * Only sends if notification hasn't been sent for this engagement yet
 */
function checkUpcomingKeyDates() {
    global $conn;
    
    // Get all active engagements with timeline
    $query = "SELECT DISTINCT t.engagement_idno, e.eng_name 
              FROM engagement_timeline t
              JOIN engagements e ON t.engagement_idno = e.eng_idno
              WHERE e.eng_status NOT IN ('archived', 'complete')
              AND t.engagement_idno NOT IN (
                  SELECT DISTINCT engagement_idno 
                  FROM engagement_notifications 
                  WHERE notif_type = 'upcoming_key_date'
              )";
    
    $result = $conn->query($query);
    
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
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $engagement_idno = $row['engagement_idno'];
            $eng_name = $row['eng_name'];
            
            // Get timeline for this engagement
            $timelineQuery = "SELECT * FROM engagement_timeline WHERE engagement_idno = ?";
            $stmt = $conn->prepare($timelineQuery);
            $stmt->bind_param('s', $engagement_idno);
            $stmt->execute();
            $timelineResult = $stmt->get_result();
            $timeline = $timelineResult->fetch_assoc();
            $stmt->close();
            
            if (!$timeline) continue;
            
            // Check each date/completed pair
            foreach ($dateFields as $dateCol => $completedCol) {
                $dateValue = $timeline[$dateCol];
                $completedValue = $timeline[$completedCol];
                
                // Only notify if: date exists, is not completed, and is 7 days away
                if ($dateValue && !$completedValue) {
                    $daysUntilDate = round((strtotime($dateValue) - time()) / 86400);
                    
                    // Notify when date is 7 days away (7 days before the event)
                    if ($daysUntilDate === 7) {
                        $title = 'Upcoming Key Date';
                        $dateTitle = $titleMap[$dateCol];
                        $message = $eng_name . ' - ' . $dateTitle . ' is due in 7 days';
                        
                        createNotification(
                            $engagement_idno,
                            'upcoming_key_date',
                            $title,
                            $message
                        );
                        break; // Only send one notification per engagement per check
                    }
                }
            }
        }
    }
}

/**
 * Check for upcoming milestones
 * Notifies when milestone due date is exactly 5 days away (5 days before the milestone)
 * Only sends if notification hasn't been sent for this engagement yet
 */
function checkUpcomingMilestones() {
    global $conn;
    
    $query = "
        SELECT m.ms_id, m.engagement_idno, m.milestone_type, m.due_date, e.eng_name
        FROM engagement_milestones m
        JOIN engagements e ON m.engagement_idno = e.eng_idno
        WHERE m.is_completed = 'N'
        AND m.due_date IS NOT NULL
        AND m.engagement_idno NOT IN (
            SELECT DISTINCT engagement_idno 
            FROM engagement_notifications 
            WHERE notif_type = 'upcoming_milestone'
        )
    ";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $daysUntilDate = round((strtotime($row['due_date']) - time()) / 86400);
            
            // Notify when date is 5 days away (5 days before the milestone)
            if ($daysUntilDate === 5) {
                // Convert milestone_type from snake_case to Title Case
                $milestoneTitle = implode(' ', array_map('ucfirst', explode('_', strtolower($row['milestone_type']))));
                
                $title = 'Upcoming Milestone';
                $message = $row['eng_name'] . ' - ' . $milestoneTitle . ' due in 5 days';
                
                createNotification(
                    $row['engagement_idno'],
                    'upcoming_milestone',
                    $title,
                    $message
                );
            }
        }
    }
}

/**
 * Check for completed engagements that should be archived
 * Notifies when engagement has been complete for 3+ days
 * Runs daily - prevents duplicate notifications on same day
 */
function checkEngagementsReadyToArchive() {
    global $conn;
    
    $query = "
        SELECT eng_idno, eng_name, eng_complete_date
        FROM engagements 
        WHERE eng_status = 'complete'
        AND eng_complete_date IS NOT NULL
        AND DATE(eng_complete_date) <= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
        AND eng_idno NOT IN (
            SELECT DISTINCT engagement_idno 
            FROM engagement_notifications 
            WHERE notif_type = 'ready_to_archive'
        )
    ";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $title = 'Ready to Archive';
            $message = $row['eng_name'] . ' has been complete for 3+ days and is ready to archive';
            
            createNotification(
                $row['eng_idno'],
                'ready_to_archive',
                $title,
                $message
            );
        }
    }
}

/**
 * Clear old read notifications (older than 30 days)
 * Run periodically to keep the table clean
 */
function clearOldNotifications($days = 30) {
    global $conn;
    
    // Check if table exists first
    $tableCheckQuery = "SHOW TABLES LIKE 'engagement_notifications'";
    $tableCheckResult = $conn->query($tableCheckQuery);
    
    if (!$tableCheckResult || $tableCheckResult->num_rows === 0) {
        return false;
    }
    
    $query = "DELETE FROM engagement_notifications 
              WHERE is_read = 'Y' 
              AND notif_timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $days);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}
?>