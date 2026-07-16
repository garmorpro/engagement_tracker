<?php
// auth/update_slack_webhook.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminVerified();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$webhookUrl = trim($data['webhook_url'] ?? '');

// Empty is allowed (clears/disables Slack notifications). Otherwise it must
// be a well-formed https URL.
if ($webhookUrl !== '' && (!filter_var($webhookUrl, FILTER_VALIDATE_URL) || !str_starts_with($webhookUrl, 'https://'))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'That doesn\'t look like a valid https:// URL']);
    exit;
}

if (setAppSetting($conn, 'slack_webhook_url', $webhookUrl)) {
    echo json_encode(['success' => true, 'message' => 'Slack webhook saved']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save Slack webhook']);
}
