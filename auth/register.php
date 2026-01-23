<?php
// auth/register.php
session_start();
require_once '../includes/functions.php';
require_once '../path.php';
// require_once '../includes/db.php';

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL);
    exit;
}

// Sanitize inputs
$account_name = trim($_POST['account_name'] ?? '');
$passcode = trim($_POST['passcode'] ?? '');
$role = trim($_POST['role'] ?? 'standard');

// Validate inputs
$errors = [];

if (!$account_name) $errors[] = 'Account name is required.';
if (!preg_match('/^\d{4}$/', $passcode)) $errors[] = 'PIN must be 4 digits.';
if (!in_array($role, ['standard', 'admin'])) $errors[] = 'Invalid role.';

if ($errors) {
    $_SESSION['error'] = implode('<br>', $errors);
    header('Location: ' . BASE_URL);
    exit;
}

// Check if account name already exists
$stmt = $conn->prepare("SELECT COUNT(*) FROM service_accounts WHERE account_name = ?");
$stmt->bind_param('s', $account_name);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

if ($count > 0) {
    $_SESSION['error'] = 'Account name already exists.';
    header('Location: ' . BASE_URL);
    exit;
}

// Insert new account
$stmt = $conn->prepare("INSERT INTO service_accounts (account_name, passcode, role, status, account_created, account_updated) VALUES (?, ?, ?, 'active', NOW(), NOW())");
$stmt->bind_param('sss', $account_name, $passcode, $role);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    // Redirect to dashboard
    header('Location: ' . BASE_URL . '/pages/dashboard.php');
    exit;
} else {
    $_SESSION['error'] = 'Failed to create account.';
    header('Location: ' . BASE_URL);
    exit;
}
