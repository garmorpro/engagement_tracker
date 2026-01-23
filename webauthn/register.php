<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require '/var/www/engagement_tracker/vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use ParagonIE\ConstantTime\Base64UrlSafe;

header('Content-Type: application/json');
session_start();

// ----------- STEP 1: SEND REGISTRATION OPTIONS -----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Get optional account name from query
    $accountName = $_GET['account_name'] ?? 'Unnamed Account';

    // Generate a new UUID for the account if registering
    $userUUID = bin2hex(random_bytes(16));
    $_SESSION['webauthn_user_uuid'] = $userUUID;

    // RP entity
    $rp = new PublicKeyCredentialRpEntity('Engagement Tracker', $_SERVER['HTTP_HOST']);

    // User handle = 16 bytes
    $userHandle = random_bytes(16);
    $_SESSION['webauthn_user_handle'] = $userHandle;

    $user = new PublicKeyCredentialUserEntity(
        $userHandle,
        $userUUID,
        $accountName
    );

    // Supported algorithms
    $pubKeyCredParams = [
        new PublicKeyCredentialParameters('public-key', -7),   // ES256
        new PublicKeyCredentialParameters('public-key', -257)  // RS256
    ];

    // No excludeCredentials for new user
    $excludeCredentials = [];

    // Generate challenge
    $challenge = random_bytes(32);
    $_SESSION['webauthn_registration'] = ['challenge' => $challenge, 'time' => time()];

    // Build response for browser
    $response = [
        'rp' => ['name' => $rp->getName(), 'id' => $rp->getId()],
        'user' => [
            'id' => Base64UrlSafe::encodeUnpadded($userHandle),
            'name' => $accountName,
            'displayName' => $accountName
        ],
        'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
        'pubKeyCredParams' => [
            ['type' => 'public-key', 'alg' => -7],
            ['type' => 'public-key', 'alg' => -257]
        ],
        'timeout' => 60000,
        'excludeCredentials' => [],
        'authenticatorSelection' => [
            'authenticatorAttachment' => 'platform',
            'userVerification' => 'required'
        ],
        'attestation' => 'none'
    ];

    echo json_encode($response);
    exit;
}

// ----------- STEP 2: VERIFY & STORE REGISTRATION -----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_SESSION['webauthn_registration']) || empty($_SESSION['webauthn_user_handle']) || empty($_SESSION['webauthn_user_uuid'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing registration challenge']);
        exit;
    }

    $challenge = $_SESSION['webauthn_registration']['challenge'];
    $userHandle = $_SESSION['webauthn_user_handle'];
    $userUUID = $_SESSION['webauthn_user_uuid'];

    unset($_SESSION['webauthn_registration'], $_SESSION['webauthn_user_handle'], $_SESSION['webauthn_user_uuid']);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    // Load the credential from the browser
    $loader = new PublicKeyCredentialLoader();
    try {
        $publicKeyCredential = $loader->loadArray($data);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to load credential: ' . $e->getMessage()]);
        exit;
    }

    // Validate attestation
    $validator = AuthenticatorAttestationResponseValidator::create();
    try {
        $credentialSource = $validator->check(
            $publicKeyCredential->getResponse(),
            $publicKeyCredential->getRawId(),
            $challenge,
            $_SERVER['HTTP_HOST'],
            $userHandle,
            true
        );
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Biometric registration failed: ' . $e->getMessage()]);
        exit;
    }

    // Optional account name from POST
    $accountName = $_POST['account_name'] ?? 'Unnamed Account';

    // Insert account into biometric_accounts
    $stmt = $conn->prepare("
        INSERT INTO biometric_accounts (user_uuid, account_name)
        VALUES (?, ?)
    ");
    $stmt->bind_param('ss', $userUUID, $accountName);
    $stmt->execute();
    $stmt->close();

    // Insert credential
    $stmt = $conn->prepare("
        INSERT INTO webauthn_credentials (user_uuid, credential_id, public_key, sign_count)
        VALUES (?, ?, ?, ?)
    ");
    $credId = Base64UrlSafe::encodeUnpadded($credentialSource->getPublicKeyCredentialId());
    $publicKey = $credentialSource->getCredentialPublicKey();
    $signCount = $credentialSource->getCounter();
    $stmt->bind_param('sssi', $userUUID, $credId, $publicKey, $signCount);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'user_uuid' => $userUUID]);
    exit;
}

// Invalid method
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
