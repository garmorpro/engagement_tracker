<?php
// Set content type FIRST before any output
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Suppress all output except JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Log errors to file instead of outputting them
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

try {
    // Get JSON input
    $json_input = file_get_contents('php://input');
    
    if (empty($json_input)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Empty request body']);
        exit;
    }
    
    $data = json_decode($json_input, true);
    
    // Validate input
    if (!$data || !isset($data['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing user_id parameter']);
        exit;
    }
    
    $userId = intval($data['user_id']);
    
    if ($userId <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid user_id']);
        exit;
    }
    
    // Include database connection
    require_once dirname(__FILE__) . '/../includes/functions.php';
    require_once dirname(__FILE__) . '/../path.php';
    require_once dirname(__FILE__) . '/../includes/init.php';
    
    // Verify database connection
    if (!isset($conn) || !$conn) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database not available']);
        exit;
    }
    
    // Check if connection is alive
    if (!$conn->ping()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection lost']);
        exit;
    }
    
    // Query the database
    $query = "SELECT `user_id`, `account_name`, `email`, `passcode` FROM `service_accounts` WHERE `user_id` = ?";
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
    
    if (!$result) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Result error']);
        exit;
    }
    
    if ($result->num_rows > 0) {
        $account = $result->fetch_assoc();
        
        // Ensure all fields exist and are properly formatted
        $response = [
            'success' => true,
            'account' => [
                'user_id' => intval($account['user_id']),
                'account_name' => $account['account_name'] ? $account['account_name'] : '',
                'email' => $account['email'] ? $account['email'] : '',
                'passcode' => $account['passcode'] ? $account['passcode'] : ''
            ]
        ];
        
        http_response_code(200);
        echo json_encode($response);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>