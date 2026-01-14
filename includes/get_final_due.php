<?php
require 'db.php'; // DB connection

$eng_id = isset($_GET['eng_id']) ? (int)$_GET['eng_id'] : 0;
$finalDue = null;
$isCompleted = 'N'; // default

if ($eng_id && isset($conn)) {
    $stmt = $conn->prepare("
        SELECT due_date, is_completed
        FROM engagement_milestones
        WHERE eng_id = ?
          AND milestone_type LIKE 'final_report_due%'
        ORDER BY ms_id ASC
    ");
    if ($stmt) {
        $stmt->bind_param("i", $eng_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $dates = [];
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['due_date'])) {
                $dates[] = $row['due_date'];
                if ($row['is_completed'] === 'Y') $isCompleted = 'Y';
            }
        }
        $stmt->close();

        if (!empty($dates)) $finalDue = min($dates);
    }
}

// Calculate days until or overdue (only if not completed)
$today = new DateTime();
$output = [];

if ($finalDue && $isCompleted !== 'Y') {
    $dueDate = new DateTime($finalDue);
    $diff = $today->diff($dueDate);
    $days = (int)$diff->format('%r%a'); // negative if past

    if ($days < 0) {
        $output = [
            'days' => abs($days),
            'label' => 'Days Late',
            'color' => '#e53e3e'
        ];
    } else {
        $output = [
            'days' => $days,
            'label' => 'Days Until Due',
            'color' => '#000'
        ];
    }
}

echo json_encode([
    'final_due' => $finalDue ?? null,
    'is_completed' => $isCompleted,
    'days_info' => $output
]);
