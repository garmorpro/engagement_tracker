<?php
require_once '../path.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagement_id = $data['engagement_id'] ?? null;
$milestone_type = $data['milestone_type'] ?? null;
$due_date = $data['due_date'] ?? null;

if (!$engagement_id || !$milestone_type) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $query = "INSERT INTO engagement_milestones 
              (engagement_idno, milestone_type, due_date, is_completed) 
              VALUES (?, ?, ?, 'N')";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param('sss', $engagement_id, $milestone_type, $due_date);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Milestone added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database insert failed: ' . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>