<?php
header('Content-Type: application/json');

// Read JSON input
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (!$data || !isset($data['user_id']) || !isset($data['name']) || !isset($data['email']) || !isset($data['passcode'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

$userId = intval($data['user_id']);
$name = trim($data['name']);
$email = trim($data['email']);
$passcode = trim($data['passcode']);

// Validation
if (!$userId || !$name || !$email || !$passcode) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid input values']));
}

if (strlen($passcode) !== 4 || !ctype_digit($passcode)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'PIN must be exactly 4 digits']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid email address']));
}

// Include your database connection
require_once '../includes/functions.php';
require_once '../path.php';
// require_once '../includes/init.php';

try {
    // Check if $conn exists
    if (!isset($conn) || !$conn) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Database connection not available']));
    }
    
    $query = "
        UPDATE `service_accounts`
        SET `name` = ?,
            `email` = ?,
            `passcode` = ?
        WHERE `user_id` = ?
    ";
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]));
    }
    
    $stmt->bind_param('sssi', $name, $email, $passcode, $userId);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Execute error: ' . $stmt->error]));
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Account updated successfully']);
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>