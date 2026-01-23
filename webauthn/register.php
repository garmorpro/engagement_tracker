<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start(); // Make sure session is started


require_once __DIR__ '/../includes/db.php';
require __DIR__ . '/../../../vendor/autoload.php';



echo "__DIR__ is: " . __DIR__;

use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\Util\Base64UrlSafe;

// Test
var_dump(class_exists(Base64UrlSafe::class));

// Force JSON response
header('Content-Type: application/json');

// Must be logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = (int) $_SESSION['user_id'];

// ---------- STEP 1: SEND REGISTRATION OPTIONS ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Fetch account name
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

    // User entity
    $user = new PublicKeyCredentialUserEntity(
        (string)$userId,
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
        $exclude[] = new PublicKeyCredentialDescriptor(
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            $row['credential_id']
        );
    }
    $stmt->close();

    // Generate challenge
    $challenge = random_bytes(32);
    $_SESSION['webauthn_registration'] = ['challenge' => $challenge, 'time' => time()];

    // Encode excludeCredentials for JS
    $excludeCredentials = array_map(fn($cred) => [
        'type' => 'public-key',
        'id'   => Base64UrlSafe::encodeUnpadded($cred->getId())
    ], $exclude);

    // Send registration options to browser
    $response = [
        'rp' => ['name' => 'Engagement Tracker', 'id' => $_SERVER['HTTP_HOST']],
        'user' => [
            'id' => Base64UrlSafe::encodeUnpadded($user->getId()),
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
            null,
            true
        );
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Biometric registration failed: ' . $e->getMessage()]);
        exit;
    }

    // Save credential to DB
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
