<?php
// ============================================
// WebAuthn Login Options Endpoint
// ============================================

// Silence all warnings/notices so JSON is clean
error_reporting(E_ERROR);
ini_set('display_errors', 0);

// Only start session if none exists
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

require_once '../includes/db.php'; // DB connection

// Fetch ONE credential for demo (replace with proper user lookup)
$result = $conn->query("SELECT credential_id FROM webauthn_credentials LIMIT 1");
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(['error' => 'No credentials registered']);
    exit;
}

// Generate random challenge
$challenge = random_bytes(32);

// Store challenge for later verification
$_SESSION['webauthn_login_challenge'] = base64_encode($challenge);

// Convert credential_id to base64url for WebAuthn
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$credentialId = $row['credential_id'];
$credentialIdB64Url = base64url_encode(base64_decode($credentialId)); 
// decode first if stored as regular base64 in DB

// Build login options
$options = [
    'challenge' => rtrim(strtr(base64_encode($challenge), '+/', '-_'), '='),
    'timeout' => 60000,
    'rpId' => $_SERVER['SERVER_NAME'],
    'allowCredentials' => [[
        'type' => 'public-key',
        'id' => $credentialIdB64Url,
        'transports' => ['internal'] // platform authenticator
    ]],
    'userVerification' => 'required'
];

// Ensure no accidental output breaks JSON
ob_clean();
echo json_encode($options);
exit;
