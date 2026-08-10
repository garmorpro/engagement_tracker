<?php
// auth/get_ntfy_settings.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminVerified();

header('Content-Type: application/json');

$topicUrl = getAppSetting($conn, 'ntfy_topic_url');
echo json_encode(['success' => true, 'topic_url' => $topicUrl ?? '']);
