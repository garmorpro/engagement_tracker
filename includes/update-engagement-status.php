<?php
require 'db.php';

// Get the JSON input
$data = json_decode(file_get_contents('php://input'), true);

// Debug output to browser console (works with JS fetch)
if (isset($data['eng_id'], $data['new_status'])) {
    $eng_id = $data['eng_id'];
    $new_status = $data['new_status'];

    // Prepare and execute the update
    $stmt = $conn->prepare("UPDATE engagements SET eng_status = ? WHERE eng_idno = ?");
    $stmt->bind_param("ss", $new_status, $eng_id);

    if ($stmt->execute()) {
        // Success response
        echo json_encode(['success' => true, 'eng_id' => $eng_id, 'new_status' => $new_status]);
    } else {
        // DB error response
        echo json_encode([
            'success' => false,
            'error' => 'DB execute failed',
            'db_error' => $stmt->error
        ]);
    }
} else {
    // Missing data
    echo json_encode([
        'success' => false,
        'error' => 'Missing eng_id or new_status',
        'received' => $data
    ]);
}
?>
