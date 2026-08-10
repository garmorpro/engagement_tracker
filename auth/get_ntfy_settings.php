<?php
// auth/get_ntfy_settings.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

header('Content-Type: application/json');

$topicUrl = getAppSetting($conn, 'ntfy_topic_url');
// Missing setting = enabled, so pre-existing installs (before this toggle
// existed) keep working exactly as before until someone flips it off.
$enabled = getAppSetting($conn, 'ntfy_enabled') !== '0';
echo json_encode(['success' => true, 'topic_url' => $topicUrl ?? '', 'enabled' => $enabled]);
