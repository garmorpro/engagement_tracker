<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '../../vendor/autoload.php';

use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\Util\Base64UrlSafe;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

// Force JSON
header('Content-Type: application/json');

// Ensure session challenge exists
if (empty($_SESSION['webauthn_authentication'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing authentication challenge']);
    exit;
}

$authSession = $_SESSION['webauthn_authentication'];
unset($_SESSION['webauthn_authentication']); // single-use challenge

// Read incoming JSON
$rawData = file_get_contents('php://input');
if (!$rawData) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$data = json_decode($rawData, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Decode credential ID
$credentialId = Base64UrlSafe::decode($data['id']);

// Load stored credential
$stmt = $conn->prepare("
    SELECT c.credential_id, c.public_key, c.sign_count, s.user_id
    FROM webauthn_credentials c
    JOIN service_accounts s ON s.user_id = c.user_id
    WHERE c.credential_id = ?
");
$stmt->bind_param('s', $credentialId);
$stmt->execute();
$result = $stmt->get_result();

$stored = $result->fetch_assoc();
$stmt->close();

if (!$stored) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Credential not recognized']);
    exit;
}

// Ensure credential belongs to expected user
if ((int)$stored['user_id'] !== (int)$authSession['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Credential/user mismatch']);
    exit;
}

// RP (your app)
$rpEntity = new PublicKeyCredentialRpEntity(
    'Engagement Tracker',
    $_SERVER['HTTP_HOST']
);

// User entity
$userEntity = new PublicKeyCredentialUserEntity(
    (string)$stored['user_id'],
    (string)$stored['user_id'],
    'User'
);

// Load credential source
$credentialSource = PublicKeyCredentialSource::createFromArray([
    'publicKeyCredentialId' => $stored['credential_id'],
    'type'                 => 'public-key',
    'transports'           => [],
    'attestationType'      => null,
    'trustPath'            => null,
    'aaguid'               => null,
    'credentialPublicKey'  => $stored['public_key'],
    'userHandle'           => (string)$stored['user_id'],
    'counter'              => (int)$stored['sign_count',
    ]
]);

// Load browser response
$loader = new PublicKeyCredentialLoader();
$publicKeyCredential = $loader->loadArray($data);

// Validator
$validator = AuthenticatorAssertionResponseValidator::create(
    null, // metadata service (optional)
    null  // token binding handler (optional)
);

try {
    $response = $validator->check(
        $credentialSource,
        $publicKeyCredential->getResponse(),
        $authSession['challenge'],
        $_SERVER['HTTP_HOST'],
        $userEntity->getId(),
        true // user verification required
    );
} catch (Throwable $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Biometric verification failed']);
    exit;
}

// Update signature counter
$stmt = $conn->prepare("
    UPDATE webauthn_credentials
    SET sign_count = ?
    WHERE credential_id = ?
");
$stmt->bind_param('is', $response->getSignCount(), $stored['credential_id']);
$stmt->execute();
$stmt->close();

// Start authenticated session
$_SESSION['user_id'] = (int)$stored['user_id'];
$_SESSION['last_activity'] = time();

// Update account login state
$stmt = $conn->prepare("
    UPDATE service_accounts
    SET logged_in = 1, last_active = NOW()
    WHERE user_id = ?
");
$stmt->bind_param('i', $stored['user_id']);
$stmt->execute();
$stmt->close();

// Success
echo json_encode(['success' => true]);
