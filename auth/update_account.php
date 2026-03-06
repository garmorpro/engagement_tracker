<?php
require_once '../includes/functions.php';
require_once '../path.php';
require_once '../includes/init.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = intval($_POST['user_id'] ?? 0);
$accountName = trim($_POST['account_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$passcode = trim($_POST['passcode'] ?? '');

// Validation
if (!$userId || !$accountName || !$email || !$passcode) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

if (strlen($passcode) !== 4 || !ctype_digit($passcode)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'PIN must be exactly 4 digits']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

try {
    $accountName = $conn->real_escape_string($accountName);
    $email = $conn->real_escape_string($email);
    $passcode = $conn->real_escape_string($passcode);
    
    $query = "
        UPDATE `service_accounts`
        SET `account_name` = '$accountName',
            `email` = '$email',
            `passcode` = '$passcode'
        WHERE `user_id` = $userId
    ";
    
    if ($conn->query($query)) {
        echo json_encode(['success' => true, 'message' => 'Account updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update account']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>