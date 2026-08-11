<?php
// api/rename-weekly-status-call.php
// Renames a linked weekly-status-call group — applies to every engagement
// sharing it, since the name is shown on the calendar as one combined
// entry, not per-engagement.
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$engagementIdno = trim($data['engagement_idno'] ?? '');
$name = trim($data['name'] ?? '');

if (!$engagementIdno || $name === '') {
    echo json_encode(['success' => false, 'message' => 'Missing engagement ID or name']);
    exit;
}
if (mb_strlen($name) > 100) {
    echo json_encode(['success' => false, 'message' => 'Name is too long (100 characters max)']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT weekly_status_call_group FROM engagement_timeline WHERE engagement_idno = ?");
    $stmt->bind_param('s', $engagementIdno);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $groupId = $row['weekly_status_call_group'] ?? null;
    if (!$groupId) {
        echo json_encode(['success' => false, 'message' => 'This engagement isn\'t linked to another one']);
        exit;
    }

    $upd = $conn->prepare("UPDATE engagement_timeline SET weekly_status_call_group_name = ? WHERE weekly_status_call_group = ?");
    $upd->bind_param('ss', $name, $groupId);
    if (!$upd->execute()) {
        throw new Exception($upd->error);
    }
    $upd->close();

    echo json_encode(['success' => true, 'message' => 'Renamed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
