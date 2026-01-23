<?php
declare(strict_types=1);

require_once __DIR__ . '../includes/init.php';
require_once __DIR__ . '../../vendor/autoload.php';

use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\Util\Base64UrlSafe;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;

// Force JSON
header('Content-Type: application/json');

// Require HTTPS (important for WebAuthn)
if (empty($_SERVER['HTTPS'])) {
    http_response_code(400);
    echo json_encode(['error' => 'WebAuthn requires HTTPS']);
    exit;
}

// Validate user_id
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
if ($userId <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid user']);
    exit;
}

// Fetch biometric credentials for this user
$stmt = $conn->prepare("
    SELECT credential_id
    FROM webauthn_credentials
    WHERE user_id = ?
");
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();

$allowCredentials = [];

while ($row = $result->fetch_assoc()) {
    $allowCredentials[] = new PublicKeyCredentialDescriptor(
        PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
        $row['credential_id']
    );
}

$stmt->close();

// No biometrics registered
if (empty($allowCredentials)) {
    http_response_code(400);
    echo json_encode(['error' => 'No biometric credentials found']);
    exit;
}

// Generate random challenge
$challenge = random_bytes(32);

// Save challenge to session
$_SESSION['webauthn_authentication'] = [
    'challenge' => $challenge,
    'user_id'   => $userId,
    'time'      => time()
];

// Create WebAuthn request options
$options = new PublicKeyCredentialRequestOptions(
    $challenge,
    60000, // timeout (ms)
    null,
    $allowCredentials,
    PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
    new AuthenticationExtensionsClientInputs()
);

// Output JSON for browser
echo json_encode([
    'challenge' => Base64UrlSafe::encodeUnpadded($options->getChallenge()),
    'timeout' => $options->getTimeout(),
    'rpId' => $_SERVER['HTTP_HOST'],
    'allowCredentials' => array_map(function ($cred) {
        return [
            'type' => 'public-key',
            'id'   => Base64UrlSafe::encodeUnpadded($cred->getId())
        ];
    }, $allowCredentials),
    'userVerification' => 'required'
]);
