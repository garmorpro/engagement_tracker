<?php
require_once '../includes/functions.php';
require_once '../path.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Read the raw input to detect if it's JSON
$raw_input = file_get_contents('php://input');
$data = [];

// If we have raw input, try to parse as JSON (API request)
if (!empty($raw_input)) {
    $data = json_decode($raw_input, true) ?? [];
    $isJsonRequest = !empty($data); // Successfully parsed JSON
} else {
    // Fall back to POST data (form submission)
    $data = $_POST;
    $isJsonRequest = false;
}

// AUTHORIZATION CHECK:
// - Allow JSON requests (API calls from admin dashboard) - NO session needed
// - Require session for form POST requests - session needed
// if (!$isJsonRequest && !isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Unauthorized']);
//     exit;
// }

$userId = intval($data['user_id'] ?? 0);
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$passcode = trim($data['passcode'] ?? '');

// Validation
if (!$userId || !$name || !$email || !$passcode) {
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
    // Use prepared statement to prevent SQL injection
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
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param('sssi', $name, $email, $passcode, $userId);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'Account updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update account: ' . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>