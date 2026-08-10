<?php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

header('Content-Type: application/json');

// Read JSON input
$json_input = file_get_contents('php://input');
$data = json_decode($json_input, true);

if (!$data || !isset($data['user_id']) || !isset($data['name']) || !isset($data['email'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Missing required fields']));
}

$userId = intval($data['user_id']);
$name = trim($data['name']);
$email = trim($data['email']);
// Passcode is optional on edit: blank means "keep the current PIN" (hashes can't
// be shown back to the admin to prefill the field, so the form starts empty).
$passcode = trim($data['passcode'] ?? '');
$role = trim($data['role'] ?? 'standard');

// Validation
if (!$userId || !$name || !$email) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid input values']));
}

if ($passcode !== '' && (strlen($passcode) !== 4 || !ctype_digit($passcode))) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'PIN must be exactly 4 digits']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid email address']));
}

// 'super_admin' is a separate, hidden concept (a single dedicated account,
// excluded from get_accounts.php entirely) — this endpoint only ever
// promotes/demotes between the two regular roles.
if (!in_array($role, ['standard', 'admin'], true)) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid role']));
}

// Don't let an admin strip their own admin access by editing their own
// account — an easy way to accidentally lock yourself out of Settings.
if ($userId === (int) ($_SESSION['user_id'] ?? 0) && $role !== ($_SESSION['role'] ?? '')) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => "You can't change your own role"]));
}

try {
    if (!isset($conn) || !$conn) {
        http_response_code(500);
        die(json_encode(['success' => false, 'message' => 'Database connection not available']));
    }

    if ($passcode !== '') {
        $hashedPasscode = password_hash($passcode, PASSWORD_DEFAULT);
        $query = "
            UPDATE `service_accounts`
            SET `name` = ?,
                `email` = ?,
                `passcode` = ?,
                `role` = ?
            WHERE `user_id` = ? AND `role` != 'super_admin'
        ";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]));
        }
        $stmt->bind_param('ssssi', $name, $email, $hashedPasscode, $role, $userId);
    } else {
        $query = "
            UPDATE `service_accounts`
            SET `name` = ?,
                `email` = ?,
                `role` = ?
            WHERE `user_id` = ? AND `role` != 'super_admin'
        ";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]));
        }
        $stmt->bind_param('sssi', $name, $email, $role, $userId);
    }

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
