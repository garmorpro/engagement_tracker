<?php
date_default_timezone_set('America/Chicago');
include 'db.php';

// Turn off any PHP warnings / notices being sent to browser
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

$response = ['success' => false, 'error' => 'Unknown error'];

$eng_id = isset($_POST['eng_id']) ? (int)$_POST['eng_id'] : 0;
$due_dates = $_POST['due_date'] ?? [];

if ($eng_id && is_array($due_dates)) {
    $stmt = $conn->prepare("UPDATE engagement_milestones SET due_date = ? WHERE ms_id = ? AND eng_id = ?");
    if ($stmt) {
        foreach ($due_dates as $ms_id => $date) {
            $ms_id = (int)$ms_id;
            $date = $date ?: null; // allow empty string → NULL
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

// Make sure **nothing else** is output before this
echo json_encode($response);
exit;
