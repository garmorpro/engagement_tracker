<?php
// api/link-weekly-status-call.php
// Links two engagements' weekly status calls together so the calendar
// shows them as one combined entry instead of duplicate chips on the same
// day. The linked-to engagement's day is overwritten to match — a shared
// call only makes sense on one day.
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$engagementIdno = trim($data['engagement_idno'] ?? '');
$linkToIdno = trim($data['link_to_idno'] ?? '');

if (!$engagementIdno || !$linkToIdno) {
    echo json_encode(['success' => false, 'message' => 'Missing engagement IDs']);
    exit;
}
if ($engagementIdno === $linkToIdno) {
    echo json_encode(['success' => false, 'message' => "Can't link an engagement to itself"]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT engagement_idno, weekly_status_call_day, weekly_status_call_group
                             FROM engagement_timeline WHERE engagement_idno = ?");
    $stmt->bind_param('s', $engagementIdno);
    $stmt->execute();
    $source = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$source || $source['weekly_status_call_day'] === null) {
        echo json_encode(['success' => false, 'message' => 'Set a day for this engagement\'s weekly status call before linking another engagement to it']);
        exit;
    }

    $groupId = $source['weekly_status_call_group'] ?: $engagementIdno;

    if (!$source['weekly_status_call_group']) {
        $upd = $conn->prepare("UPDATE engagement_timeline SET weekly_status_call_group = ? WHERE engagement_idno = ?");
        $upd->bind_param('ss', $groupId, $engagementIdno);
        $upd->execute();
        $upd->close();
    }

    $upd2 = $conn->prepare("UPDATE engagement_timeline
                             SET weekly_status_call_group = ?, weekly_status_call_day = ?
                             WHERE engagement_idno = ?");
    $upd2->bind_param('sis', $groupId, $source['weekly_status_call_day'], $linkToIdno);
    if (!$upd2->execute()) {
        throw new Exception($upd2->error);
    }
    $upd2->close();

    echo json_encode(['success' => true, 'message' => 'Engagements linked']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
