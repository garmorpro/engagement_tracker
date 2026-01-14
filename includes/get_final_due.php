<?php
require 'db.php'; // DB connection

$eng_id = isset($_GET['eng_id']) ? (int)$_GET['eng_id'] : 0;
$finalDue = null;
$completed = false;

if ($eng_id && isset($conn)) {
    $stmt = $conn->prepare("
        SELECT due_date, completed
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
                if ($row['completed']) $completed = true;
            }
        }
        $stmt->close();

        if (!empty($dates)) $finalDue = min($dates);
    }
}

// Calculate days until or overdue
$today = new DateTime();
$output = [];

if ($finalDue && !$completed) {
    $dueDate = new DateTime($finalDue);
    $diff = $today->diff($dueDate);
    $days = (int)$diff->format('%r%a'); // negative if past

    if ($days < 0) {
        $output = [
            'days' => abs($days),
            'label' => 'Days Overdue',
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
    'completed' => $completed,
    'days_info' => $output
]);
