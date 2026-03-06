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
$milestone_type = $data['milestone_type'] ?? null;
$is_completed = $data['is_completed'] ?? null;

if (!$engagement_id || !$milestone_type || !$is_completed) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Validate is_completed is either Y or N
if ($is_completed !== 'Y' && $is_completed !== 'N') {
    echo json_encode(['success' => false, 'message' => 'Invalid completion status']);
    exit;
}

try {
    // Update the milestone in the database
    $query = "UPDATE milestones 
              SET is_completed = :is_completed 
              WHERE engagement_idno = :engagement_id 
              AND milestone_type = :milestone_type";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':is_completed', $is_completed);
    $stmt->bindParam(':engagement_id', $engagement_id);
    $stmt->bindParam(':milestone_type', $milestone_type);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Milestone updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}