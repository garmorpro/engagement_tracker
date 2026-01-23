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
    'rp' => ['name' => 'Engagement Tracker', 'id' => 'et.morganserver.com'],
    'user' => [
        'id' => base64url_encode($user_id),
        'name' => $username,
        'displayName' => $username
    ],
    'pubKeyCredParams' => [['type' => 'public-key', 'alg' => -7]],
    'authenticatorSelection' => ['authenticatorAttachment' => 'platform', 'userVerification' => 'required'],
    'timeout' => 60000,
    'attestation' => 'none'
];

echo json_encode($options);
exit;