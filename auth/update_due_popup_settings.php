<?php
// auth/update_due_popup_settings.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$daysAhead = $data['days_ahead'] ?? null;

if (!is_numeric($daysAhead) || (int) $daysAhead < 1 || (int) $daysAhead > 90) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Enter a number of days between 1 and 90']);
    exit;
}

if (setAppSetting($conn, 'due_popup_days_ahead', (string) (int) $daysAhead)) {
    echo json_encode(['success' => true, 'message' => 'Due items window saved']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save due items window']);
}
