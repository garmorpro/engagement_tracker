<?php
header('Content-Type: application/json');
include '../includes/db.php';

$eng_id = $_POST['eng_id'] ?? '';
$type   = $_POST['type'] ?? '';
$name   = trim($_POST['name'] ?? '');
$index  = (int)($_POST['index'] ?? 0);

if (!in_array($type, ['senior','staff']) || $index < 1 || $index > 2 || $eng_id === '' || $name === '') {
    echo json_encode(['success'=>false,'error'=>'Invalid input']);
    exit;
}

$column = 'eng_' . $type . $index;

$stmt = $conn->prepare("UPDATE engagements SET `$column` = ? WHERE eng_idno = ?");
if (!$stmt) {
    echo json_encode(['success'=>false,'error'=>$conn->error]);
    exit;
}

$stmt->bind_param("ss", $name, $eng_id);
$success = $stmt->execute();

echo json_encode(['success'=>$success,'error'=>$stmt->error]);
exit;
