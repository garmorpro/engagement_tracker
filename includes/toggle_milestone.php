<?php
header('Content-Type: application/json');
require 'db.php';

$ms_id = isset($_POST['ms_id']) ? intval($_POST['ms_id']) : 0;
if (!$ms_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid milestone ID']);
    exit;
}

// Get current milestone info
$stmt = $conn->prepare("SELECT eng_id, milestone_type, is_completed FROM engagement_milestones WHERE ms_id = ?");
$stmt->bind_param("i", $ms_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Milestone not found']);
    exit;
}

$eng_id        = $row['eng_id'];
$milestoneType = $row['milestone_type'];
$newStatus     = ($row['is_completed'] === 'Y') ? 'N' : 'Y';

// Update all milestones for the same engagement and milestone_type
$update = $conn->prepare("
    UPDATE engagement_milestones 
    SET is_completed = ? 
    WHERE eng_id = ? AND milestone_type = ?
");
$update->bind_param("sis", $newStatus, $eng_id, $milestoneType);
$update->execute();
$update->close();

echo json_encode(['success' => true, 'new_status' => $newStatus]);
exit;
