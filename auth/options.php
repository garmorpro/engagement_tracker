<?php
declare(strict_types=1);
require '/var/www/engagement_tracker/vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\AuthenticationExtensions\AuthenticationExtensionsClientInputs;
use ParagonIE\ConstantTime\Base64UrlSafe;

session_start();
header('Content-Type: application/json');

$userUUID = $_GET['user_uuid'] ?? '';
if (!$userUUID) { http_response_code(400); echo json_encode(['error'=>'Invalid user']); exit; }

$stmt = $conn->prepare("SELECT credential_id FROM webauthn_credentials WHERE user_uuid = ?");
$stmt->bind_param('s', $userUUID);
$stmt->execute();
$res = $stmt->get_result();

$allowCredentials = [];
while ($row = $res->fetch_assoc()) {
    $allowCredentials[] = new PublicKeyCredentialDescriptor(
        PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
        Base64UrlSafe::decode($row['credential_id'])
    );
}
$stmt->close();

if (empty($allowCredentials)) { http_response_code(400); echo json_encode(['error'=>'No credentials']); exit; }

$challenge = random_bytes(32);
$_SESSION['webauthn_authentication'] = ['challenge'=>$challenge,'user_uuid'=>$userUUID,'time'=>time()];

$options = new PublicKeyCredentialRequestOptions(
    $challenge,
    60000,
    null,
    $allowCredentials,
    PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED,
    new AuthenticationExtensionsClientInputs()
);

echo json_encode([
    'challenge'=>Base64UrlSafe::encodeUnpadded($options->getChallenge()),
    'timeout'=>$options->getTimeout(),
    'rpId'=>$_SERVER['HTTP_HOST'],
    'allowCredentials'=>array_map(fn($cred)=>['type'=>'public-key','id'=>Base64UrlSafe::encodeUnpadded($cred->getId())],$allowCredentials),
    'userVerification'=>'required'
]);
