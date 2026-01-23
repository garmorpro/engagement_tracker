<?php
session_start();

// TEMP user ID for testing
$_SESSION['user_id'] = 1;

function b64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$challenge = random_bytes(32);
$_SESSION['webauthn_challenge'] = $challenge;

echo json_encode([
    'challenge' => b64url($challenge),
    'rp' => [
        'name' => 'Engagement Tracker'
    ],
    'user' => [
        'id' => b64url(pack('N', $_SESSION['user_id'])),
        'name' => 'user@example.com',
        'displayName' => 'Test User'
    ],
    'pubKeyCredParams' => [
        ['type' => 'public-key', 'alg' => -7] // ES256
    ],
    'authenticatorSelection' => [
        'userVerification' => 'required'
    ],
    'timeout' => 60000,
    'attestation' => 'none'
]);
