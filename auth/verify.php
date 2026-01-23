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

$session = $_SESSION['webauthn_authentication'] ?? null;
if (!$session) { echo json_encode(['error'=>'Missing session']); exit; }
unset($_SESSION['webauthn_authentication']);

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) { echo json_encode(['error'=>'Invalid request']); exit; }

$loader = new PublicKeyCredentialLoader(new EmptyTrustPathChecker());
try { $publicKeyCredential = $loader->loadArray($data); }
catch (Throwable $e) { echo json_encode(['error'=>$e->getMessage()]); exit; }

$stmt = $conn->prepare("SELECT * FROM webauthn_credentials WHERE user_uuid = ?");
$stmt->bind_param('s', $session['user_uuid']);
$stmt->execute();
$res = $stmt->get_result();
$credentialRow = $res->fetch_assoc();
$stmt->close();

if (!$credentialRow) { echo json_encode(['error'=>'Credential not found']); exit; }

$validator = new AuthenticatorAssertionResponseValidator();
try {
    $credentialSource = $validator->check(
        $publicKeyCredential->getResponse(),
        $publicKeyCredential->getRawId(),
        $session['challenge'],
        $_SERVER['HTTP_HOST'],
        $credentialRow['public_key'],
        $credentialRow['sign_count']
    );
} catch (Throwable $e) {
    echo json_encode(['error'=>'Authentication failed']);
    exit;
}

echo json_encode(['success'=>true]);
