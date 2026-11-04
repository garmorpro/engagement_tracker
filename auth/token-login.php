<?php
// auth/token-login.php
// Redeems a ONE-TIME sign-in link made by api/dashboard-open-token.php, signs in as the account
// named by DASH_LOGIN_USER_ID in .env, and opens the requested engagement.
//
// This page is reachable from the internet (like the login page), so it is deliberately strict:
//   - the token must be 64 hex characters and match a stored hash that is unused and not expired
//   - it is consumed atomically (one UPDATE), so it can only ever work once
//   - failures look identical to the visitor (back to the normal login page) and are rate limited
//   - success is written to the activity log
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
require_once '../includes/db.php';

function tokenLoginFail(): void
{
    header('Location: ' . BASE_URL . '/');
    exit;
}

$ip = getClientIp();
if (isRateLimited($conn, $ip, 'token_ip', 10, 15 * 60)) tokenLoginFail();

$token = (string) ($_GET['t'] ?? '');
$open = (string) ($_GET['open'] ?? '');
$userId = (int) ($_ENV['DASH_LOGIN_USER_ID'] ?? 0);

if (!preg_match('/^[a-f0-9]{64}$/', $token) || $userId <= 0) {
    recordFailedAttempt($conn, $ip, 'token_ip');
    tokenLoginFail();
}
if ($open !== '' && !preg_match('/^[A-Za-z0-9_-]{1,40}$/', $open)) $open = '';

// Redeem: succeeds for exactly one request, only while the token is unused and unexpired.
$hash = hash('sha256', $token);
$stmt = $conn->prepare('UPDATE dash_login_tokens SET used_at = NOW() WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()');
$stmt->bind_param('s', $hash);
$stmt->execute();
$redeemed = $stmt->affected_rows === 1;
$stmt->close();

if (!$redeemed) {
    recordFailedAttempt($conn, $ip, 'token_ip');
    tokenLoginFail();
}

$stmt = $conn->prepare("SELECT `user_id`, `name`, `account_name`, `role`, `email` FROM `service_accounts` WHERE `user_id` = ? AND `status` = 'active' LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) tokenLoginFail();

// Same session setup as auth/login.php (plus a fresh session id).
session_regenerate_id(true);
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['account_name'] = $user['account_name'];
$_SESSION['name'] = $user['name'];
$_SESSION['email'] = $user['email'] ?? '';
$_SESSION['role'] = $user['role'];
$_SESSION['last_activity'] = time();

$stmt = $conn->prepare('UPDATE `service_accounts` SET `logged_in` = 1, `last_active` = NOW() WHERE `user_id` = ?');
$stmt->bind_param('i', $userId);
$stmt->execute();
$stmt->close();

logActivity($conn, 'token_login', 'account', (string) $userId, 'Signed in with a one-time link from the Desk Ctrl Panel');

header('Location: ' . BASE_URL . '/pages/dashboard.php' . ($open !== '' ? '?open=' . rawurlencode($open) : ''));
exit;
