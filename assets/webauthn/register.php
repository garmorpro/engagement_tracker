<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\Util\Base64UrlSafe;

// Force JSON
header('Content-Type: application/json');

// Must be logged in via password
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

// ---------- STEP 1: SEND REGISTRATION OPTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Fetch account name
    $stmt = $conn->prepare("
        SELECT account_name
        FROM service_accounts
        WHERE user_id = ?
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$account) {
        http_response_code(400);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    // Create RP (your app)
    $rp = new PublicKeyCredentialRpEntity(
        'Engagement Tracker',
        $_SERVER['HTTP_HOST']
    );

    // Create user entity
    $user = new PublicKeyCredentialUserEntity(
        (string) $userId,
        (string) $userId,
        $account['account_name']
    );

    // Supported algorithms
    $pubKeyCredParams = [
        new PublicKeyCredentialParameters('public-key', -7),   // ES256
        new PublicKeyCredentialParameters('public-key', -257), // RS256
    ];

    // Exclude already-registered credentials
    $exclude = [];
    $stmt = $conn->prepare("
        SELECT credential_id
        FROM webauthn_credentials
        WHERE user_id = ?
    ");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $exclude[] = new PublicKeyCredentialDescriptor(
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            $row['credential_id']
        );
    }
    $stmt->close();

    // Generate challenge
    $challenge = random_bytes(32);

    $_SESSION['webauthn_registration'] = [
        'challenge' => $challenge,
        'time'      => time()
    ];

    // Create options
    $options = new PublicKeyCredentialCreationOptions(
        $rp,
        $user,
        $challenge,
        $pubKeyCredParams
    );

    $options->setTimeout(60000);
    $options->setExcludeCredentials($exclude);
    $options->setAuthenticatorSelection([
        'authenticatorAttachment' => 'platform',
        'userVerification'        => 'required'
    ]);
    $options->setAttestation(PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE);

    // Send options to browser
    echo json_encode([
        'rp' => [
            'name' => 'Engagement Tracker',
            'id'   => $_SERVER['HTTP_HOST']
        ],
        'user' => [
            'id'          => Base64UrlSafe::encodeUnpadded($user->getId()),
            'name'        => $account['account_name'],
            'displayName' => $account['account_name']
        ],
        'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
        'pubKeyCredParams' => [
            ['type' => 'public-key', 'alg' => -7],
            ['type' => 'public-key', 'alg' => -257]
        ],
        'timeout' => 60000,
        'excludeCredentials' => array_map(function ($cred) {
            return [
                'type' => 'public-key',
                'id'   => Base64UrlSafe::encodeUnpadded($cred->getId())
            ];
        }, $exclude),
        'authenticatorSelection' => [
            'authenticatorAttachment' => 'platform',
            'userVerification'        => 'required'
        ],
        'attestation' => 'none'
    ]);

    exit;
}

// ---------- STEP 2: VERIFY & STORE REGISTRATION ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (empty($_SESSION['webauthn_registration'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing registration challenge']);
        exit;
    }

    $challenge = $_SESSION['webauthn_registration']['challenge'];
    unset($_SESSION['webauthn_registration']);

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    // Load browser credential
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
            null,
            true
        );
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Biometric registration failed']);
        exit;
    }

    // Store credential
    $stmt = $conn->prepare("
        INSERT INTO webauthn_credentials
            (user_id, credential_id, public_key, sign_count, device_name)
        VALUES (?, ?, ?, ?, ?)
    ");

    $deviceName = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown device';
    $stmt->bind_param(
        'issis',
        $userId,
        $credentialSource->getPublicKeyCredentialId(),
        $credentialSource->getCredentialPublicKey(),
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
