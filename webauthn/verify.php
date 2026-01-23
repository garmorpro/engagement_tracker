<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
require '/var/www/engagement_tracker/vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialSource;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use ParagonIE\ConstantTime\Base64UrlSafe;

header('Content-Type: application/json');

if (empty($_SESSION['webauthn_authentication'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing authentication challenge']);
    exit;
}

$authSession = $_SESSION['webauthn_authentication'];
unset($_SESSION['webauthn_authentication']);

$rawData = file_get_contents('php://input');
$data = json_decode($rawData, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

try {
    $credentialId = Base64UrlSafe::decode($data['id']);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid credential ID']);
    exit;
}

$stmt = $conn->prepare("
    SELECT c.credential_id, c.public_key, c.sign_count, s.user_id
    FROM webauthn_credentials c
    JOIN service_accounts s ON s.user_id = c.user_id
    WHERE c.credential_id = ?
");
$stmt->bind_param('s', $credentialId);
$stmt->execute();
$stored = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$stored || (int)$stored['user_id'] !== (int)$authSession['user_id']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Credential/user mismatch']);
    exit;
}

// RP & User entities
$rpEntity = new PublicKeyCredentialRpEntity('Engagement Tracker', $_SERVER['HTTP_HOST']);
$userEntity = new PublicKeyCredentialUserEntity(
    (string)$stored['user_id'],
    (string)$stored['user_id'],
    'User'
);

$credentialSource = PublicKeyCredentialSource::createFromArray([
    'publicKeyCredentialId' => $stored['credential_id'],
    'type' => 'public-key',
    'transports' => [],
    'attestationType' => null,
    'trustPath' => null,
    'aaguid' => null,
    'credentialPublicKey' => $stored['public_key'],
    'userHandle' => (string)$stored['user_id'],
    'counter' => (int)$stored['sign_count']
]);

$loader = new PublicKeyCredentialLoader();
$publicKeyCredential = $loader->loadArray($data);

$validator = AuthenticatorAssertionResponseValidator::create();

try {
    $response = $validator->check(
        $credentialSource,
        $publicKeyCredential->getResponse(),
        $authSession['challenge'],
        $_SERVER['HTTP_HOST'],
        $userEntity->getId(),
        true
    );
} catch (Throwable $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Biometric verification failed: ' . $e->getMessage()]);
    exit;
}

$stmt = $conn->prepare("UPDATE webauthn_credentials SET sign_count = ? WHERE credential_id = ?");
$signCount = $response->getSignCount();
$stmt->bind_param('is', $signCount, $stored['credential_id']);
$stmt->execute();
$stmt->close();

$_SESSION['user_id'] = (int)$stored['user_id'];
$_SESSION['last_activity'] = time();

$stmt = $conn->prepare("UPDATE service_accounts SET logged_in = 1, last_active = NOW() WHERE user_id = ?");
$stmt->bind_param('i', $stored['user_id']);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
