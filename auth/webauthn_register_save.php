<?php
session_start();
require '../includes/db.php';

function fromB64url($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

$data = json_decode(file_get_contents('php://input'), true);

$userId = $_SESSION['user_id'];

$credId = fromB64url($data['rawId']);
$pubKey = fromB64url($data['publicKey']);

$stmt = $db->prepare("
    INSERT INTO webauthn_credentials (user_id, credential_id, public_key)
    VALUES (?, ?, ?)
");
$stmt->bind_param('iss', $userId, $credId, $pubKey);
$stmt->execute();

echo json_encode(['success'=>true]);
