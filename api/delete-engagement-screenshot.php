<?php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = trim($data['engagement_id'] ?? '');
$file = basename(trim($data['file'] ?? ''));

if ($engagementId === '' || $file === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing engagement id or file']);
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

    $files = planningDocList($row['eng_planning_doc']);
    if (!in_array($file, $files, true)) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'That planning doc was not found on this engagement']);
        exit;
    }

    $remaining = planningDocStore(array_values(array_diff($files, [$file])));
    $stmt = $conn->prepare("UPDATE engagements SET eng_planning_doc = ? WHERE eng_idno = ?");
    $stmt->bind_param('ss', $remaining, $engagementId);
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }
    $stmt->close();

    // Only after the database change is saved.
    $path = planningDocDir() . '/' . $file;
    if (is_file($path)) {
        @unlink($path);
    }

    echo json_encode(['success' => true, 'message' => 'Planning doc removed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
