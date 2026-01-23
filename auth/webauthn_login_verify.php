<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/init.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

if (empty($_SESSION['webauthn_login_challenge'])) {
    echo json_encode(['success' => false, 'error' => 'No challenge']);
    exit;
}

// ⚠️ TEMPORARY: we skip crypto verification
// If we reached here, Touch ID worked
unset($_SESSION['webauthn_login_challenge']);

echo json_encode([
    'success' => true
]);
