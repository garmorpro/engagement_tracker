<?php
session_start();
require '../includes/db.php';

$challenge = random_bytes(32);
$_SESSION['webauthn_challenge'] = base64_encode($challenge);

// Get all credentials
$res = $db->query("SELECT credential_id FROM webauthn_credentials");
$allow = [];

while ($row = $res->fetch_assoc()) {
    $allow[] = [
        'type' => 'public-key',
        'id' => rtrim(strtr(base64_encode($row['credential_id']), '+/', '-_'), '=')
    ];
}

echo json_encode([
    'challenge' => rtrim(strtr(base64_encode($challenge), '+/', '-_'), '='),
    'rpId' => $_SERVER['HTTP_HOST'],
    'allowCredentials' => $allow,
    'userVerification' => 'preferred'
]);
