<?php
// auth/login.php
session_start();
require_once '../includes/functions.php';
require_once '../path.php';

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

try {
    $json_input = file_get_contents('php://input');
    
    if (empty($json_input)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Empty request']));
    }
    
    $data = json_decode($json_input, true);
    
    if (!$data || !isset($data['user_id']) || !isset($data['account_name']) || !isset($data['email']) || !isset($data['passcode'])) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Missing required fields']));
    }
    
    $userId = intval($data['user_id']);
    $accountName = trim($data['account_name']);
    $email = trim($data['email']);
    $passcode = trim($data['passcode']);
    
    if ($userId <= 0 || empty($accountName) || empty($email) || empty($passcode)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Invalid input values']));
    }
    
    
    // Update query
    $query = "UPDATE `service_accounts` SET `account_name` = ?, `email` = ?, `passcode` = ? WHERE `user_id` = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Prepare error']));
    }
    
    $stmt->bind_param('sssi', $accountName, $email, $passcode, $userId);
    
    if ($stmt->execute()) {
        http_response_code(200);
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Update failed']);
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>