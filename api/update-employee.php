<?php
// api/update-employee.php
// Edits a roster entry's name/role. Admin-gated — unlike adding a name
// during team assignment (any user, api/add-employee.php), renaming or
// re-role-ing an existing person here changes what shows up for
// everyone's autocomplete and DOL Generator restrictions going forward.
//
// Also cascades the new name/role onto every engagement_team row that
// currently has the old name — engagement_team isn't foreign-keyed to
// employees (each row is its own free-text copy), so without this an
// edit here would only affect new assignments and silently leave every
// engagement someone's already staffed on showing their stale name/role.
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
    $before = $conn->prepare("SELECT emp_name, emp_role FROM employees WHERE emp_id = ?");
    $before->bind_param('i', $empId);
    $before->execute();
    $old = $before->get_result()->fetch_assoc();
    $before->close();

    if (!$old) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

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

    $engagementsUpdated = 0;
    if ($old['emp_name'] !== $empName || strtolower((string) $old['emp_role']) !== $empRole) {
        $cascade = $conn->prepare("UPDATE engagement_team SET emp_name = ?, role = ? WHERE emp_name = ?");
        $cascade->bind_param('sss', $empName, $empRole, $old['emp_name']);
        if (!$cascade->execute()) {
            throw new Exception($cascade->error);
        }
        $engagementsUpdated = $cascade->affected_rows;
        $cascade->close();

        if ($old['emp_name'] !== $empName) {
            logActivity(
                $conn,
                'employee_renamed',
                'employee',
                (string) $empId,
                "Renamed {$old['emp_name']} to {$empName}" . ($engagementsUpdated ? " — updated on {$engagementsUpdated} engagement team assignment(s)" : '')
            );
        }
    }

    echo json_encode(['success' => true, 'message' => 'Employee updated', 'engagements_updated' => $engagementsUpdated]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
