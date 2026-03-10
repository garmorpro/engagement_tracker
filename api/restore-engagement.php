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

if (!$engagement_id) {
    echo json_encode(['success' => false, 'message' => 'Missing engagement ID']);
    exit;
}

try {
    $query = "UPDATE engagements 
          SET eng_status = 'complete',
              eng_archive = NULL
          WHERE eng_idno = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param('s', $engagement_id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Engagement restored successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>