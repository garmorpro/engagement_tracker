<?php
// api/update-employee-restrictions.php
// Sets which DOL criteria an employee is restricted from (not yet trained
// on) — applies to every engagement they're staffed on, since it's stored
// on the roster (employees), matched by name, not per-engagement.
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$empName = trim($data['emp_name'] ?? '');
$role = trim($data['role'] ?? 'staff');
$restricted = array_filter(array_map('trim', $data['restricted'] ?? []));

if ($empName === '') {
    echo json_encode(['success' => false, 'message' => 'Missing employee name']);
    exit;
}

try {
    $value = implode(',', $restricted);

    // The team member may not have a roster row yet (e.g. added to
    // engagement_team before the employees table existed, or a name typo)
    // — upsert rather than assume one exists.
    $stmt = $conn->prepare("INSERT INTO employees (emp_name, emp_role, emp_restricted_criteria)
                             VALUES (?, ?, ?)
                             ON DUPLICATE KEY UPDATE emp_restricted_criteria = VALUES(emp_restricted_criteria)");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('sss', $empName, $role, $value);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Restrictions saved']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
