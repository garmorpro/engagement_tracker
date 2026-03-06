<?php
require_once '../path.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$milestone_id = $data['milestone_id'] ?? null;
$old_milestone_type = $data['old_milestone_type'] ?? null;
$milestone_type = $data['milestone_type'] ?? null;
$due_date = $data['due_date'] ?? null;

if (!$milestone_id || !$milestone_type) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $query = "UPDATE engagement_milestones 
              SET due_date = ? 
              WHERE ms_id = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param('si', $due_date, $milestone_id);
    
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