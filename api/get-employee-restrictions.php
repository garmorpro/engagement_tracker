<?php
// api/get-employee-restrictions.php
// Batch lookup of restricted DOL criteria for a list of employee names —
// used by the DOL Generator to know who can't be assigned what before it
// computes a split. Matched by name (see
// includes/migrate_add_employee_restricted_criteria.php for why).
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$names = array_filter(array_map('trim', $data['names'] ?? []));

if (empty($names)) {
    echo json_encode(['success' => true, 'restrictions' => []]);
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($names), '?'));
    $types = str_repeat('s', count($names));
    $stmt = $conn->prepare("SELECT emp_name, emp_restricted_criteria FROM employees WHERE emp_name IN ($placeholders)");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param($types, ...$names);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $restrictions = [];
    foreach ($rows as $row) {
        $list = array_filter(array_map('trim', explode(',', (string) $row['emp_restricted_criteria'])));
        $restrictions[$row['emp_name']] = array_values($list);
    }

    echo json_encode(['success' => true, 'restrictions' => $restrictions]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
