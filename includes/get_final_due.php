<?php
require 'db.php'; // your DB connection

$eng_id = isset($_GET['eng_id']) ? (int)$_GET['eng_id'] : 0;
$finalDue = null;

if ($eng_id && isset($conn)) {
    $stmt = $conn->prepare("
        SELECT due_date
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
            if (!empty($row['due_date'])) $dates[] = $row['due_date'];
        }
        $stmt->close();

        if (!empty($dates)) $finalDue = min($dates);
    }
}

echo json_encode(['final_due' => $finalDue ?? null]);
