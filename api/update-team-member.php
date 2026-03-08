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
$empId = $input['emp_id'] ?? null;
$empName = $input['emp_name'] ?? null;
$role = $input['role'] ?? null;
$empDol = $input['emp_dol'] ?? null;

if (!$engagementIdno || !$empId || !$empName || !$role) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: engagement_idno, emp_id, emp_name, role']);
    exit;
}

try {
    // Update the team member
    $query = "UPDATE engagement_team 
              SET emp_name = ?, role = ?, emp_dol = ?, emp_updated = NOW()
              WHERE emp_id = ? AND engagement_idno = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('sssss', $empName, $role, $empDol, $empId, $engagementIdno);
    
    if ($stmt->execute()) {
        $member = [
            'emp_id' => $empId,
            'engagement_idno' => $engagementIdno,
            'emp_name' => $empName,
            'role' => $role,
            'emp_dol' => $empDol
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Team member updated successfully',
            'member' => $member
        ]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating team member: ' . $e->getMessage()
    ]);
}
?>