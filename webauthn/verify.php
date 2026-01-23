<?php
declare(strict_types=1);
require '/var/www/engagement_tracker/vendor/autoload.php';
require_once __DIR__ . '/../includes/db.php';

use Webauthn\PublicKeyCredentialLoader;
use Webauthn\AuthenticatorAssertionResponseValidator;
use ParagonIE\ConstantTime\Base64UrlSafe;

session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true) ?? [];
if (!$data) { http_response_code(400); echo json_encode(['error'=>'Invalid request']); exit; }

$loader = new PublicKeyCredentialLoader();
try { $publicKeyCredential = $loader->loadArray($data); }
catch(Throwable $e){ http_response_code(400); echo json_encode(['error'=>'Invalid credential']); exit; }

$session = $_SESSION['webauthn_authentication'] ?? null;
if(!$session){ http_response_code(400); echo json_encode(['error'=>'No challenge']); exit; }

$validator = AuthenticatorAssertionResponseValidator::create();
try {
    $credentialSource = $validator->check(
        $publicKeyCredential->getResponse(),
        $publicKeyCredential->getRawId(),
        $session['challenge'],
        $_SERVER['HTTP_HOST'],
        null,
        true
    );
} catch(Throwable $e){
    http_response_code(400);
    echo json_encode(['error'=>'Authentication failed']);
    exit;
}

// TODO: Set session or cookie to mark user logged in
$_SESSION['user_uuid'] = $session['user_uuid'];

echo json_encode(['success'=>true]);
