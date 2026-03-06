<?php
/**
 * Notification Cron Job
 * Run this script periodically (e.g., every hour) via cron job
 * 
 * Cron example:
 * 0 * * * * php /path/to/notification-cron.php
 * 
 * This runs every hour and checks for:
 * - Upcoming due dates (within 3 days)
 * - Milestones to notify about
 * - Old notifications to clean up
 */

require_once '../path.php';
require_once '../includes/functions.php';
require_once 'notification-helper.php';

// Log file for debugging
$logFile = '../logs/notification-cron.log';
if (!is_dir('../logs')) {
    mkdir('../logs', 0755, true);
}

$logMessage = date('Y-m-d H:i:s') . ' - Starting notification cron job' . PHP_EOL;
file_put_contents($logFile, $logMessage, FILE_APPEND);

try {
    // Check for upcoming due dates
    checkUpcomingDueDates();
    $logMessage = date('Y-m-d H:i:s') . ' - Checked upcoming due dates' . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Clean up old read notifications (optional - run once per day)
    $hour = date('H');
    if ($hour === '00') { // Run at midnight
        clearOldNotifications(30);
        $logMessage = date('Y-m-d H:i:s') . ' - Cleaned old notifications' . PHP_EOL;
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    $logMessage = date('Y-m-d H:i:s') . ' - Notification cron job completed successfully' . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
} catch (Exception $e) {
    $logMessage = date('Y-m-d H:i:s') . ' - ERROR: ' . $e->getMessage() . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}
?>