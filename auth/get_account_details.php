<?php
// Set content type first
header('Content-Type: application/json');

// Enable error reporting for debugging
error_log("=== GET_ACCOUNT_DETAILS REQUEST ===");

// Get JSON input
$json_input = file_get_contents('php://input');
error_log("Raw input: " . $json_input);

$data = json_decode($json_input, true);
error_log("Decoded data: " . print_r($data, true));

// Validate input
if (!$data || !isset($data['user_id'])) {
    error_log("ERROR: Missing user_id in request");
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing user_id']);
    exit;
}

$userId = intval($data['user_id']);
error_log("Processing user_id: " . $userId);

// Include database connection - adjust path as needed
require_once dirname(__FILE__) . '/../includes/functions.php';
require_once dirname(__FILE__) . '/../path.php';
require_once dirname(__FILE__) . '/../includes/init.php';

// Verify database connection
if (!isset($conn) || !$conn) {
    error_log("ERROR: Database connection not initialized");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

error_log("Database connection successful");

// Prepare and execute query
$query = "SELECT `user_id`, `account_name`, `email`, `passcode` FROM `service_accounts` WHERE `user_id` = ?";
error_log("Executing query: " . $query);

$stmt = $conn->prepare($query);

if (!$stmt) {
    error_log("ERROR: Query preparation failed: " . $conn->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query preparation failed']);
    exit;
}

$stmt->bind_param('i', $userId);

if (!$stmt->execute()) {
    error_log("ERROR: Query execution failed: " . $stmt->error);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Query execution failed']);
    exit;
}

$result = $stmt->get_result();
error_log("Query result rows: " . $result->num_rows);

if ($result && $result->num_rows > 0) {
    $account = $result->fetch_assoc();
    error_log("Account found: " . $account['account_name']);
    
    // Return success with account data
    echo json_encode([
        'success' => true,
        'account' => $account
    ]);
} else {
    error_log("ERROR: User not found with id: " . $userId);
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
error_log("=== END GET_ACCOUNT_DETAILS ===");
?>