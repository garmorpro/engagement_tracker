<?php
// auth/get_due_popup_settings.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminVerified();

header('Content-Type: application/json');

$daysAhead = getAppSetting($conn, 'due_popup_days_ahead');
echo json_encode(['success' => true, 'days_ahead' => ($daysAhead !== null && $daysAhead !== '') ? (int) $daysAhead : 7]);
