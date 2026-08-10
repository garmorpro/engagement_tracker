<?php
// auth/update_ntfy_settings.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$topicUrl = trim($data['topic_url'] ?? '');
// 'enabled' is optional so the toggle can PATCH just the on/off state
// without requiring a topic_url in the same request; when omitted, keep
// whatever is already saved (default enabled, matching get_ntfy_settings.php).
$enabled = array_key_exists('enabled', $data)
    ? (bool) $data['enabled']
    : (getAppSetting($conn, 'ntfy_enabled') !== '0');

// Empty is allowed (clears the topic, which also disables push in
// practice). Otherwise it must be a well-formed https URL — this is a
// topic URL (e.g. https://ntfy.sh/your-private-topic-name), not just a
// bare topic name, so it also works with a self-hosted ntfy server.
if ($topicUrl !== '' && (!filter_var($topicUrl, FILTER_VALIDATE_URL) || !str_starts_with($topicUrl, 'https://'))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'That doesn\'t look like a valid https:// URL']);
    exit;
}

$saved = setAppSetting($conn, 'ntfy_topic_url', $topicUrl)
    && setAppSetting($conn, 'ntfy_enabled', $enabled ? '1' : '0');

if ($saved) {
    echo json_encode(['success' => true, 'message' => 'Push notification settings saved']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to save push notification settings']);
}
