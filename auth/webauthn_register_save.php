<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) exit(json_encode(['success'=>false, 'error'=>'No input']));

// Store credential in DB
$rawId = $input['rawId']; // base64url
$attestationObject = $input['attestationObject'];

$stmt = $conn->prepare("INSERT INTO webauthn_credentials (credential_id, attestation) VALUES (?, ?)");
$stmt->bind_param('ss', $rawId, $attestationObject);
$stmt->execute();
$stmt->close();

echo json_encode(['success'=>true]);
exit;