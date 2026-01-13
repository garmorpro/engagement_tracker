<?php
header('Content-Type: application/json');
include 'db.php';

$eng_id = (int)($_POST['eng_id'] ?? 0);
$type = $_POST['type'] ?? '';
$name = trim($_POST['name'] ?? '');
$index = (int)($_POST['index'] ?? 0);

if (!in_array($type, ['senior', 'staff']) || $index < 1 || $index > 2 || $eng_id <= 0 || $name === '') {
    echo json_encode(['success' => false]);
    exit;
}

$column = 'eng_' . $type . $index;
$stmt = $conn->prepare("UPDATE engagements SET `$column` = ? WHERE eng_idno = ?");
$stmt->bind_param("si", $name, $eng_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
