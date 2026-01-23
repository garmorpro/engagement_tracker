<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/init.php';

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);
$userUUID = $input['user_uuid'] ?? null;
$accountName = $input['account_name'] ?? '';

// Basic validation
if (!$userUUID) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing user UUID']);
    exit;
}

// Generate a random challenge
$challenge = random_bytes(32); // 32 bytes = 256 bits

// Store challenge in session for verification
$_SESSION['webauthn_registration_challenge'][$userUUID] = base64_encode($challenge);

// Prepare PublicKeyCredentialCreationOptions
$options = [
    'challenge' => base64_encode($challenge),
    'rp' => [
        'name' => 'Engagement Tracker',
        'id' => $_SERVER['SERVER_NAME']
    ],
    'user' => [
        'id' => base64_encode($userUUID),
        'name' => $accountName ?: $userUUID,
        'displayName' => $accountName ?: $userUUID
    ],
    'pubKeyCredParams' => [
        ['type' => 'public-key', 'alg' => -7],   // ES256
        ['type' => 'public-key', 'alg' => -257], // RS256
    ],
    'authenticatorSelection' => [
        'userVerification' => 'preferred'
    ],
    'timeout' => 60000, // 60 seconds
    'attestation' => 'direct'
];

// Return JSON options to browser
header('Content-Type: application/json');
echo json_encode($options);
