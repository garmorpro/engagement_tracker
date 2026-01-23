<?php
declare(strict_types=1);

require '/var/www/engagement_tracker/vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\TrustPath\EmptyTrustPathChecker;
use ParagonIE\ConstantTime\Base64UrlSafe;

session_start();
header('Content-Type: application/json');

// Ensure POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the stored challenge and user
$challenge = $_SESSION['webauthn_authentication_challenge'] ?? null;
$userUUID  = $_SESSION['webauthn_authentication_user_uuid'] ?? null;

if (!$challenge || !$userUUID) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing login challenge/session']);
    exit;
}

// Remove challenge from session after use
unset($_SESSION['webauthn_authentication_challenge']);
unset($_SESSION['webauthn_authentication_user_uuid']);

// Get POSTed credential from browser
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

// Load the credential
$loader = new PublicKeyCredentialLoader(new EmptyTrustPathChecker());
try {
    $publicKeyCredential = $loader->loadArray($data);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to load credential: ' . $e->getMessage()]);
    exit;
}

// Fetch stored credential from DB
$credId = Base64UrlSafe::encodeUnpadded($publicKeyCredential->getRawId());
$stmt = $conn->prepare("SELECT public_key, sign_count FROM webauthn_credentials WHERE user_uuid = ? AND credential_id = ?");
$stmt->bind_param('ss', $userUUID, $credId);
$stmt->execute();
$stored = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$stored) {
    http_response_code(400);
    echo json_encode(['error' => 'Credential not found']);
    exit;
}

// Validate assertion
$validator = new AuthenticatorAssertionResponseValidator();
try {
    $credentialSource = $validator->check(
        $publicKeyCredential->getResponse(),
        Base64UrlSafe::decode($stored['public_key']),
        $challenge,
        $_SERVER['HTTP_HOST'],
        true,
        (int)$stored['sign_count']
    );
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Biometric verification failed: ' . $e->getMessage()]);
    exit;
}

// Update sign count
$stmt = $conn->prepare("UPDATE webauthn_credentials SET sign_count = ? WHERE user_uuid = ? AND credential_id = ?");
$newSignCount = $credentialSource->getCounter();
$stmt->bind_param('iss', $newSignCount, $userUUID, $credId);
$stmt->execute();
$stmt->close();

// Set session as logged in
$_SESSION['user_uuid'] = $userUUID;

// Success
echo json_encode(['success' => true]);
