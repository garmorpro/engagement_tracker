<?php
// Clean any output buffer to ensure clean JSON response
ob_clean();

require_once '../path.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

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
    // Start transaction
    $conn->begin_transaction();

    // Delete from engagement_timeline first (foreign key constraint)
    $timelineQuery = "DELETE FROM engagement_timeline WHERE engagement_idno = ?";
    $stmt = $conn->prepare($timelineQuery);
    
    if (!$stmt) {
        throw new Exception('Prepare failed on timeline delete: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $engagement_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete timeline: ' . $stmt->error);
    }
    $stmt->close();

    // Delete from engagement_milestones
    $milestoneQuery = "DELETE FROM engagement_milestones WHERE engagement_idno = ?";
    $stmt = $conn->prepare($milestoneQuery);
    
    if (!$stmt) {
        throw new Exception('Prepare failed on milestone delete: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $engagement_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete milestones: ' . $stmt->error);
    }
    $stmt->close();

    // Delete from engagement_notifications
    $notifQuery = "DELETE FROM engagement_notifications WHERE engagement_idno = ?";
    $stmt = $conn->prepare($notifQuery);
    
    if (!$stmt) {
        throw new Exception('Prepare failed on notification delete: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $engagement_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete notifications: ' . $stmt->error);
    }
    $stmt->close();

    // Delete from engagements
    $engQuery = "DELETE FROM engagements WHERE eng_idno = ?";
    $stmt = $conn->prepare($engQuery);
    
    if (!$stmt) {
        throw new Exception('Prepare failed on engagement delete: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $engagement_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to delete engagement: ' . $stmt->error);
    }
    $stmt->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Engagement deleted successfully'
    ]);

} catch (Exception $e) {
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>