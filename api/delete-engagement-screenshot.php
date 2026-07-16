<?php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = trim($data['engagement_id'] ?? '');

if ($engagementId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing engagement id']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT eng_planning_doc FROM engagements WHERE eng_idno = ?");
    $stmt->bind_param('s', $engagementId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Engagement not found']);
        exit;
    }

    if (!empty($row['eng_planning_doc'])) {
        $path = dirname(__DIR__) . '/uploads/engagement-screenshots/' . basename($row['eng_planning_doc']);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    $stmt = $conn->prepare("UPDATE engagements SET eng_planning_doc = NULL WHERE eng_idno = ?");
    $stmt->bind_param('s', $engagementId);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    echo json_encode(['success' => true, 'message' => 'Screenshot removed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
