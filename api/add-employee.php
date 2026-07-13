<?php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$empName = trim($data['emp_name'] ?? '');
$empRole = strtolower(trim($data['emp_role'] ?? ''));
$validRoles = ['manager', 'senior', 'staff', 'intern'];

if ($empName === '' || !in_array($empRole, $validRoles, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name and a valid role (manager, senior, staff, intern) are required']);
    exit;
}

try {
    // If this name already exists in the roster, just return it rather than erroring —
    // this endpoint is a convenience "add if missing" action.
    $stmt = $conn->prepare("SELECT emp_id, emp_name, emp_role FROM employees WHERE emp_name = ?");
    $stmt->bind_param('s', $empName);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        echo json_encode(['success' => true, 'employee' => $existing, 'existed' => true]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO employees (emp_name, emp_role) VALUES (?, ?)");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('ss', $empName, $empRole);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $newId = $conn->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'employee' => ['emp_id' => $newId, 'emp_name' => $empName, 'emp_role' => $empRole],
        'existed' => false,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
