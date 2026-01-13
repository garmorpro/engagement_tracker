<?php
header('Content-Type: application/json');
include 'db.php';

$eng_id = ($_POST['eng_id'] ?? 0);
$type = $_POST['type'] ?? '';
$name = trim($_POST['name'] ?? '');
$index = (int)($_POST['index'] ?? 0);

// Validation
if (!in_array($type, ['senior','staff']) || $index < 1 || $index > 2 || $eng_id <= 0 || $name === '') {
    echo json_encode(['success'=>false,'error'=>'Invalid input']);
    exit;
}

$column = 'eng_' . $type . $index;

// Prepare
$stmt = $conn->prepare("UPDATE engagements SET `$column` = ? WHERE eng_idno = ?");
if(!$stmt){
    echo json_encode(['success'=>false,'error'=>$conn->error]);
    exit;
}

// Bind & execute
$stmt->bind_param("si",$name,$eng_id);
$success = $stmt->execute();

echo json_encode(['success'=>$success,'error'=>$stmt->error]);
