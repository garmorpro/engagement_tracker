<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'db.php'; // your DB connection

// Read raw JSON input
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

// Log input for debugging
file_put_contents('debug_update_status.txt', "Incoming JSON:\n" . print_r($data, true), FILE_APPEND);

// Check required fields
if (isset($data['eng_id'], $data['new_status'])) {
    $eng_id = $data['eng_id'];
    $new_status = $data['new_status'];

    // Prepare statement
    $stmt = $conn->prepare("UPDATE engagements SET eng_status = ? WHERE eng_idno = ?");
    if (!$stmt) {
        // Prepare failed
        file_put_contents('debug_update_status.txt', "Prepare failed: " . $conn->error . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'error' => $conn->error]);
        exit;
    }

    $stmt->bind_param("ss", $new_status, $eng_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        // Execution failed
        file_put_contents('debug_update_status.txt', "Execute failed: " . $stmt->error . "\n", FILE_APPEND);
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }

    $stmt->close();
} else {
    file_put_contents('debug_update_status.txt', "Missing fields in JSON: " . $inputJSON . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'error' => 'Missing eng_id or new_status']);
}

$conn->close();
?>
