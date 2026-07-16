<?php
// auth/get_slack_webhook.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminVerified();

header('Content-Type: application/json');

$webhookUrl = getAppSetting($conn, 'slack_webhook_url');
echo json_encode(['success' => true, 'webhook_url' => $webhookUrl ?? '']);
