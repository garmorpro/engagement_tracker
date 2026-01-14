<?php
header('Content-Type: application/json');
require 'db.php';

$ms_id = isset($_POST['ms_id']) ? intval($_POST['ms_id']) : 0;
if (!$ms_id) {
    echo json_encode(['success' => false, 'error' => 'Invalid milestone ID']);
    exit;
}

// Get current status
$stmt = $conn->prepare("SELECT is_completed FROM engagement_milestones WHERE ms_id = ?");
$stmt->bind_param("i", $ms_id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Milestone not found']);
    exit;
}

$newStatus = ($row['is_completed'] === 'Y') ? 'N' : 'Y';

// Update status
$update = $conn->prepare("UPDATE engagement_milestones SET is_completed = ? WHERE ms_id = ?");
$update->bind_param("si", $newStatus, $ms_id);
$update->execute();
$update->close();

echo json_encode(['success' => true, 'new_status' => $newStatus]);
exit;
