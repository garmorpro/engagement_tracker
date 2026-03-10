<?php
// includes/session_check.php
session_start();
require_once '../includes/db.php';
require_once '../path.php';

// If not logged in, redirect
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL);
    exit;
}

// Set inactivity limit (15 minutes)
$inactive_limit = 15 * 60; // 15 minutes in seconds

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactive_limit)) {
    // Session expired: log out
    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id) {
        $stmt = $conn->prepare("UPDATE `service_accounts` SET `logged_in` = 0 WHERE `user_id` = ?");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $stmt->close();
    }

    $_SESSION = [];
    session_unset();
    session_destroy();
    header('Location: ' . BASE_URL . '?timeout=1'); // Optional: show message
    exit;
}

// Update last activity
$_SESSION['last_activity'] = time();