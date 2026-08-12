<?php
// api/update-employee.php
// Edits a roster entry's name/role. Admin-gated — unlike adding a name
// during team assignment (any user, api/add-employee.php), renaming or
// re-role-ing an existing person here changes what shows up for
// everyone's autocomplete and DOL Generator restrictions going forward.
require_once '../path.php';
require_once '../includes/functions.php';
requireAdminRole();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$empId = (int) ($data['emp_id'] ?? 0);
$empName = trim($data['emp_name'] ?? '');
$empRole = strtolower(trim($data['emp_role'] ?? ''));
$validRoles = ['manager', 'senior', 'staff', 'intern'];

if (!$empId || $empName === '' || !in_array($empRole, $validRoles, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Name and a valid role (manager, senior, staff, intern) are required']);
    exit;
}

try {
    $dupe = $conn->prepare("SELECT emp_id FROM employees WHERE emp_name = ? AND emp_id != ?");
    $dupe->bind_param('si', $empName, $empId);
    $dupe->execute();
    if ($dupe->get_result()->fetch_assoc()) {
        $dupe->close();
        echo json_encode(['success' => false, 'message' => 'Another employee already has that name']);
        exit;
    }
    $dupe->close();

    $stmt = $conn->prepare("UPDATE employees SET emp_name = ?, emp_role = ? WHERE emp_id = ?");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('ssi', $empName, $empRole, $empId);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Employee updated']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
