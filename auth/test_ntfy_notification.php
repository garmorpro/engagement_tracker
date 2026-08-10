<?php
// auth/test_ntfy_notification.php
// Sends a test push to whatever URL is in the request (not necessarily
// what's saved yet), so the field can be validated before committing to Save.
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$topicUrl = trim($data['topic_url'] ?? '');

if ($topicUrl === '' || !filter_var($topicUrl, FILTER_VALIDATE_URL) || !str_starts_with($topicUrl, 'https://')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Enter a valid https:// URL first']);
    exit;
}

$ch = curl_init($topicUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'Test message from Engagement Tracker — push notifications are working.');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Title: Engagement Tracker',
    'Priority: default',
    'Content-Type: text/plain; charset=utf-8'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 8);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError) {
    echo json_encode(['success' => false, 'message' => 'Request failed: ' . $curlError]);
} elseif ($httpCode === 200) {
    echo json_encode(['success' => true, 'message' => 'Test push sent — check your phone.']);
} else {
    echo json_encode(['success' => false, 'message' => "ntfy responded with HTTP {$httpCode}: {$response}"]);
}
