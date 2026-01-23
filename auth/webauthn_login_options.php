<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/init.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Fetch ONE credential
$result = $conn->query("SELECT credential_id FROM webauthn_credentials LIMIT 1");
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(['error' => 'No credentials registered']);
    exit;
}

// Generate challenge
$challenge = random_bytes(32);

// Store challenge for later verification
$_SESSION['webauthn_login_challenge'] = base64_encode($challenge);

echo json_encode([
    'challenge' => rtrim(strtr(base64_encode($challenge), '+/', '-_'), '='),
    'timeout' => 60000,
    'rpId' => $_SERVER['SERVER_NAME'],
    'allowCredentials' => [[
        'type' => 'public-key',
        'id' => $row['credential_id'],
    ]],
    'userVerification' => 'required'
]);
