<?php
// auth/login.php
session_start();
require_once '../includes/functions.php';
require_once '../path.php';
// require_once '../includes/db.php';

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL);
    exit;
}

// Get POST data
$user_id = intval($_POST['user_id'] ?? 0);
$passcode = trim($_POST['passcode'] ?? '');

// Validate input
if (!$user_id || !$passcode) {
    $_SESSION['error'] = 'Invalid login credentials.';
    header('Location: ' . BASE_URL);
    exit;
}

// Fetch user
$stmt = $conn->prepare("
    SELECT `user_id`, `account_name`, `passcode`, `role`, `status`
    FROM `service_accounts`
    WHERE `user_id` = ? AND `status` = 'active'
    LIMIT 1
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['error'] = 'User not found or inactive.';
    header('Location: ' . BASE_URL);
    exit;
}

// Check passcode
$expected_length = ($user['role'] === 'super_admin') ? 6 : 4;
if (!preg_match("/^\d{" . $expected_length . "}$/", $passcode) || $passcode !== $user['passcode']) {
    $_SESSION['error'] = 'Incorrect PIN.';
    header('Location: ' . BASE_URL);
    exit;
}

// Update logged_in and last_active
$stmt = $conn->prepare("
    UPDATE `service_accounts`
    SET `logged_in` = 1, `last_active` = NOW()
    WHERE `user_id` = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->close();

// Set session
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['account_name'] = $user['account_name'];
$_SESSION['role'] = $user['role'];

// Redirect to dashboard
header('Location: ' . BASE_URL . '/pages/dashboard.php');
exit;
