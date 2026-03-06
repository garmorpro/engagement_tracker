<?php
/**
 * Notification Test Script
 * Use this to manually create notifications for testing
 * 
 * Run via command line:
 * php notification-test.php
 */

require_once '../path.php';
require_once '../includes/functions.php';
require_once 'notification-helper.php';

// Get all active engagements
$query = "SELECT eng_idno, eng_name FROM engagements WHERE eng_status != 'archived' LIMIT 3";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $counter = 0;
    while ($engagement = $result->fetch_assoc()) {
        $engId = $engagement['eng_idno'];
        $engName = $engagement['eng_name'];
        
        // Create different types of notifications
        createNotification(
            $engId,
            'upcoming_due_date',
            'Upcoming Due Date',
            $engName . ' SOC 2 audit due in 3 days'
        );
        $counter++;
        
        createNotification(
            $engId,
            'status_updated',
            'Status Updated',
            $engName . ' moved to In Progress'
        );
        $counter++;
        
        createNotification(
            $engId,
            'milestone_complete',
            'Milestone Complete',
            $engName . ' reached 75% completion'
        );
        $counter++;
    }
    
    echo "✓ Successfully created " . $counter . " test notifications!\n";
    echo "Check your dashboard notification bell to see them.\n";
} else {
    echo "✗ No engagements found to create notifications for.\n";
}
?>