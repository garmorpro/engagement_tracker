<?php
require_once '../path.php';

/**
 * Create a notification in the database
 * 
 * @param string $engagement_idno - Engagement ID
 * @param string $notif_type - Type: upcoming_due_date, status_updated, team_assignment, milestone_complete
 * @param string $notif_title - Notification title
 * @param string $notif_message - Notification message
 * @param int|null $user_id - Optional user ID
 * @return bool - Success status
 */
function createNotification($engagement_idno, $notif_type, $notif_title, $notif_message, $user_id = null) {
    global $conn;
    
    // Check if table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'engagement_notifications'";
    $tableCheckResult = $conn->query($tableCheckQuery);
    
    if (!$tableCheckResult || $tableCheckResult->num_rows === 0) {
        return false; // Table doesn't exist yet
    }
    
    $query = "INSERT INTO engagement_notifications 
              (engagement_idno, notif_type, notif_title, notif_message, user_id, is_read, notif_timestamp) 
              VALUES (?, ?, ?, ?, ?, 'N', NOW())";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param('ssssi', $engagement_idno, $notif_type, $notif_title, $notif_message, $user_id);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Check for upcoming due dates and create notifications
 * Runs daily - notifies when engagement is due within next 3 days
 */
function checkUpcomingDueDates() {
    global $conn;
    
    $query = "
        SELECT eng_idno, eng_name, eng_final_due 
        FROM engagements 
        WHERE eng_status != 'archived' 
        AND eng_status != 'complete'
        AND eng_final_due IS NOT NULL
        AND DATE(eng_final_due) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        AND eng_idno NOT IN (
            SELECT engagement_idno 
            FROM engagement_notifications 
            WHERE notif_type = 'upcoming_due_date' 
            AND DATE(notif_timestamp) = CURDATE()
        )
    ";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $daysUntilDue = (int)((strtotime($row['eng_final_due']) - time()) / 86400);
            $title = 'Upcoming Due Date';
            $message = $row['eng_name'] . ' audit due in ' . ($daysUntilDue + 1) . ' day' . ($daysUntilDue !== 0 ? 's' : '');
            
            createNotification(
                $row['eng_idno'],
                'upcoming_due_date',
                $title,
                $message
            );
        }
    }
}

/**
 * Check for status changes and create notifications
 * This should be called when an engagement status is updated
 */
function notifyStatusChange($engagement_idno, $old_status, $new_status) {
    global $conn;
    
    // Get engagement name
    $query = "SELECT eng_name FROM engagements WHERE eng_idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $engagement_idno);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) return false;
    
    $title = 'Status Updated';
    $message = $row['eng_name'] . ' moved to ' . ucfirst(str_replace('-', ' ', $new_status));
    
    return createNotification(
        $engagement_idno,
        'status_updated',
        $title,
        $message
    );
}

/**
 * Create notification when someone is added to a team
 */
function notifyTeamAssignment($engagement_idno, $team_name) {
    global $conn;
    
    // Get engagement name
    $query = "SELECT eng_name FROM engagements WHERE eng_idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $engagement_idno);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) return false;
    
    $title = 'Team Assignment';
    $message = 'You were added to ' . $row['eng_name'] . ' ' . $team_name . ' team';
    
    return createNotification(
        $engagement_idno,
        'team_assignment',
        $title,
        $message
    );
}

/**
 * Create notification when a milestone is completed
 */
function notifyMilestoneComplete($engagement_idno, $milestone_type, $completion_percent) {
    global $conn;
    
    // Get engagement name
    $query = "SELECT eng_name FROM engagements WHERE eng_idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $engagement_idno);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) return false;
    
    $title = 'Milestone Complete';
    $milestoneText = str_replace('_', ' ', $milestone_type);
    $message = $row['eng_name'] . ' reached ' . $completion_percent . '% completion';
    
    return createNotification(
        $engagement_idno,
        'milestone_complete',
        $title,
        $message
    );
}

/**
 * Clear old read notifications (older than 30 days)
 * Run periodically to keep the table clean
 */
function clearOldNotifications($days = 30) {
    global $conn;
    
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