<?php
require 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!empty($data['eng_id']) && !empty($data['new_status'])) {
    $eng_id = $data['eng_id'];
    $new_status = $data['new_status'];

    $stmt = $conn->prepare("UPDATE engagements SET eng_status = ? WHERE eng_idno = ?");
    $stmt->bind_param("ss", $new_status, $eng_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'eng_id' => $eng_id, 'new_status' => $new_status]);
    } else {
        echo json_encode(['success' => false, 'error' => 'DB execute failed', 'db_error' => $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Missing eng_id or new_status', 'raw_input' => $data]);
}
?>
