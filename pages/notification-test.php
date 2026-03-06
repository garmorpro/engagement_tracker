<?php
/**
 * Notification Test Script
 * Use this to manually create test notifications
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
        
        // Create upcoming key date notification
        createNotification(
            $engId,
            'upcoming_key_date',
            'Upcoming Key Date',
            $engName . ' SOC 2 audit is due in 3 days'
        );
        $counter++;
        
        // Create upcoming milestone notification
        createNotification(
            $engId,
            'upcoming_milestone',
            'Upcoming Milestone',
            $engName . ' - Document Review due in 5 days'
        );
        $counter++;
        
        // Create ready to archive notification
        createNotification(
            $engId,
            'ready_to_archive',
            'Ready to Archive',
            $engName . ' has been complete for 3+ days and is ready to archive'
        );
        $counter++;
    }
    
    echo "✓ Successfully created " . $counter . " test notifications!\n";
    echo "Check your dashboard notification bell to see them.\n";
} else {
    echo "✗ No engagements found to create notifications for.\n";
}
?>