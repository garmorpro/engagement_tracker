<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require '/var/www/engagement_tracker/vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use ParagonIE\ConstantTime\Base64UrlSafe;

header('Content-Type: application/json');

// Ensure HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    http_response_code(400);
    echo json_encode(['error' => 'WebAuthn requires HTTPS']);
    exit;
}

// Get user_uuid from GET
$userUUID = $_GET['user_uuid'] ?? '';
if (empty($userUUID)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user UUID']);
    exit;
}

// Fetch credentials for this user
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
$_SESSION['webauthn_authentication'] = [
    'challenge' => $challenge,
    'user_uuid' => $userUUID,
    'time'      => time()
];

// Build WebAuthn options
$options = new PublicKeyCredentialRequestOptions(
    $challenge,
    60000,
    null,
    $allowCredentials,
    PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
    new AuthenticationExtensionsClientInputs()
);

// Output JSON for the browser
echo json_encode([
    'challenge' => Base64UrlSafe::encodeUnpadded($options->getChallenge()),
    'timeout' => $options->getTimeout(),
    'rpId' => $_SERVER['HTTP_HOST'],
    'allowCredentials' => array_map(fn($cred) => [
        'type' => 'public-key',
        'id' => Base64UrlSafe::encodeUnpadded($cred->getId())
    ], $allowCredentials),
    'userVerification' => 'required'
]);
