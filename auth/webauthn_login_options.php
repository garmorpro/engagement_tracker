<?php
session_start();
require '../includes/db.php';

function b64url($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

$challenge = random_bytes(32);
$_SESSION['webauthn_challenge'] = $challenge;

$res = $db->query("SELECT credential_id FROM webauthn_credentials");

$allow = [];
while ($row = $res->fetch_assoc()) {
    $allow[] = [
        'type' => 'public-key',
        'id' => b64url($row['credential_id'])
    ];
}

echo json_encode([
    'challenge' => b64url($challenge),
    'rpId' => $_SERVER['HTTP_HOST'],
    'allowCredentials' => $allow,
    'userVerification' => 'preferred'
]);
