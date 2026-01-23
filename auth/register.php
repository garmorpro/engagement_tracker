<?php
declare(strict_types=1);

require '/var/www/engagement_tracker/vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\TrustPath\EmptyTrustPathChecker;
use ParagonIE\ConstantTime\Base64UrlSafe;

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Step 1: Send registration options to browser
    $accountName = $_GET['account_name'] ?? 'Unnamed Account';
    $userUUID = $_GET['user_uuid'] ?? bin2hex(random_bytes(16));

    $_SESSION['webauthn_user_uuid'] = $userUUID;

    // RP entity
    $rp = new PublicKeyCredentialRpEntity('Engagement Tracker', $_SERVER['HTTP_HOST']);

    // User entity
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

    // Exclude already registered credentials
    $exclude = [];
    $stmt = $conn->prepare("SELECT credential_id FROM webauthn_credentials WHERE user_uuid = ?");
    $stmt->bind_param('s', $userUUID);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $exclude[] = new PublicKeyCredentialDescriptor(
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            Base64UrlSafe::decode($row['credential_id'])
        );
    }
    $stmt->close();

    // Generate challenge
    $challenge = random_bytes(32);
    $_SESSION['webauthn_registration_challenge'] = $challenge;

    // Encode excludeCredentials for JS
    $excludeCredentials = array_map(fn($cred) => [
        'type' => 'public-key',
        'id' => Base64UrlSafe::encodeUnpadded($cred->getId())
    ], $exclude);

    echo json_encode([
        'rp' => ['name' => 'Engagement Tracker', 'id' => $_SERVER['HTTP_HOST']],
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
        'excludeCredentials' => $excludeCredentials,
        'authenticatorSelection' => [
            'authenticatorAttachment' => 'platform',
            'userVerification' => 'required'
        ],
        'attestation' => 'none',
        'user_uuid' => $userUUID
    ]);
    exit;
}

// ---------------- STEP 2: VERIFY REGISTRATION ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userUUID = $_SESSION['webauthn_user_uuid'] ?? '';
    $userHandle = $_SESSION['webauthn_user_handle'] ?? '';
    $challenge = $_SESSION['webauthn_registration_challenge'] ?? '';

    if (!$userUUID || !$userHandle || !$challenge) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing registration challenge/session']);
        exit;
    }

    unset($_SESSION['webauthn_user_uuid'], $_SESSION['webauthn_user_handle'], $_SESSION['webauthn_registration_challenge']);

    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    $loader = new PublicKeyCredentialLoader(new EmptyTrustPathChecker());
    try {
        $publicKeyCredential = $loader->loadArray($data);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Failed to load credential: ' . $e->getMessage()]);
        exit;
    }

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

    // Insert account
    $accountName = $_GET['account_name'] ?? 'Unnamed Account';
    $stmt = $conn->prepare("INSERT INTO biometric_accounts (user_uuid, account_name, status) VALUES (?, ?, 'active') ON DUPLICATE KEY UPDATE account_name=VALUES(account_name)");
    $stmt->bind_param('ss', $userUUID, $accountName);
    $stmt->execute();
    $stmt->close();

    // Insert credential
    $stmt = $conn->prepare("INSERT INTO webauthn_credentials (user_uuid, credential_id, public_key, sign_count, device_name) VALUES (?, ?, ?, ?, ?)");
    $credId = Base64UrlSafe::encodeUnpadded($credentialSource->getPublicKeyCredentialId());
    $publicKey = $credentialSource->getCredentialPublicKey();
    $signCount = $credentialSource->getCounter();
    $deviceName = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device';
    $stmt->bind_param('sssis', $userUUID, $credId, $publicKey, $signCount, $deviceName);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'user_uuid' => $userUUID]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
