<?php
// ---------------------------
// WebAuthn Login Options API
// ---------------------------

// Suppress PHP warnings and notices
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Return JSON
header('Content-Type: application/json');

require_once '../includes/init.php';

// Fetch ONE credential from database
$result = $conn->query("SELECT credential_id FROM webauthn_credentials LIMIT 1");
$row = $result ? $result->fetch_assoc() : null;

if (!$row) {
    echo json_encode(['error' => 'No credentials registered']);
    exit;
}

// Generate 32-byte challenge
$challenge = random_bytes(32);

// Store challenge in session for verification
$_SESSION['webauthn_login_challenge'] = base64_encode($challenge);

// Prepare WebAuthn options
$options = [
    'challenge' => rtrim(strtr(base64_encode($challenge), '+/', '-_'), '='),
    'timeout' => 60000,
    'rpId' => $_SERVER['SERVER_NAME'],
    'allowCredentials' => [[
        'type' => 'public-key',
        // credential_id must be sent base64url encoded
        'id' => $row['credential_id'],
        'transports' => ['internal'], // optional
    ]],
    'userVerification' => 'required'
];

// Send clean JSON
echo json_encode($options);
exit;
