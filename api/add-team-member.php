<?php
require_once '../path.php';
require_once '../includes/functions.php';

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagementIdno = $input['engagement_idno'] ?? null;
$empName = $input['emp_name'] ?? null;
$role = $input['role'] ?? null;
$empDol = $input['emp_dol'] ?? null;

if (!$engagementIdno || !$empName || !$role) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: engagement_idno, emp_name, role']);
    exit;
}

try {
    // Insert the new team member
    $query = "INSERT INTO engagement_team (engagement_idno, emp_name, role, emp_dol, audit_type) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $auditType = 'General';
    $stmt->bind_param('sssss', $engagementIdno, $empName, $role, $empDol, $auditType);
    
    if ($stmt->execute()) {
        $empId = $conn->insert_id;
        
        $member = [
            'emp_id' => $empId,
            'engagement_idno' => $engagementIdno,
            'emp_name' => $empName,
            'role' => $role,
            'emp_dol' => $empDol,
            'audit_type' => $auditType
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Team member added successfully',
            'member' => $member
        ]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding team member: ' . $e->getMessage()
    ]);
}
?>