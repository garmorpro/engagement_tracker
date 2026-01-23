<?php
session_start();
require '../includes/db.php';

function fromB64url($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

$data = json_decode(file_get_contents('php://input'), true);
$client = json_decode(fromB64url($data['response']['clientDataJSON']), true);

if (!$client || !isset($_SESSION['webauthn_challenge'])) {
    exit(json_encode(['success'=>false]));
}

$rawId = fromB64url($data['rawId']);

$stmt = $db->prepare("SELECT user_id FROM webauthn_credentials WHERE credential_id=?");
$stmt->bind_param('s', $rawId);
$stmt->execute();
$stmt->bind_result($userId);
$stmt->fetch();

if (!$userId) {
    exit(json_encode(['success'=>false]));
}

$_SESSION['user_id'] = $userId;
unset($_SESSION['webauthn_challenge']);

echo json_encode(['success'=>true]);
