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

// JSON header
header('Content-Type: application/json');

require_once '../includes/db.php'; // DB connection

// Fetch ONE credential for demo
$result = $conn->query("SELECT credential_id FROM webauthn_credentials LIMIT 1");
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(['error' => 'No credentials registered']);
    exit;
}

// Generate a random challenge
$challenge = random_bytes(32);

// Store challenge for verification
$_SESSION['webauthn_login_challenge'] = base64_encode($challenge);

// Convert base64 to base64url
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Convert credential_id to base64url
$credentialId = $row['credential_id'];
$credentialIdB64Url = base64url_encode(base64_decode($credentialId)); // decode if stored as base64

// Build login options for Face ID / Fingerprint only
$options = [
    'challenge' => base64url_encode($challenge),
    'timeout' => 60000,
    'rpId' => $_SERVER['SERVER_NAME'],
    'allowCredentials' => [], // remove explicit credentials
    // 'allowCredentials' => [[
    //     'type' => 'public-key',
    //     'id' => $credentialIdB64Url,
    //     'transports' => ['internal'] // 🔹 platform authenticator only
    // ]],
    'userVerification' => 'required' // 🔹 forces biometric verification
];

// Clean any prior output
ob_clean();
echo json_encode($options);
exit;
