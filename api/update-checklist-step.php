<?php
// Ticks or unticks one step of an engagement's PCI checklist. Ticking records
// the date it was completed (server time); ticking an already-ticked step
// keeps its original date; unticking removes it.
if (ob_get_level() > 0) {
    ob_clean();
}

require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = trim((string) ($data['engagement_id'] ?? ''));
$stepKey = trim((string) ($data['step_key'] ?? ''));
$completed = !empty($data['completed']);

$steps = pciChecklistSteps();
if ($engagementId === '' || !isset($steps[$stepKey])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing engagement or unknown checklist step']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT eng_idno FROM engagements WHERE eng_idno = ?");
    $stmt->bind_param('s', $engagementId);
    $stmt->execute();
    $exists = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$exists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Engagement not found']);
        exit;
    }

    if ($completed) {
        $by = (string) ($_SESSION['name'] ?? '');
        // Re-ticking keeps the first completion date (completed_at = completed_at).
        $stmt = $conn->prepare("INSERT INTO engagement_checklist (engagement_idno, step_key, completed_at, completed_by)
                                VALUES (?, ?, NOW(), ?)
                                ON DUPLICATE KEY UPDATE completed_at = completed_at");
        $stmt->bind_param('sss', $engagementId, $stepKey, $by);
    } else {
        $stmt = $conn->prepare("DELETE FROM engagement_checklist WHERE engagement_idno = ? AND step_key = ?");
        $stmt->bind_param('ss', $engagementId, $stepKey);
    }
    $stmt->execute();
    $stmt->close();

    logActivity($conn, 'checklist_step', 'engagement', $engagementId,
        ($completed ? 'Completed' : 'Unchecked') . " \"{$steps[$stepKey]}\" on {$engagementId}");

    echo json_encode(['success' => true, 'message' => 'Checklist updated', 'checklist' => getChecklistMap($conn, $engagementId)]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
