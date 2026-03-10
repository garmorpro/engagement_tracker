<?php
// includes/session_check.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../path.php'; // Make sure BASE_URL is defined

// If not logged in, redirect immediately
if (empty($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL);
    exit;
}

// Set inactivity limit (for testing: 1 minute, production: 15*60)
$inactive_limit = 1 * 60; // seconds

// Check last activity
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    
    if ($inactive_time > $inactive_limit) {
        // Session expired: log out user in DB
        if (!empty($_SESSION['user_id'])) {
            $stmt = $conn->prepare("UPDATE `service_accounts` SET `logged_in` = 0 WHERE `user_id` = ?");
            $stmt->bind_param('i', $_SESSION['user_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Destroy session
        $_SESSION = [];
        session_unset();
        session_destroy();

        // Redirect to login with timeout flag
        header('Location: ' . BASE_URL . '/?timeout=1');
        exit;
    }
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();