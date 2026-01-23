<?php
declare(strict_types=1);

require '/var/www/engagement_tracker/vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use ParagonIE\ConstantTime\Base64UrlSafe;

session_start();
header('Content-Type: application/json');

// Ensure HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    http_response_code(400);
    echo json_encode(['error' => 'WebAuthn requires HTTPS']);
    exit;
}

// Get user_uuid from query
$userUUID = $_GET['user_uuid'] ?? '';
if (!$userUUID) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user']);
    exit;
}

// Fetch user account
$stmt = $conn->prepare("SELECT account_name FROM biometric_accounts WHERE user_uuid = ?");
$stmt->bind_param('s', $userUUID);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    http_response_code(400);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Fetch credentials
$stmt = $conn->prepare("SELECT credential_id FROM webauthn_credentials WHERE user_uuid = ?");
$stmt->bind_param('s', $userUUID);
$stmt->execute();
$res = $stmt->get_result();

$allowCredentials = [];
while ($row = $res->fetch_assoc()) {
    $allowCredentials[] = new PublicKeyCredentialDescriptor(
        PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
        Base64UrlSafe::decode($row['credential_id'])
    );
}
$stmt->close();

if (empty($allowCredentials)) {
    http_response_code(400);
    echo json_encode(['error' => 'No biometric credentials found']);
    exit;
}

// Generate challenge
$challenge = random_bytes(32);
$_SESSION['webauthn_authentication_challenge'] = $challenge;
$_SESSION['webauthn_authentication_user_uuid'] = $userUUID;

$options = new PublicKeyCredentialRequestOptions(
    $challenge,
    60000,
    null,
    $allowCredentials,
    PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
    new AuthenticationExtensionsClientInputs()
);

echo json_encode([
    'challenge' => Base64UrlSafe::encodeUnpadded($options->getChallenge()),
    'timeout' => $options->getTimeout(),
    'rpId' => $_SERVER['HTTP_HOST'],
    'user' => [
        'id' => Base64UrlSafe::encodeUnpadded(random_bytes(16)), // needed for browser
        'name' => $account['account_name'],
        'displayName' => $account['account_name']
    ],
    'allowCredentials' => array_map(fn($cred) => [
        'type' => 'public-key',
        'id' => Base64UrlSafe::encodeUnpadded($cred->getId())
    ], $allowCredentials),
    'userVerification' => 'required'
]);
