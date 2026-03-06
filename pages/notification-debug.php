<?php
/**
 * Comprehensive Debug Script for Notification System
 * Shows exactly what dates we have and what should trigger notifications
 */

$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

echo "=== COMPREHENSIVE NOTIFICATION DEBUG ===\n\n";

// Check tables exist
echo "=== TABLE CHECK ===\n";
$tables = ['engagement_notifications', 'engagement_timeline', 'engagement_milestones', 'engagements'];
foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    $status = ($result && $result->num_rows > 0) ? '✓' : '❌';
    echo "$status $table\n";
}
echo "\n";

// Get all engagement timeline data
echo "=== ALL ENGAGEMENT TIMELINE DATA ===\n";
$query = "
    SELECT 
        t.engagement_idno,
        e.eng_name,
        e.eng_status,
        t.internal_planning_call_date,
        t.internal_planning_call_completed_at,
        t.planning_memo_date,
        t.planning_memo_completed_at,
        t.irl_due_date,
        t.irl_completed_at,
        t.client_planning_call_date,
        t.client_planning_call_completed_at,
        t.fieldwork_date,
        t.fieldwork_completed_at,
        t.leadsheet_date,
        t.leadsheet_completed_at,
        t.conclusion_memo_date,
        t.conclusion_memo_completed_at,
        t.draft_report_due_date,
        t.draft_report_completed_at,
        t.final_report_date,
        t.final_report_completed_at,
        t.archive_date,
        t.archive_completed_at
    FROM engagement_timeline t
    JOIN engagements e ON t.engagement_idno = e.eng_idno
";

$result = $conn->query($query);

$timelineData = [];
while ($row = $result->fetch_assoc()) {
    $timelineData[] = $row;
}

if (empty($timelineData)) {
    echo "No timeline data found\n";
} else {
    foreach ($timelineData as $timeline) {
        echo "\n📋 Engagement: {$timeline['engagement_idno']} - {$timeline['eng_name']}\n";
        echo "   Status: {$timeline['eng_status']}\n";
        echo "   Key Dates:\n";
        
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
        
        foreach ($dateFields as $dateCol => $completedCol) {
            $dateValue = $timeline[$dateCol];
            $completedValue = $timeline[$completedCol];
            
            if ($dateValue) {
                $daysAway = round((strtotime($dateValue) - time()) / 86400);
                $completed = $completedValue ? '✓ COMPLETED' : '⏳ PENDING';
                echo "      - $dateCol: {$dateValue} ($daysAway days away) [$completed]\n";
                if ($completedValue) {
                    echo "        Completed at: $completedValue\n";
                }
            }
        }
    }
}

// Get all milestones
echo "\n\n=== ALL ENGAGEMENT MILESTONES ===\n";
$query = "
    SELECT 
        m.ms_id,
        m.engagement_idno,
        e.eng_name,
        m.milestone_type,
        m.due_date,
        m.is_completed
    FROM engagement_milestones m
    JOIN engagements e ON m.engagement_idno = e.eng_idno
    ORDER BY m.engagement_idno, m.due_date
";

$result = $conn->query($query);

if ($result->num_rows === 0) {
    echo "No milestones found\n";
} else {
    $currentEng = '';
    while ($row = $result->fetch_assoc()) {
        if ($currentEng !== $row['engagement_idno']) {
            $currentEng = $row['engagement_idno'];
            echo "\n📋 Engagement: {$row['engagement_idno']} - {$row['eng_name']}\n";
        }
        
        if ($row['due_date']) {
            $daysAway = round((strtotime($row['due_date']) - time()) / 86400);
            $completed = $row['is_completed'] === 'Y' ? '✓ COMPLETED' : '⏳ PENDING';
            echo "   - {$row['milestone_type']}: {$row['due_date']} ($daysAway days away) [$completed]\n";
        } else {
            $completed = $row['is_completed'] === 'Y' ? '✓ COMPLETED' : '⏳ PENDING';
            echo "   - {$row['milestone_type']}: (no due date) [$completed]\n";
        }
    }
}

// Now show what SHOULD trigger notifications
echo "\n\n=== WHAT SHOULD TRIGGER NOTIFICATIONS ===\n";

$sevenDaysOut = date('Y-m-d', strtotime('+7 days'));
$fiveDaysOut = date('Y-m-d', strtotime('+5 days'));

echo "\nToday: " . date('Y-m-d H:i:s') . "\n";
echo "7 days from now: $sevenDaysOut\n";
echo "5 days from now: $fiveDaysOut\n";

echo "\n📌 KEY DATES that should trigger (pending + 7 days out):\n";
$query = "
    SELECT 
        t.engagement_idno,
        e.eng_name
    FROM engagement_timeline t
    JOIN engagements e ON t.engagement_idno = e.eng_idno
    WHERE e.eng_status NOT IN ('archived', 'complete')
";

$result = $conn->query($query);
$foundKeyDates = false;

while ($row = $result->fetch_assoc()) {
    $timelineResult = $conn->query("SELECT * FROM engagement_timeline WHERE engagement_idno = '{$row['engagement_idno']}'");
    $timeline = $timelineResult->fetch_assoc();
    
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
    
    foreach ($dateFields as $dateCol => $completedCol) {
        $dateValue = $timeline[$dateCol];
        $completedValue = $timeline[$completedCol];
        
        if ($dateValue && !$completedValue && date('Y-m-d', strtotime($dateValue)) === $sevenDaysOut) {
            echo "   ✓ {$row['engagement_idno']}: {$row['eng_name']} - $dateCol\n";
            $foundKeyDates = true;
        }
    }
}

if (!$foundKeyDates) {
    echo "   ⚠ None found\n";
}

echo "\n📌 MILESTONES that should trigger (pending + 5 days out):\n";
$query = "
    SELECT 
        m.engagement_idno,
        e.eng_name,
        m.milestone_type,
        m.due_date
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
    echo "   ⚠ None found\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "   ✓ {$row['engagement_idno']}: {$row['eng_name']} - {$row['milestone_type']}\n";
    }
}
$stmt->close();

// Show existing notifications
echo "\n\n=== EXISTING NOTIFICATIONS ===\n";
$query = "SELECT COUNT(*) as count FROM engagement_notifications";
$result = $conn->query($query);
$row = $result->fetch_assoc();
echo "Total notifications: {$row['count']}\n";

if ($row['count'] > 0) {
    $query = "SELECT engagement_idno, notif_type, notif_title, notif_timestamp FROM engagement_notifications ORDER BY notif_timestamp DESC LIMIT 10";
    $result = $conn->query($query);
    echo "\nMost recent:\n";
    while ($row = $result->fetch_assoc()) {
        echo "   - {$row['engagement_idno']}: {$row['notif_type']} - {$row['notif_title']}\n";
    }
}

echo "\n✓ Debug complete\n";
?>