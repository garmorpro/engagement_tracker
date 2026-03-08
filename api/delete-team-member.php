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

if (!$engagementIdno || !$empId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: engagement_idno, emp_id']);
    exit;
}

try {
    // Delete the team member
    $query = "DELETE FROM engagement_team 
              WHERE emp_id = ? AND engagement_idno = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param('ss', $empId, $engagementIdno);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Team member removed successfully'
        ]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting team member: ' . $e->getMessage()
    ]);
}
?>