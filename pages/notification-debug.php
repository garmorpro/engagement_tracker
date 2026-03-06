<?php
/**
 * Simplified Debug - Only Show What Would Be Notified
 */

$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

echo "=== NOTIFICATIONS THAT WOULD BE SENT ===\n\n";

$today = date('Y-m-d');
$sevenDaysOut = date('Y-m-d', strtotime('+7 days'));
$fiveDaysOut = date('Y-m-d', strtotime('+5 days'));

echo "Today: $today\n";
echo "7 days out: $sevenDaysOut\n";
echo "5 days out: $fiveDaysOut\n\n";

// KEY DATES FROM TIMELINE
echo "=== KEY DATES (Timeline) - Would Notify 7 Days Before ===\n";
echo "Looking for dates = $sevenDaysOut with NO *_completed_at\n\n";

$query = "
    SELECT 
        t.engagement_idno,
        e.eng_name,
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
    WHERE e.eng_status NOT IN ('archived', 'complete')
";

$result = $conn->query($query);

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

$titleMap = [
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

$keyDateNotifications = [];

while ($row = $result->fetch_assoc()) {
    foreach ($dateFields as $dateCol => $completedCol) {
        $dateValue = $row[$dateCol];
        $completedValue = $row[$completedCol];
        
        if ($dateValue && !$completedValue && date('Y-m-d', strtotime($dateValue)) === $sevenDaysOut) {
            $keyDateNotifications[] = [
                'engagement_idno' => $row['engagement_idno'],
                'eng_name' => $row['eng_name'],
                'notif_type' => 'upcoming_key_date',
                'notif_title' => 'Upcoming Key Date',
                'notif_message' => $row['eng_name'] . ' - ' . $titleMap[$dateCol] . ' is due in 7 days',
                'date_column' => $dateCol,
                'date_value' => $dateValue
            ];
        }
    }
}

if (empty($keyDateNotifications)) {
    echo "❌ None\n";
} else {
    foreach ($keyDateNotifications as $notif) {
        echo "✓ engagement_idno: {$notif['engagement_idno']}\n";
        echo "  notif_type: {$notif['notif_type']}\n";
        echo "  notif_title: {$notif['notif_title']}\n";
        echo "  notif_message: {$notif['notif_message']}\n";
        echo "  (date column: {$notif['date_column']} = {$notif['date_value']})\n\n";
    }
}

// MILESTONES
echo "=== MILESTONES - Would Notify 5 Days Before ===\n";
echo "Looking for due_date = $fiveDaysOut with is_completed = 'N'\n\n";

$query = "
    SELECT 
        m.engagement_idno,
        e.eng_name,
        m.milestone_type,
        m.due_date,
        m.is_completed
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

$milestoneNotifications = [];

while ($row = $result->fetch_assoc()) {
    $milestoneTitle = implode(' ', array_map('ucfirst', explode('_', strtolower($row['milestone_type']))));
    
    $milestoneNotifications[] = [
        'engagement_idno' => $row['engagement_idno'],
        'eng_name' => $row['eng_name'],
        'notif_type' => 'upcoming_milestone',
        'notif_title' => 'Upcoming Milestone',
        'notif_message' => $row['eng_name'] . ' - ' . $milestoneTitle . ' due in 5 days',
        'milestone_type' => $row['milestone_type'],
        'due_date' => $row['due_date']
    ];
}

$stmt->close();

if (empty($milestoneNotifications)) {
    echo "❌ None\n";
} else {
    foreach ($milestoneNotifications as $notif) {
        echo "✓ engagement_idno: {$notif['engagement_idno']}\n";
        echo "  notif_type: {$notif['notif_type']}\n";
        echo "  notif_title: {$notif['notif_title']}\n";
        echo "  notif_message: {$notif['notif_message']}\n";
        echo "  (milestone: {$notif['milestone_type']} = {$notif['due_date']})\n\n";
    }
}

// ARCHIVE READY
echo "=== READY TO ARCHIVE - Would Notify 3+ Days After Complete ===\n";
echo "Looking for eng_complete_date <= " . date('Y-m-d', strtotime('-3 days')) . "\n\n";

$threeDeployOut = date('Y-m-d', strtotime('-3 days'));
$query = "
    SELECT 
        eng_idno,
        eng_name,
        eng_complete_date
    FROM engagements
    WHERE eng_status = 'complete'
    AND eng_complete_date IS NOT NULL
    AND DATE(eng_complete_date) <= ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('s', $threeDeployOut);
$stmt->execute();
$result = $stmt->get_result();

$archiveNotifications = [];

while ($row = $result->fetch_assoc()) {
    $archiveNotifications[] = [
        'engagement_idno' => $row['eng_idno'],
        'eng_name' => $row['eng_name'],
        'notif_type' => 'ready_to_archive',
        'notif_title' => 'Ready to Archive',
        'notif_message' => $row['eng_name'] . ' has been complete for 3+ days and is ready to archive',
        'complete_date' => $row['eng_complete_date']
    ];
}

$stmt->close();

if (empty($archiveNotifications)) {
    echo "❌ None\n";
} else {
    foreach ($archiveNotifications as $notif) {
        echo "✓ engagement_idno: {$notif['engagement_idno']}\n";
        echo "  notif_type: {$notif['notif_type']}\n";
        echo "  notif_title: {$notif['notif_title']}\n";
        echo "  notif_message: {$notif['notif_message']}\n";
        echo "  (complete date: {$notif['complete_date']})\n\n";
    }
}

// SUMMARY
echo "\n=== SUMMARY ===\n";
$totalNotifs = count($keyDateNotifications) + count($milestoneNotifications) + count($archiveNotifications);
echo "Total notifications that would be sent: $totalNotifs\n";
echo "  - Key Dates: " . count($keyDateNotifications) . "\n";
echo "  - Milestones: " . count($milestoneNotifications) . "\n";
echo "  - Archive Ready: " . count($archiveNotifications) . "\n";

?>