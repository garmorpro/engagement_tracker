<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/db.php';

$user_id = 1; // your user ID
$username = "testuser";

// Generate challenge
$challenge = random_bytes(32);
$_SESSION['webauthn_registration_challenge'][$user_id] = base64_encode($challenge);

// Base64url encode function
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$options = [
    'challenge' => base64url_encode($challenge),
    'rp' => [
        'name' => 'Engagement Tracker',
        'id' => $_SERVER['SERVER_NAME']
    ],
    'user' => [
        'id' => base64url_encode($userUUID),
        'name' => $accountName ?: $userUUID,
        'displayName' => $accountName ?: $userUUID
    ],
    'pubKeyCredParams' => [
        ['type' => 'public-key', 'alg' => -7],   // ES256 (Elliptic curve)
        // ['type' => 'public-key', 'alg' => -257], // RS256 optional, can remove
    ],
    'authenticatorSelection' => [
        'authenticatorAttachment' => 'platform', // Only Touch ID / Face ID
        'userVerification' => 'required'
    ],
    'timeout' => 60000,
    'attestation' => 'none' // No attestation needed
];

echo json_encode($options);
exit;