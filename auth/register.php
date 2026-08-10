<?php
// auth/register.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/settings.php');
    exit;
}

// Sanitize inputs
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$passcode = trim($_POST['passcode'] ?? '');
$role = trim($_POST['role'] ?? 'standard');

// Validate inputs
$errors = [];

if (!$name) $errors[] = 'Full name is required.';
if (!$email) $errors[] = 'Email is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
if (!preg_match('/^\d{4}$/', $passcode)) $errors[] = 'PIN must be 4 digits.';
if (!in_array($role, ['standard', 'admin'])) $errors[] = 'Invalid role.';

if ($errors) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ' . BASE_URL . '/pages/settings.php');
    exit;
}

// Check if email already exists
$stmt = $conn->prepare("SELECT COUNT(*) FROM service_accounts WHERE email = ?");
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    $_SESSION['error'] = 'Email already registered.';
    header('Location: ' . BASE_URL . '/pages/settings.php');
    exit;
}

// Insert new account
$hashedPasscode = password_hash($passcode, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO service_accounts (name, email, passcode, role, status, account_created, account_updated) VALUES (?, ?, ?, ?, 'active', NOW(), NOW())");
$stmt->bind_param('ssss', $name, $email, $hashedPasscode, $role);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    header('Location: ' . BASE_URL . '/pages/settings.php');
    exit;
} else {
    $_SESSION['error'] = 'Failed to create account.';
    header('Location: ' . BASE_URL . '/pages/settings.php');
    exit;
}