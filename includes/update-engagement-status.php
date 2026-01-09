<?php
require 'db.php'; // your DB connection

$data = json_decode(file_get_contents('php://input'), true);

if(isset($data['eng_id'], $data['new_status'])){
    $eng_id = $data['eng_id'];
    $new_status = $data['new_status'];

    $stmt = $conn->prepare("UPDATE engagements SET eng_status = ? WHERE eng_idno = ?");
    $stmt->bind_param("ss", $new_status, $eng_id);
    if($stmt->execute()){
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>