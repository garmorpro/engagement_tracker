<?php
/**
 * Notification Cron Job
 * Run this script periodically (e.g., daily) via cron job
 * 
 * Cron example (run daily at 8 AM):
 * 0 8 * * * php /var/www/engagement_tracker/public_html/engagement_tracker/pages/notification-cron.php
 * 
 * This checks for:
 * - Upcoming key dates (engagement due dates 7 days out)
 * - Upcoming milestones (milestone due dates 5 days out)
 * - Engagements ready to archive (3+ days after completion)
 */

// Use absolute path instead of relative path
$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';
require_once $basePath . '/pages/notification-helper.php';

// Log file for debugging
$logFile = $basePath . '/logs/notification-cron.log';
if (!is_dir(dirname($logFile))) {
    mkdir(dirname($logFile), 0755, true);
}

$logMessage = date('Y-m-d H:i:s') . ' - Starting notification cron job' . PHP_EOL;
file_put_contents($logFile, $logMessage, FILE_APPEND);

try {
    // Check for upcoming key dates
    checkUpcomingKeyDates();
    $logMessage = date('Y-m-d H:i:s') . ' - Checked upcoming key dates' . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Check for upcoming milestones
    checkUpcomingMilestones();
    $logMessage = date('Y-m-d H:i:s') . ' - Checked upcoming milestones' . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Check for engagements ready to archive
    checkEngagementsReadyToArchive();
    $logMessage = date('Y-m-d H:i:s') . ' - Checked engagements ready to archive' . PHP_EOL;
    file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    // Clean up old read notifications once per day
    $hour = date('H');
    if ($hour === '08') { // Run at 8 AM
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