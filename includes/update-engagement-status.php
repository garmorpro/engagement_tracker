<?php
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

// Debug output
file_put_contents('debug_update_status.txt', print_r($data, true));

if (isset($data['eng_id'], $data['new_status'])) {
    $eng_id = $data['eng_id'];
    $new_status = $data['new_status'];

    $stmt = $conn->prepare("UPDATE engagements SET eng_status = ? WHERE eng_idno = ?");
    $stmt->bind_param("ss", $new_status, $eng_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'DB execute failed']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing eng_id or new_status']);
}
?>
