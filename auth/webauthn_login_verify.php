<?php
error_reporting(E_ERROR);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once '../includes/init.php'; // DB connection

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Retrieve stored challenge
$challenge = $_SESSION['webauthn_login_challenge'] ?? null;
if (!$challenge) {
    echo json_encode(['success' => false, 'error' => 'No challenge found']);
    exit;
}

// For demo: mark login successful
$response = ['success' => true];

// Clean any output before sending JSON
ob_clean();
echo json_encode($response);
exit;
