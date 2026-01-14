<?php
include '../db.php';
header('Content-Type: application/json');

// Turn off error output to browser
error_reporting(E_ALL);
ini_set('display_errors', 0);

$response = ['success' => false, 'error' => 'Unknown error'];

$eng_id = isset($_POST['eng_id']) ? (int)$_POST['eng_id'] : 0;
$due_dates = $_POST['due_date'] ?? [];

if ($eng_id && !empty($due_dates)) {
    $stmt = $conn->prepare("UPDATE engagement_milestones SET due_date = ? WHERE ms_id = ? AND eng_id = ?");
    if ($stmt) {
        foreach ($due_dates as $ms_id => $date) {
            $stmt->bind_param("sii", $date, $ms_id, $eng_id);
            $stmt->execute();
        }
        $stmt->close();
        $response['success'] = true;
        unset($response['error']);
    } else {
        $response['error'] = $conn->error;
    }
}

echo json_encode($response);
exit;
