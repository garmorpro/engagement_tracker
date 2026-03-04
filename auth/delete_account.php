<?php
// auth/delete_account.php
session_start();
require_once '../includes/functions.php';
require_once '../path.php';

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
$stmt = $conn->prepare("SELECT role FROM service_accounts WHERE user_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();
$stmt->close();

if ($role === 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot delete super admin account']);
    exit;
}

// Delete the account
$stmt = $conn->prepare("DELETE FROM service_accounts WHERE user_id = ? AND role != 'super_admin'");
$stmt->bind_param('i', $user_id);
$success = $stmt->execute();
$stmt->close();

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Account deleted']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error deleting account']);
}