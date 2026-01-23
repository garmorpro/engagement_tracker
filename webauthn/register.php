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

// Must be logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

// ---------- STEP 1: SEND REGISTRATION OPTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $stmt = $conn->prepare("SELECT account_name FROM service_accounts WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$account) {
        http_response_code(400);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // RP entity
    $rp = new PublicKeyCredentialRpEntity('Engagement Tracker', $_SERVER['HTTP_HOST']);

    // Generate 16-byte user handle (binary)
    $userHandle = random_bytes(16);
    $_SESSION['webauthn_user_handle'] = $userHandle;

    $user = new PublicKeyCredentialUserEntity(
        $userHandle, // binary
        (string)$userId,
        $account['account_name']
    );

    // Supported algorithms
    $pubKeyCredParams = [
        new PublicKeyCredentialParameters('public-key', -7),
        new PublicKeyCredentialParameters('public-key', -257)
    ];

    // Exclude already registered credentials
    $exclude = [];
    $stmt = $conn->prepare("SELECT credential_id FROM webauthn_credentials WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        // Decode from DB if stored as base64, ensure binary
        $binaryId = Base64UrlSafe::decode($row['credential_id']);
        $exclude[] = new PublicKeyCredentialDescriptor(
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            $binaryId
        );
    }
    $stmt->close();

    // Generate challenge (binary)
    $challenge = random_bytes(32);
    $_SESSION['webauthn_registration'] = ['challenge' => $challenge, 'time' => time()];

    // Encode excludeCredentials for JS (always valid base64url)
    $excludeCredentials = array_map(fn($cred) => [
        'type' => 'public-key',
        'id' => Base64UrlSafe::encodeUnpadded($cred->getId())
    ], $exclude);

    // Build response
    $response = [
        'rp' => ['name' => 'Engagement Tracker', 'id' => $_SERVER['HTTP_HOST']],
        'user' => [
            'id' => Base64UrlSafe::encodeUnpadded($userHandle),
            'name' => $account['account_name'],
            'displayName' => $account['account_name']
        ],
        'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
        'pubKeyCredParams' => [
            ['type' => 'public-key', 'alg' => -7],
            ['type' => 'public-key', 'alg' => -257]
        ],
        'timeout' => 60000,
        'excludeCredentials' => $excludeCredentials,
        'authenticatorSelection' => [
            'authenticatorAttachment' => 'platform',
            'userVerification' => 'required'
        ],
        'attestation' => 'none'
    ];

    echo json_encode($response);
    exit;
}

// ---------- STEP 2: VERIFY & STORE REGISTRATION ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_SESSION['webauthn_registration']) || empty($_SESSION['webauthn_user_handle'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing registration challenge']);
        exit;
    }

    $challenge = $_SESSION['webauthn_registration']['challenge'];
    $userHandle = $_SESSION['webauthn_user_handle'];
    unset($_SESSION['webauthn_registration'], $_SESSION['webauthn_user_handle']);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    // Load credential from browser
    $loader = new PublicKeyCredentialLoader();
    $publicKeyCredential = $loader->loadArray($data);

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

    // Save credential to DB (binary)
    $stmt = $conn->prepare("
        INSERT INTO webauthn_credentials
            (user_id, credential_id, public_key, sign_count, device_name)
        VALUES (?, ?, ?, ?, ?)
    ");
    $deviceName = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device';
    $stmt->bind_param(
        'issis',
        $userId,
        $credentialSource->getPublicKeyCredentialId(), // binary
        $credentialSource->getCredentialPublicKey(),   // binary
        $credentialSource->getCounter(),
        $deviceName
    );
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

// Invalid method
http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
