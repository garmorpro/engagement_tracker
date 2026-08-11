<?php
// api/unlink-weekly-status-call.php
// Removes one engagement from its weekly-status-call group. If that
// leaves only one engagement in the group, that one is cleared too — a
// "group" of one engagement isn't a link anymore.
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$engagementIdno = trim($data['engagement_idno'] ?? '');

if (!$engagementIdno) {
    echo json_encode(['success' => false, 'message' => 'Missing engagement ID']);
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

    $upd = $conn->prepare("UPDATE engagement_timeline SET weekly_status_call_group = NULL WHERE engagement_idno = ?");
    $upd->bind_param('s', $engagementIdno);
    $upd->execute();
    $upd->close();

    $remaining = $conn->prepare("SELECT engagement_idno FROM engagement_timeline WHERE weekly_status_call_group = ?");
    $remaining->bind_param('s', $groupId);
    $remaining->execute();
    $rows = $remaining->get_result()->fetch_all(MYSQLI_ASSOC);
    $remaining->close();

    if (count($rows) === 1) {
        $lastIdno = $rows[0]['engagement_idno'];
        $clearLast = $conn->prepare("UPDATE engagement_timeline SET weekly_status_call_group = NULL WHERE engagement_idno = ?");
        $clearLast->bind_param('s', $lastIdno);
        $clearLast->execute();
        $clearLast->close();
    }

    echo json_encode(['success' => true, 'message' => 'Engagement unlinked']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
