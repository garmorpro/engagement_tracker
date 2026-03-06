<?php
// auth/login.php
session_start();
require_once '../includes/functions.php';
require_once '../path.php';

// CRITICAL: Set content type FIRST before any output
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

// Suppress all output except JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

try {
    // Read JSON input
    $json_input = file_get_contents('php://input');
    
    if (empty($json_input)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Empty request']));
    }
    
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
    
    
    // Query database
    $query = "SELECT `user_id`, `account_name`, `email`, `passcode` FROM `service_accounts` WHERE `user_id` = ?";
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
    
    if (!$result) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Result error']));
    }
    
    if ($result->num_rows > 0) {
        $account = $result->fetch_assoc();
        
        // Build response
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'account' => [
                'user_id' => intval($account['user_id']),
                'account_name' => $account['account_name'] ?: '',
                'email' => $account['email'] ?: '',
                'passcode' => $account['passcode'] ?: ''
            ]
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>