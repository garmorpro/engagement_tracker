<?php
// ============================================
// WebAuthn Login Options Endpoint
// ============================================

// Silence warnings to keep JSON clean
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// Start session if not already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
require_once '../includes/db.php';

// Fetch ONE credential for this user
$result = $conn->query("SELECT credential_id FROM webauthn_credentials LIMIT 1");
$row = $result->fetch_assoc();

if (!$row) exit(json_encode(['error'=>'No credentials registered']));

$challenge = random_bytes(32);
$_SESSION['webauthn_login_challenge'] = base64_encode($challenge);

// Base64url encode function
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$options = [
    'challenge' => base64url_encode($challenge),
    'timeout' => 60000,
    'rpId' => $_SERVER['SERVER_NAME'],
    'allowCredentials' => [[
        'type' => 'public-key',
        'id' => $row['credential_id'],
        'transports' => ['internal'] // fingerprint / Face ID only
    ]],
    'userVerification' => 'required'
];

echo json_encode($options);
exit;