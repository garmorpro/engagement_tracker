<?php
// auth/delete_account.php
require_once '../includes/session_config.php';
startSecureSession();
require_once '../includes/functions.php';
require_once '../path.php';
requireAdminRole();

header('Content-Type: application/json');

// Only handle POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);
$user_id = intval($data['user_id'] ?? 0);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit;
}

// Prevent deleting super_admin accounts
$stmt = $conn->prepare("SELECT name, email, role FROM service_accounts WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (($account['role'] ?? null) === 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete super admin account']);
    exit;
}

// Delete the account
$stmt = $conn->prepare("DELETE FROM service_accounts WHERE user_id = ? AND role != 'super_admin'");
$stmt->bind_param('i', $user_id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    if ($account) {
        logActivity($conn, 'account_deleted', 'account', (string) $user_id, "Deleted account {$account['name']} ({$account['email']})");
    }
    echo json_encode(['success' => true, 'message' => 'Account deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting account']);
}