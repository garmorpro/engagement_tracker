<?php
// api/delete-employee.php
// Removes someone from the roster. Safe with respect to existing
// engagement assignments — employees isn't foreign-keyed to
// engagement_team (each team row is its own independent copy of the
// name/role/DOL), so this only affects future autocomplete suggestions
// and DOL Generator restriction lookups for that name, not anything
// already saved on an engagement.
require_once '../path.php';
require_once '../includes/functions.php';
requireAdminRole();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$empId = (int) ($data['emp_id'] ?? 0);

if (!$empId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing employee ID']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM employees WHERE emp_id = ?");
    $stmt->bind_param('i', $empId);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Employee removed from roster']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
