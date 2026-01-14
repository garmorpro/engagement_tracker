<?php
header('Content-Type: application/json');
require 'db.php';

$eng_id = isset($_POST['eng_id']) ? intval($_POST['eng_id']) : 0;
$name   = trim($_POST['name'] ?? '');

if (!$eng_id || !$name) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Delete all entries with same name + eng_id (handles SOC 1 & SOC 2)
$stmt = $conn->prepare("DELETE FROM engagement_team WHERE eng_id = ? AND emp_name = ?");
$stmt->bind_param("is", $eng_id, $name);
$success = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $success]);
exit;
