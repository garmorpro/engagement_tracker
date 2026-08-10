<?php
// auth/update_slack_webhook.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$webhookUrl = trim($data['webhook_url'] ?? '');
// 'enabled' is optional so the toggle can PATCH just the on/off state
// without requiring a webhook_url in the same request; when omitted, keep
// whatever is already saved (default enabled, matching get_slack_webhook.php).
$enabled = array_key_exists('enabled', $data)
    ? (bool) $data['enabled']
    : (getAppSetting($conn, 'slack_enabled') !== '0');

// Empty is allowed (clears the webhook, which also disables Slack in
// practice). Otherwise it must be a well-formed https URL.
if ($webhookUrl !== '' && (!filter_var($webhookUrl, FILTER_VALIDATE_URL) || !str_starts_with($webhookUrl, 'https://'))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'That doesn\'t look like a valid https:// URL']);
    exit;
}

$saved = setAppSetting($conn, 'slack_webhook_url', $webhookUrl)
    && setAppSetting($conn, 'slack_enabled', $enabled ? '1' : '0');

if ($saved) {
    echo json_encode(['success' => true, 'message' => 'Slack settings saved']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save Slack settings']);
}
