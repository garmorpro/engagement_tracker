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
$teamId = $input['team_id'] ?? null;

if (!$engagementId || !$teamId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Delete the team member
    $query = "DELETE FROM engagement_team 
              WHERE team_id = ? AND engagement_idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ss', $teamId, $engagementId);
    
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