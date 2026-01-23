<?php
session_start();
require '../includes/db.php';

$data = json_decode(file_get_contents('php://input'), true);

$challenge = base64_decode($_SESSION['webauthn_challenge'] ?? '');
$clientData = json_decode(base64_decode($data['response']['clientDataJSON']), true);

// Verify challenge
if ($clientData['challenge'] !== rtrim(strtr(base64_encode($challenge), '+/', '-_'), '=')) {
    exit(json_encode(['success' => false]));
}

// Find credential
$rawId = base64_decode(strtr($data['rawId'], '-_', '+/'));
$stmt = $db->prepare("SELECT user_id FROM webauthn_credentials WHERE credential_id=?");
$stmt->bind_param('s', $rawId);
$stmt->execute();
$stmt->bind_result($userId);
$stmt->fetch();

if (!$userId) exit(json_encode(['success'=>false]));

// SUCCESS → LOGIN
$_SESSION['user_id'] = $userId;
echo json_encode(['success' => true]);
