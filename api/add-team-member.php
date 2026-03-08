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

$engagementId = $input['engagement_id'] ?? null;
$empName = $input['emp_name'] ?? null;
$role = $input['role'] ?? null;
$empDol = $input['emp_dol'] ?? null;

if (!$engagementId || !$empName || !$role) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Insert the new team member
    $query = "INSERT INTO engagement_team (engagement_idno, emp_name, role, emp_dol) 
              VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssss', $engagementId, $empName, $role, $empDol);
    
    if ($stmt->execute()) {
        $teamId = $conn->insert_id;
        
        $member = [
            'team_id' => $teamId,
            'engagement_idno' => $engagementId,
            'emp_name' => $empName,
            'role' => $role,
            'emp_dol' => $empDol
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