<?php
/**
 * Debug Script for Notification System
 * Updated to check engagement_timeline and engagement_milestones
 */

$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

echo "=== NOTIFICATION DEBUG ===\n\n";

// Check if table exists
$tableCheckResult = $conn->query("SHOW TABLES LIKE 'engagement_notifications'");
if (!$tableCheckResult || $tableCheckResult->num_rows === 0) {
    echo "❌ ERROR: engagement_notifications table does not exist!\n";
    exit(1);
}
echo "✓ engagement_notifications table exists\n";

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
}
echo "✓ engagements table exists\n\n";

// Check timeline table
echo "=== ENGAGEMENT_TIMELINE KEY DATES ===\n";
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

$query = "SELECT engagement_idno FROM engagement_timeline";
$result = $conn->query($query);
$engagementCount = $result->num_rows;
echo "Found $engagementCount engagements with timeline data\n\n";

// Get all active engagements with timeline dates
$query = "
    SELECT t.engagement_idno, e.eng_name, e.eng_status,
           t.internal_planning_call_date,
           t.planning_memo_date,
           t.irl_due_date,
           t.client_planning_call_date,
           t.fieldwork_date,
           t.leadsheet_date,
           t.conclusion_memo_date,
           t.draft_report_due_date,
           t.final_report_date,
           t.archive_date
    FROM engagement_timeline t
    JOIN engagements e ON t.engagement_idno = e.eng_idno
    WHERE e.eng_status != 'archived' AND e.eng_status != 'complete'
";

$result = $conn->query($query);
while ($row = $result->fetch_assoc()) {
    echo "Engagement: {$row['engagement_idno']} - {$row['eng_name']}\n";
    foreach ($timelineColumns as $col) {
        if ($row[$col]) {
            $daysUntil = round((strtotime($row[$col]) - time()) / 86400);
            echo "  - $col: {$row[$col]} (${daysUntil} days away)\n";
        }
    }
    echo "\n";
}

// Check what 7 days from now is
$sevenDaysOut = date('Y-m-d', strtotime('+7 days'));
$fiveDaysOut = date('Y-m-d', strtotime('+5 days'));

echo "=== TIMELINE DATES MATCHING 7 DAYS FROM NOW ($sevenDaysOut) ===\n";
$dateColumns = implode(", ", $timelineColumns);
$query = "
    SELECT t.engagement_idno, e.eng_name
    FROM engagement_timeline t
    JOIN engagements e ON t.engagement_idno = e.eng_idno
    WHERE e.eng_status != 'archived' AND e.eng_status != 'complete'
";
$result = $conn->query($query);
$found = false;
while ($row = $result->fetch_assoc()) {
    $timelineResult = $conn->query("SELECT * FROM engagement_timeline WHERE engagement_idno = '{$row['engagement_idno']}'");
    $timeline = $timelineResult->fetch_assoc();
    
    foreach ($timelineColumns as $col) {
        if ($timeline[$col] && date('Y-m-d', strtotime($timeline[$col])) === $sevenDaysOut) {
            echo "✓ {$row['engagement_idno']}: {$row['eng_name']} - $col matches!\n";
            $found = true;
        }
    }
}
if (!$found) {
    echo "⚠ No timeline dates found exactly 7 days from now\n";
}

echo "\n=== MILESTONE DATES MATCHING 5 DAYS FROM NOW ($fiveDaysOut) ===\n";
$query = "
    SELECT m.engagement_idno, e.eng_name, m.milestone_type, m.due_date
    FROM engagement_milestones m
    JOIN engagements e ON m.engagement_idno = e.eng_idno
    WHERE m.is_completed = 'N'
    AND m.due_date IS NOT NULL
    AND DATE(m.due_date) = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('s', $fiveDaysOut);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "⚠ No milestones found due exactly 5 days from now\n";
} else {
    echo "✓ Milestones found due 5 days from now:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['engagement_idno']}: {$row['eng_name']} - {$row['milestone_type']} ({$row['due_date']})\n";
    }
}
$stmt->close();

// Check existing notifications
echo "\n=== EXISTING NOTIFICATIONS ===\n";
$query = "SELECT notif_id, engagement_idno, notif_type, notif_title FROM engagement_notifications ORDER BY notif_timestamp DESC LIMIT 10";
$result = $conn->query($query);
if ($result->num_rows === 0) {
    echo "⚠ No notifications found\n";
} else {
    echo "Found {$result->num_rows} notifications:\n";
    while ($row = $result->fetch_assoc()) {
        echo "  - {$row['engagement_idno']}: {$row['notif_type']} - {$row['notif_title']}\n";
    }
}

echo "\n✓ Debug complete\n";
?>