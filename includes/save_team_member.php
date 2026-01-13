<?php
header('Content-Type: application/json');
include '../includes/db.php';

$eng_id = $_POST['eng_id'] ?? '';
$type   = $_POST['type'] ?? ''; // "senior" or "staff"
$name   = trim($_POST['name'] ?? '');
$index  = (int)($_POST['index'] ?? 0); // not strictly needed but you can keep for validation

// Validate input
if (!in_array($type, ['senior','staff']) || $eng_id === '' || $name === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Map type to role
$role = ucfirst($type); // 'Senior' or 'Staff'

// Insert new team member into engagement_team
$stmt = $conn->prepare("
    INSERT INTO engagement_team (eng_id, emp_name, role)
    VALUES (?, ?, ?)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

$stmt->bind_param("iss", $eng_id, $name, $role);
$success = $stmt->execute();

echo json_encode(['success' => $success, 'error' => $stmt->error]);
exit;
