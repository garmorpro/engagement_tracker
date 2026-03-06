<?php
/**
 * Debug - Show All Upcoming Dates in the Window
 */

$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

echo "=== ALL UPCOMING DATES WITHIN NOTIFICATION WINDOWS ===\n\n";

$today = date('Y-m-d');

echo "Today: $today\n\n";

// Get dates within range
$minDays = 5;
$maxDays = 9;

$minDate = date('Y-m-d', strtotime("+$minDays days"));
$maxDate = date('Y-m-d', strtotime("+$maxDays days"));

echo "=== KEY DATES IN THE 5-9 DAY WINDOW ===\n";
echo "Range: $minDate to $maxDate\n\n";

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

$found = false;

while ($row = $result->fetch_assoc()) {
    foreach ($dateFields as $dateCol => $completedCol) {
        $dateValue = $row[$dateCol];
        $completedValue = $row[$completedCol];
        
        if ($dateValue && !$completedValue) {
            $daysAway = round((strtotime($dateValue) - time()) / 86400);
            
            // Check if in range
            if ($daysAway >= $minDays && $daysAway <= $maxDays) {
                echo "✓ {$row['engagement_idno']}: {$row['eng_name']}\n";
                echo "  - {$titleMap[$dateCol]}: $dateValue ($daysAway days away)\n";
                echo "  → Would notify when 7 days before (in " . (7 - $daysAway) . " days)\n\n";
                $found = true;
            }
        }
    }
}

if (!$found) {
    echo "❌ None found in this window\n\n";
}

// Milestones
echo "\n=== MILESTONES IN THE 3-7 DAY WINDOW ===\n";

$minDays2 = 3;
$maxDays2 = 7;

$minDate2 = date('Y-m-d', strtotime("+$minDays2 days"));
$maxDate2 = date('Y-m-d', strtotime("+$maxDays2 days"));

echo "Range: $minDate2 to $maxDate2\n\n";

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
";

$result = $conn->query($query);
$found = false;

while ($row = $result->fetch_assoc()) {
    $daysAway = round((strtotime($row['due_date']) - time()) / 86400);
    
    if ($daysAway >= $minDays2 && $daysAway <= $maxDays2) {
        $milestoneTitle = implode(' ', array_map('ucfirst', explode('_', strtolower($row['milestone_type']))));
        
        echo "✓ {$row['engagement_idno']}: {$row['eng_name']}\n";
        echo "  - $milestoneTitle: {$row['due_date']} ($daysAway days away)\n";
        echo "  → Would notify when 5 days before (in " . (5 - $daysAway) . " days)\n\n";
        $found = true;
    }
}

if (!$found) {
    echo "❌ None found in this window\n\n";
}

?>