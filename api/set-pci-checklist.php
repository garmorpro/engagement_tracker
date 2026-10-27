<?php
// "Add PCI checklist" / "Remove" for an engagement that has PCI alongside other
// audit types. Only flips whether the checklist section is shown; ticked steps
// are kept, so adding it back later restores them. PCI-only engagements always
// have the checklist (no switch), and engagements without PCI can't have it.
if (ob_get_level() > 0) {
    ob_clean();
}

require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = trim((string) ($data['engagement_id'] ?? ''));
$enabled = !empty($data['enabled']) ? 1 : 0;

if ($engagementId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing engagement id']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT eng_idno, eng_audit_type, eng_pci_checklist FROM engagements WHERE eng_idno = ?");
    $stmt->bind_param('s', $engagementId);
    $stmt->execute();
    $eng = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$eng) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Engagement not found']);
        exit;
    }
    $mode = pciChecklistMode($eng);
    if ($mode === 'none') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'This engagement has no PCI audit type.']);
        exit;
    }
    if ($mode === 'only') {
        echo json_encode(['success' => true, 'message' => 'PCI-only engagements always have the checklist']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE engagements SET eng_pci_checklist = ? WHERE eng_idno = ?");
    $stmt->bind_param('is', $enabled, $engagementId);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, 'checklist_toggled', 'engagement', $engagementId, ($enabled ? 'Added' : 'Removed') . " the PCI checklist on {$engagementId}");
    echo json_encode(['success' => true, 'message' => $enabled ? 'PCI checklist added' : 'PCI checklist removed']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
