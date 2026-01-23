<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/init.php';

// Read JSON body (credential returned from navigator.credentials.create())
$input = json_decode(file_get_contents('php://input'), true);
$userUUID = $input['user_uuid'] ?? null;
$attestationObject = $input['attestationObject'] ?? null;
$clientDataJSON = $input['clientDataJSON'] ?? null;
$accountName = $input['account_name'] ?? '';

// Basic validation
if (!$userUUID || !$attestationObject || !$clientDataJSON) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// Retrieve stored challenge
if (empty($_SESSION['webauthn_registration_challenge'][$userUUID])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No challenge found for this user']);
    exit;
}

$challenge = base64_decode($_SESSION['webauthn_registration_challenge'][$userUUID]);

// ---------------------------
// Step 1: Verify the attestation
// ---------------------------
// This normally requires parsing the attestationObject (CBOR) and clientDataJSON, 
// extracting the public key, checking challenge matches, etc.
// For simplicity, you can use the webauthn-lib PHP library, but here's a **simplified demo**:

$decodedAttestation = base64_decode($attestationObject);
$decodedClientData = base64_decode($clientDataJSON);

// In production: parse CBOR attestation, extract publicKey, check challenge
$publicKey = base64_encode(random_bytes(64)); // placeholder public key
$credentialId = base64_encode(random_bytes(32)); // placeholder credential id

// ---------------------------
// Step 2: Save credential to database
// ---------------------------
$stmt = $conn->prepare("
    INSERT INTO webauthn_credentials (user_uuid, credential_id, public_key, sign_count, created_at, updated_at)
    VALUES (?, ?, ?, 0, NOW(), NOW())
");
$stmt->bind_param('sss', $userUUID, $credentialId, $publicKey);
$stmt->execute();
$stmt->close();

// ---------------------------
// Step 3: Update biometric_accounts status if needed
// ---------------------------
$stmt2 = $conn->prepare("
    UPDATE biometric_accounts
    SET status = 'active', account_name = ?
    WHERE user_uuid = ?
");
$stmt2->bind_param('ss', $accountName, $userUUID);
$stmt2->execute();
$stmt2->close();

// Cleanup challenge
unset($_SESSION['webauthn_registration_challenge'][$userUUID]);

// Return success
echo json_encode(['success' => true, 'message' => 'Biometric registration successful']);
