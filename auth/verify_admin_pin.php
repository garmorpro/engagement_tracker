<?php
// auth/verify_admin_pin.php
header('Content-Type: application/json');
require_once '../includes/functions.php';
require_once '../path.php';
// require_once '../includes/db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['passcode'])) {
    echo json_encode(['success' => false, 'error' => 'Missing passcode']);
    exit;
}

$passcode = trim($input['passcode']);

// Validate format: must be 6 digits
if (!preg_match('/^\d{6}$/', $passcode)) {
    echo json_encode(['success' => false, 'error' => 'Invalid PIN format']);
    exit;
}

// Rate limiting: cap attempts per IP.
$ip = getClientIp();
if (isRateLimited($conn, $ip, 'admin_pin', 5, 15 * 60)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many failed attempts. Please try again in 15 minutes.']);
    exit;
}

// Look up super admin account
$stmt = $conn->prepare("SELECT `passcode` FROM `service_accounts` WHERE `role` = 'super_admin' AND `account_name` = 'admin' LIMIT 1");
$stmt->execute();
$stmt->bind_result($storedPasscode);
if ($stmt->fetch()) {
    $stmt->close();
    if (password_verify($passcode, $storedPasscode)) {
        clearAttempts($conn, $ip, 'admin_pin');
        $_SESSION['admin_verified'] = time();
        echo json_encode(['success' => true]);
    } else {
        recordFailedAttempt($conn, $ip, 'admin_pin');
        echo json_encode(['success' => false, 'error' => 'Incorrect PIN']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Super admin not found']);
}
