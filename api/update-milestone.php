<?php
require_once '../path.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagement_id = $data['engagement_id'] ?? null;
$milestone_id = $data['milestone_id'] ?? null;
$is_completed = $data['is_completed'] ?? null;

if (!$engagement_id || !$milestone_id || !$is_completed) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate is_completed is either Y or N
if ($is_completed !== 'Y' && $is_completed !== 'N') {
    echo json_encode(['success' => false, 'message' => 'Invalid completion status']);
    exit;
}

try {
    // Update the milestone in the database using ms_id
    $query = "UPDATE engagement_milestones 
              SET is_completed = ? 
              WHERE engagement_idno = ? 
              AND ms_id = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    // Bind parameters: s = string, i = integer for ms_id
    $stmt->bind_param('ssi', $is_completed, $engagement_id, $milestone_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Milestone updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>