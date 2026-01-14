<?php
include 'db.php';
header('Content-Type: application/json'); // important

$response = ['success' => false];

$eng_id = $_POST['eng_id'] ?? 0;
$due_dates = $_POST['due_date'] ?? [];

if ($eng_id && !empty($due_dates)) {
    $stmt = $conn->prepare("UPDATE engagement_milestones SET due_date = ? WHERE ms_id = ? AND eng_id = ?");
    foreach ($due_dates as $ms_id => $date) {
        $stmt->bind_param("sii", $date, $ms_id, $eng_id);
        $stmt->execute();
    }
    $response['success'] = true;
}

// Make sure NOTHING else is printed!
echo json_encode($response);
exit;
