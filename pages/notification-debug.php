<?php
/**
 * Debug Script for Notification System
 * Run this in your browser or command line to debug notifications
 * 
 * Command line: php /path/to/notification-debug.php
 * Browser: http://yoursite.com/engagement_tracker/pages/notification-debug.php
 */

// Use absolute path
$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

echo "=== NOTIFICATION DEBUG ===\n\n";

// Check if table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'engagement_notifications'");
if (!$tableCheckResult || $tableCheckResult->num_rows === 0) {
    echo "❌ ERROR: engagement_notifications table does not exist!\n";
    echo "Run this SQL first:\n";
    echo "CREATE TABLE IF NOT EXISTS engagement_notifications (\n";
    echo "    notif_id INT AUTO_INCREMENT PRIMARY KEY,\n";
    echo "    engagement_idno VARCHAR(50) NOT NULL,\n";
    echo "    notif_type VARCHAR(50) NOT NULL,\n";
    echo "    notif_title VARCHAR(255) NOT NULL,\n";
    echo "    notif_message TEXT,\n";
    echo "    notif_timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
    echo "    is_read CHAR(1) DEFAULT 'N',\n";
    echo "    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,\n";
    echo "    FOREIGN KEY (engagement_idno) REFERENCES engagements(eng_idno) ON DELETE CASCADE,\n";
    echo "    INDEX idx_engagement (engagement_idno),\n";
    echo "    INDEX idx_is_read (is_read),\n";
    echo "    INDEX idx_timestamp (notif_timestamp)\n";
    echo ");\n";
    exit(1);
}
echo "✓ engagement_notifications table exists\n\n";

// Check engagements table
$query = "SHOW TABLES LIKE 'engagements'";
$result = $conn->query($query);
if (!$result || $result->num_rows === 0) {
    echo "❌ ERROR: engagements table does not exist!\n";
    exit(1);
}
echo "✓ engagements table exists\n\n";

// Check if eng_complete_date column exists
$query = "DESCRIBE engagements";
$result = $conn->query($query);
$hasCompleteDate = false;
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'eng_complete_date') {
        $hasCompleteDate = true;
        break;
    }
}
if (!$hasCompleteDate) {
    echo "⚠ WARNING: eng_complete_date column missing (needed for archive notifications)\n";
    echo "Run: ALTER TABLE engagements ADD COLUMN eng_complete_date DATETIME DEFAULT NULL AFTER eng_status;\n\n";
}

// Check engagements with key dates
echo "=== ENGAGEMENTS WITH KEY DATES ===\n";
$query = "SELECT eng_idno, eng_name, eng_final_due, eng_status FROM engagements WHERE eng_final_due IS NOT NULL AND eng_status != 'archived' AND eng_status != 'complete' ORDER BY eng_final_due";
$result = $conn->query($query);
$count = $result->num_rows;
echo "Found $count active engagements with due dates:\n";
while ($row = $result->fetch_assoc()) {
    $daysUntil = round((strtotime($row['eng_final_due']) - time()) / 86400);
    echo "  ID: {$row['eng_idno']}, Name: {$row['eng_name']}, Due: {$row['eng_final_due']} (${daysUntil} days away)\n";
}

// Check what 7 days from now is
$sevenDaysOut = date('Y-m-d', strtotime('+7 days'));
echo "\n=== CHECKING FOR KEY DATE NOTIFICATIONS ===\n";
echo "Today: " . date('Y-m-d H:i:s') . "\n";
echo "7 days from now: " . $sevenDaysOut . "\n\n";

// Check engagements that should trigger notifications
$query = "
    SELECT eng_idno, eng_name, eng_final_due, eng_status 
    FROM engagements 
    WHERE eng_status != 'archived' 
    AND eng_status != 'complete'
    AND eng_final_due IS NOT NULL
    AND DATE(eng_final_due) = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $sevenDaysOut);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "⚠ No engagements found with due date EXACTLY 7 days from now ($sevenDaysOut)\n";
    echo "This is normal if no engagement due date matches exactly.\n";
} else {
    echo "✓ Engagements found with due date 7 days from now ($sevenDaysOut):\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['eng_idno']}: {$row['eng_name']} ({$row['eng_final_due']})\n";
    }
}
$stmt->close();

// Check which engagements have notifications
echo "\n=== ENGAGEMENTS WITH NOTIFICATIONS ===\n";
$query = "SELECT DISTINCT engagement_idno, notif_type FROM engagement_notifications WHERE notif_type = 'upcoming_key_date'";
$result = $conn->query($query);
$notifiedEngagements = [];
while ($row = $result->fetch_assoc()) {
    $notifiedEngagements[$row['engagement_idno']] = true;
}

if (empty($notifiedEngagements)) {
    echo "⚠ No key date notifications sent yet\n";
} else {
    echo "✓ Key date notifications already sent for:\n";
    foreach ($notifiedEngagements as $engId => $val) {
        echo "  - $engId\n";
    }
}

// Check existing notifications (recent)
echo "\n=== RECENT NOTIFICATIONS (Last 10) ===\n";
$query = "SELECT notif_id, engagement_idno, notif_type, notif_title, notif_timestamp, is_read FROM engagement_notifications ORDER BY notif_timestamp DESC LIMIT 10";
$result = $conn->query($query);
if ($result->num_rows === 0) {
    echo "⚠ No notifications found\n";
} else {
    while ($row = $result->fetch_assoc()) {
        $read = $row['is_read'] === 'Y' ? '(read)' : '(unread)';
        echo "  {$row['notif_id']}: {$row['engagement_idno']} - {$row['notif_type']} - {$row['notif_timestamp']} $read\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "If you have an engagement due in 5 days but no notification:\n";
echo "1. Check that the due date is EXACTLY 7 days away (not 5 or 6)\n";
echo "2. Check that the engagement status is not 'archived' or 'complete'\n";
echo "3. Check the log file: /logs/notification-cron.log\n";
echo "4. Make sure cron job is actually running: crontab -l\n";
echo "\nTo manually test, run: php /path/to/pages/notification-cron.php\n";

echo "\n✓ Debug complete\n";
?>