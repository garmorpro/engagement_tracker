<?php
require_once '../path.php';
require_once '../includes/functions.php';
require_once '../pages/notification-helper.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagement_id = $data['engagement_id'] ?? null;
$notification_type = $data['notification_type'] ?? null;

if (!$engagement_id || !$notification_type) {
    echo json_encode(['success' => false, 'message' => 'Missing engagement ID or notification type']);
    exit;
}

try {
    // Get engagement details
    $query = "SELECT eng_name FROM engagements WHERE eng_idno = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $engagement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $engagement = $result->fetch_assoc();
    $stmt->close();
    
    if (!$engagement) {
        echo json_encode(['success' => false, 'message' => 'Engagement not found']);
        exit;
    }
    
    $engName = $engagement['eng_name'];
    $success = false;
    
    if ($notification_type === 'ready_to_archive') {
        $title = 'Ready to Archive';
        $message = $engName . ' has been marked as complete and is ready to archive in 3 days';
        $success = createNotification($engagement_id, 'ready_to_archive', $title, $message);
    }
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Notification created successfully'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create notification']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>