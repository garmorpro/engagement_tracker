<?php
// auth/register.php
session_start();
require_once '../includes/functions.php';
require_once '../path.php';

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL);
    exit;
}

// Sanitize inputs
$name = trim($_POST['name'] ?? '');
$account_name = trim($_POST['account_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$passcode = trim($_POST['passcode'] ?? '');
$role = trim($_POST['role'] ?? 'standard');

// Validate inputs
$errors = [];

if (!$name) $errors[] = 'Name is required.';
if (!$account_name) $errors[] = 'Account name is required.';
if (!$email) $errors[] = 'Email is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
if (!preg_match('/^\d{4}$/', $passcode)) $errors[] = 'PIN must be 4 digits.';
if (!in_array($role, ['standard', 'admin'])) $errors[] = 'Invalid role.';

if ($errors) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ' . BASE_URL);
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
    header('Location: ' . BASE_URL);
    exit;
}

// Insert new account
$stmt = $conn->prepare("INSERT INTO service_accounts (name, account_name, email, passcode, role, status, account_created, account_updated) VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW())");
$stmt->bind_param('sssss', $name, $account_name, $email, $passcode, $role);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    // Redirect to dashboard
    header('Location: ' . BASE_URL . '/');
    exit;
} else {
    $_SESSION['error'] = 'Failed to create account.';
    header('Location: ' . BASE_URL);
    exit;
}