<?php
require_once '../path.php';

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
            SELECT engagement_idno 
            FROM engagement_notifications 
            WHERE notif_type = 'ready_to_archive' 
            AND DATE(notif_timestamp) = CURDATE()
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
 * Check for upcoming key dates (engagement final due dates)
 * Notifies when engagement is due within next 7 days
 * Runs daily - prevents duplicate notifications on same day
 */
function checkUpcomingKeyDates() {
    global $conn;
    
    $query = "
        SELECT eng_idno, eng_name, eng_final_due 
        FROM engagements 
        WHERE eng_status != 'archived' 
        AND eng_status != 'complete'
        AND eng_final_due IS NOT NULL
        AND DATE(eng_final_due) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND eng_idno NOT IN (
            SELECT engagement_idno 
            FROM engagement_notifications 
            WHERE notif_type = 'upcoming_key_date' 
            AND DATE(notif_timestamp) = CURDATE()
        )
    ";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $daysUntilDue = (int)((strtotime($row['eng_final_due']) - time()) / 86400);
            $title = 'Upcoming Key Date';
            $message = $row['eng_name'] . ' is due in ' . ($daysUntilDue + 1) . ' day' . ($daysUntilDue !== 0 ? 's' : '');
            
            createNotification(
                $row['eng_idno'],
                'upcoming_key_date',
                $title,
                $message
            );
        }
    }
}

/**
 * Check for upcoming milestones
 * Notifies when milestone due date is within next 7 days
 * Runs daily - prevents duplicate notifications on same day
 */
function checkUpcomingMilestones() {
    global $conn;
    
    $query = "
        SELECT m.ms_id, m.engagement_idno, m.milestone_type, m.due_date, e.eng_name
        FROM engagement_milestones m
        JOIN engagements e ON m.engagement_idno = e.eng_idno
        WHERE m.is_completed = 'N'
        AND m.due_date IS NOT NULL
        AND DATE(m.due_date) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND m.engagement_idno NOT IN (
            SELECT engagement_idno 
            FROM engagement_notifications 
            WHERE notif_type = 'upcoming_milestone' 
            AND DATE(notif_timestamp) = CURDATE()
        )
    ";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $daysUntilDue = (int)((strtotime($row['due_date']) - time()) / 86400);
            
            // Convert milestone_type from snake_case to Title Case
            $milestoneTitle = implode(' ', array_map('ucfirst', explode('_', strtolower($row['milestone_type']))));
            
            $title = 'Upcoming Milestone';
            $message = $row['eng_name'] . ' - ' . $milestoneTitle . ' due in ' . ($daysUntilDue + 1) . ' day' . ($daysUntilDue !== 0 ? 's' : '');
            
            createNotification(
                $row['engagement_idno'],
                'upcoming_milestone',
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