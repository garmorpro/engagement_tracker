<?php
header('Content-Type: application/json');

// Read JSON input
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (!$data || !isset($data['user_id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing user_id']));
}

$userId = intval($data['user_id']);

if ($userId <= 0) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid user_id']));
}

// Include your database connection
require_once '../includes/functions.php';
require_once '../path.php';
require_once '../includes/init.php';

try {
    // Check if $conn exists
    if (!isset($conn) || !$conn) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Database connection not available']));
    }
    
    $query = "SELECT `user_id`, `name`, `email`, `passcode` FROM `service_accounts` WHERE `user_id` = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]));
    }
    
    $stmt->bind_param('i', $userId);
    
    if (!$stmt->execute()) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Execute error: ' . $stmt->error]));
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