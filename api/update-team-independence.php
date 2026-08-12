<?php
// api/update-team-independence.php
// Sets whether a team member has confirmed independence from the client
// on this specific engagement. Updates every engagement_team row for that
// person on this engagement (they can have more than one row if staffed
// under more than one audit type's DOL) so the attestation covers the
// whole assignment, not just one row.
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$empIds = array_filter(array_map('intval', $data['emp_ids'] ?? []));
$independent = $data['independent'] ?? null; // 'Y' | 'N' | null (clear back to unanswered)
$engagementIdno = trim($data['engagement_idno'] ?? '');
$empName = trim($data['emp_name'] ?? '');

if (empty($empIds)) {
    echo json_encode(['success' => false, 'message' => 'Missing team member']);
    exit;
}
if ($independent !== null && !in_array($independent, ['Y', 'N'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid value']);
    exit;
}

try {
    $placeholders = implode(',', array_fill(0, count($empIds), '?'));
    $types = str_repeat('i', count($empIds));
    $stmt = $conn->prepare("UPDATE engagement_team SET emp_independent = ? WHERE emp_id IN ($placeholders)");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('s' . $types, $independent, ...$empIds);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    if ($engagementIdno && $empName) {
        $label = $independent === 'Y' ? 'independent' : ($independent === 'N' ? 'not independent' : 'unanswered');
        logActivity($conn, 'independence_updated', 'engagement', $engagementIdno, "Marked {$empName} as {$label} on {$engagementIdno}");
    }

    echo json_encode(['success' => true, 'message' => 'Saved']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
