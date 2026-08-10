<?php
// auth/get_accounts.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

header('Content-Type: application/json');

$result = $conn->query("
    SELECT `user_id`, `name`, `account_name`, `email`, `role`
    FROM `service_accounts`
    WHERE `status` = 'active' AND `role` != 'super_admin'
    ORDER BY `account_name`
");

if ($result) {
    $accounts = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode(['success' => true, 'accounts' => $accounts]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error fetching accounts']);
}