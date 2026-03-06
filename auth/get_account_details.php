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
if (!$isJsonRequest && !isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = intval($data['user_id'] ?? 0);

if (!$userId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing or invalid user_id']);
    exit;
}

try {
    // Use prepared statement
    $query = "SELECT `user_id`, `name`, `email`, `passcode` FROM `service_accounts` WHERE `user_id` = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param('i', $userId);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Query error: ' . $stmt->error]);
        exit;
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $account = $result->fetch_assoc();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'account' => [
                'user_id' => intval($account['user_id']),
                'name' => $account['name'] ?: '',
                'email' => $account['email'] ?: '',
                'passcode' => $account['passcode'] ?: ''
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>