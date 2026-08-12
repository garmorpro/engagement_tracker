<?php
// api/get-all-employees.php
// Full roster listing for pages/employees.php.
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

try {
    $result = $conn->query("SELECT emp_id, emp_name, emp_role, emp_restricted_criteria FROM employees ORDER BY emp_name ASC");
    $employees = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    echo json_encode(['success' => true, 'employees' => $employees]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
