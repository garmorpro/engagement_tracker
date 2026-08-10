<?php
// auth/get_slack_webhook.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

header('Content-Type: application/json');

$webhookUrl = getAppSetting($conn, 'slack_webhook_url');
// Missing setting = enabled, so pre-existing installs (before this toggle
// existed) keep working exactly as before until someone flips it off.
$enabled = getAppSetting($conn, 'slack_enabled') !== '0';
echo json_encode(['success' => true, 'webhook_url' => $webhookUrl ?? '', 'enabled' => $enabled]);
