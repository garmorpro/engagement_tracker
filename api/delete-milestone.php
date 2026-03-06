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
$engagement_id = $data['engagement_id'] ?? null;

if (!$milestone_id || !$engagement_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $query = "DELETE FROM engagement_milestones 
              WHERE milestone_idno = ? AND engagement_idno = ?";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param('is', $milestone_id, $engagement_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Milestone deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database delete failed: ' . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>