<?php
// auth/logout.php
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../path.php';

$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    $stmt = $conn->prepare("UPDATE `service_accounts` SET `logged_in` = 0 WHERE `user_id` = ?");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $stmt->close();
}

// Destroy session
$_SESSION = [];
session_unset();
session_destroy();

// Detect if this was an AJAX/fetch request
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo json_encode(['success' => true]);
    exit;
}

// Otherwise, redirect to login page
header('Location: ' . BASE_URL . '/?logged_out=1');
exit;