<?php
header('Content-Type: application/json');

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if(!isset($input['eng_id']) || !isset($input['status'])){
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$engId = $input['eng_id'];
$newStatus = $input['status'];

// TODO: include your DB connection
include 'db.php';

$stmt = $conn->prepare("UPDATE engagements SET eng_status = ? WHERE eng_idno = ?");
$stmt->bind_param("ss", $newStatus, $engId);

if($stmt->execute()){
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB error']);
}
