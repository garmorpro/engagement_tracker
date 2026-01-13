<?php
header('Content-Type: application/json');
include '../includes/db.php';

$eng_id = $_POST['eng_id'] ?? '';
$type   = $_POST['type'] ?? ''; // "manager", "senior" or "staff"
$name   = trim($_POST['name'] ?? '');

// Validate input
if (!in_array($type, ['manager','senior','staff']) || $eng_id === '' || $name === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Map type to role
$role = ucfirst($type); // "Manager", "Senior", "Staff"

// Check if manager already exists (only for manager type)
if ($role === 'Manager') {
    $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM engagement_team WHERE eng_id = ? AND role = 'Manager'");
    $stmt_check->bind_param("i", $eng_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($result_check['count'] > 0) {
        echo json_encode(['success' => false, 'error' => 'Manager already assigned for this engagement']);
        exit;
    }
}

// Insert new team member into engagement_team
$stmt = $conn->prepare("
    INSERT INTO engagement_team (eng_id, emp_name, role, audit_type, emp_dol)
    VALUES (?, ?, ?, NULL, NULL)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

$stmt->bind_param("iss", $eng_id, $name, $role);
$success = $stmt->execute();

echo json_encode(['success' => $success, 'error' => $stmt->error]);
exit;
