<?php
header('Content-Type: application/json');
include '../includes/db.php'; // your DB connection

$eng_id = (int)($_POST['eng_id'] ?? 0);
$type = $_POST['type'] ?? '';
$name = trim($_POST['name'] ?? '');
$index = (int)($_POST['index'] ?? 0);

// Validate
if (!in_array($type, ['senior', 'staff']) || $index < 1 || $index > 2 || $eng_id <= 0 || $name === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$column = 'eng_' . $type . $index; // e.g., eng_senior1
echo $column;
$stmt = $conn->prepare("UPDATE engagements SET `$column` = ? WHERE eng_idno = ?");
$stmt->bind_param("si", $name, $eng_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
