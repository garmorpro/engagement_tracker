<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../path.php'; // Make sure this file defines BASE_URL

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Set logged_in to 0 in DB
    $stmt = $conn->prepare("UPDATE `service_accounts` SET `logged_in` = 0 WHERE `user_id` = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
}

// Clear session
$_SESSION = [];
session_unset();
session_destroy();

// Redirect to login
header('Location: ' . BASE_URL);
exit;