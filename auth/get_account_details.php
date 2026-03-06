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

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$userId = intval($data['user_id']);

try {
    $result = $conn->query("
        SELECT `user_id`, `account_name`, `email`, `passcode`
        FROM `service_accounts`
        WHERE `user_id` = $userId
        LIMIT 1
    ");
    
    if ($result && $result->num_rows > 0) {
        $account = $result->fetch_assoc();
        echo json_encode(['success' => true, 'account' => $account]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>