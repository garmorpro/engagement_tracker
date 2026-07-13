<?php
// auth/login.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
require_once '../includes/db.php'; // Make sure $conn is available

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

// Rate limiting: cap attempts per IP overall, and per IP+user_id specifically.
$ip = getClientIp();
$userIdentifier = $ip . ':' . $user_id;

if (isRateLimited($conn, $ip, 'login_ip', 20, 15 * 60) || isRateLimited($conn, $userIdentifier, 'login_user', 5, 15 * 60)) {
    $_SESSION['error'] = 'Too many failed attempts. Please try again in 15 minutes.';
    header('Location: ' . BASE_URL);
    exit;
}

// Fetch user
$stmt = $conn->prepare("
    SELECT `user_id`, `name`, `account_name`, `passcode`, `role`, `status`, `email`
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
    recordFailedAttempt($conn, $ip, 'login_ip');
    recordFailedAttempt($conn, $userIdentifier, 'login_user');
    $_SESSION['error'] = 'User not found or inactive.';
    header('Location: ' . BASE_URL);
    exit;
}

// Check passcode
$expected_length = ($user['role'] === 'super_admin') ? 6 : 4;
if (!preg_match("/^\d{" . $expected_length . "}$/", $passcode) || !password_verify($passcode, $user['passcode'])) {
    recordFailedAttempt($conn, $ip, 'login_ip');
    recordFailedAttempt($conn, $userIdentifier, 'login_user');
    $_SESSION['error'] = 'Incorrect PIN.';
    header('Location: ' . BASE_URL);
    exit;
}

clearAttempts($conn, $ip, 'login_ip');
clearAttempts($conn, $userIdentifier, 'login_user');

// Set session variables
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['account_name'] = $user['account_name'];
$_SESSION['name'] = $user['name'];
$_SESSION['email'] = $user['email'] ?? '';
$_SESSION['role'] = $user['role'];
$_SESSION['last_activity'] = time(); // Track session activity

// Update logged_in and last_active in DB
$stmt = $conn->prepare("
    UPDATE `service_accounts`
    SET `logged_in` = 1, `last_active` = NOW()
    WHERE `user_id` = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->close();

// Redirect to dashboard
header('Location: ' . BASE_URL . '/pages/dashboard.php');
exit;