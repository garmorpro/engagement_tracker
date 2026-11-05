<?php
// api/save-time-task-map.php
// Saves how the tracker's task names map to the names used in the work timesheet,
// shown on the Time page ("FW Calls" -> "Client Calls"). Kept in app_settings as JSON.
// POST JSON: { map: { "FW Calls": "Client Calls", ... } }   (an empty value clears that task)
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data) || !isset($data['map']) || !is_array($data['map'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$clean = [];
foreach ($data['map'] as $task => $work) {
    $task = mb_substr(trim((string) $task), 0, 60);
    $work = mb_substr(trim((string) $work), 0, 100);
    if ($task !== '' && $work !== '' && count($clean) < 100) {
        $clean[$task] = $work;
    }
}

try {
    if (!setAppSetting($conn, 'time_task_map', json_encode($clean, JSON_UNESCAPED_UNICODE))) {
        throw new RuntimeException('save failed');
    }
    echo json_encode(['success' => true, 'map' => (object) $clean]);
} catch (Throwable $e) {
    error_log('save-time-task-map: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save the task names']);
}
