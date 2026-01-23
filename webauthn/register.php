<?php
declare(strict_types=1);
require '/var/www/engagement_tracker/vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAttestationResponseValidator;
use ParagonIE\ConstantTime\Base64UrlSafe;

session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $accountName = $_POST['account_name'] ?? 'Unnamed Account';
    $userUUID = $_POST['user_uuid'] ?: bin2hex(random_bytes(16));

    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    // Load credential
    $loader = new PublicKeyCredentialLoader();
    try {
        $publicKeyCredential = $loader->loadArray($data);
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }

    // Validate attestation
    $validator = AuthenticatorAttestationResponseValidator::create();
    try {
        $credentialSource = $validator->check(
            $publicKeyCredential->getResponse(),
            $publicKeyCredential->getRawId(),
            random_bytes(32), // challenge placeholder
            $_SERVER['HTTP_HOST'],
            random_bytes(16),
            true
        );
    } catch (Throwable $e) {
        http_response_code(400);
        echo json_encode(['error' => 'Biometric registration failed']);
        exit;
    }

    // Insert account
    $stmt = $conn->prepare("INSERT INTO biometric_accounts (user_uuid, account_name) VALUES (?, ?)");
    $stmt->bind_param('ss', $userUUID, $accountName);
    $stmt->execute();
    $stmt->close();

    // Insert credential
    $stmt = $conn->prepare("INSERT INTO webauthn_credentials (user_uuid, credential_id, public_key, sign_count) VALUES (?, ?, ?, ?)");
    $credId = Base64UrlSafe::encodeUnpadded($credentialSource->getPublicKeyCredentialId());
    $publicKey = $credentialSource->getCredentialPublicKey();
    $signCount = $credentialSource->getCounter();
    $stmt->bind_param('sssi', $userUUID, $credId, $publicKey, $signCount);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true, 'user_uuid' => $userUUID]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
