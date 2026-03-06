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
 * Get all timeline date columns that have values
 * Returns array of [column_name => date_value] for non-null dates
 */
function getTimelineKeyDates($engagement_idno) {
    global $conn;
    
    $timelineColumns = [
        'internal_planning_call_date',
        'planning_memo_date',
        'irl_due_date',
        'client_planning_call_date',
        'fieldwork_date',
        'leadsheet_date',
        'conclusion_memo_date',
        'draft_report_due_date',
        'final_report_date',
        'archive_date'
    ];
    
    $query = "SELECT " . implode(", ", $timelineColumns) . " FROM engagement_timeline WHERE engagement_idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $engagement_idno);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    $keyDates = [];
    if ($row) {
        foreach ($timelineColumns as $column) {
            if ($row[$column] !== null) {
                $keyDates[$column] = $row[$column];
            }
        }
    }
    
    return $keyDates;
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
 * Notifies when any timeline date is exactly 7 days away
 * Only sends if notification hasn't been sent for this engagement yet
 */
function checkUpcomingKeyDates() {
    global $conn;
    
    $dueDate = date('Y-m-d', strtotime('+7 days')); // 7 days from now
    
    // Get all engagements
    $query = "SELECT DISTINCT engagement_idno, e.eng_name 
              FROM engagement_timeline t
              JOIN engagements e ON t.engagement_idno = e.eng_idno
              WHERE e.eng_status != 'archived' 
              AND e.eng_status != 'complete'
              AND t.engagement_idno NOT IN (
                  SELECT DISTINCT engagement_idno 
                  FROM engagement_notifications 
                  WHERE notif_type = 'upcoming_key_date'
              )";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $engagement_idno = $row['engagement_idno'];
            $eng_name = $row['eng_name'];
            
            // Get all key dates for this engagement
            $keyDates = getTimelineKeyDates($engagement_idno);
            
            // Check if any key date matches 7 days from now
            foreach ($keyDates as $columnName => $dateValue) {
                if (date('Y-m-d', strtotime($dateValue)) === $dueDate) {
                    $title = 'Upcoming Key Date';
                    $dateTitle = getTimelineTitle($columnName);
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

/**
 * Check for upcoming milestones
 * Notifies when milestone due date is exactly 5 days away
 * Only sends if notification hasn't been sent for this engagement yet
 */
function checkUpcomingMilestones() {
    global $conn;
    
    $dueDate = date('Y-m-d', strtotime('+5 days')); // 5 days from now
    
    $query = "
        SELECT m.ms_id, m.engagement_idno, m.milestone_type, m.due_date, e.eng_name
        FROM engagement_milestones m
        JOIN engagements e ON m.engagement_idno = e.eng_idno
        WHERE m.is_completed = 'N'
        AND m.due_date IS NOT NULL
        AND DATE(m.due_date) = ?
        AND m.engagement_idno NOT IN (
            SELECT DISTINCT engagement_idno 
            FROM engagement_notifications 
            WHERE notif_type = 'upcoming_milestone'
        )
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $dueDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
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
    $stmt->close();
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