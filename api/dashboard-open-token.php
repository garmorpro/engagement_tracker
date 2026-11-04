<?php
// api/dashboard-open-token.php
// Issues a ONE-TIME sign-in link for the Desk Ctrl Panel "open on Mac mini" feature.
// POST {"id": "ENG-2026-001"}  ->  {"success": true, "url": "https://.../auth/token-login.php?t=...&open=ENG-2026-001"}
//
// Same safeguards as dashboard-feed.php:
//   1. Home network only: anything forwarded by Cloudflare (CF-* headers) is refused.
//   2. Shared secret: DASH_API_KEY in .env, sent as the X-Dash-Key header.
// The token is 256 random bits, valid for 60 seconds, usable once. Only its hash is stored.
require_once '../path.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function tokenDeny(int $code = 403): void
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => 'Not available']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') tokenDeny(405);

foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_CF_RAY', 'HTTP_CF_VISITOR', 'HTTP_CDN_LOOP'] as $cfHeader) {
    if (!empty($_SERVER[$cfHeader])) tokenDeny();
}

$expectedKey = (string) ($_ENV['DASH_API_KEY'] ?? '');
$givenKey = (string) ($_SERVER['HTTP_X_DASH_KEY'] ?? '');
if (strlen($expectedKey) < 24 || !hash_equals($expectedKey, $givenKey)) tokenDeny();

$body = json_decode((string) file_get_contents('php://input'), true);
$id = is_array($body) ? (string) ($body['id'] ?? '') : '';
if (!preg_match('/^[A-Za-z0-9_-]{1,40}$/', $id)) tokenDeny(400);

try {
    // Only issue a link for an engagement that exists.
    $stmt = $conn->prepare('SELECT 1 FROM engagements WHERE eng_idno = ? LIMIT 1');
    $stmt->bind_param('s', $id);
    $stmt->execute();
    $exists = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$exists) tokenDeny(404);

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);

    $stmt = $conn->prepare('INSERT INTO dash_login_tokens (token_hash, expires_at) VALUES (?, DATE_ADD(NOW(), INTERVAL 60 SECOND))');
    $stmt->bind_param('s', $hash);
    $stmt->execute();
    $stmt->close();

    // Tidy up old tokens.
    $conn->query('DELETE FROM dash_login_tokens WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)');

    echo json_encode([
        'success' => true,
        'url' => BASE_URL . '/auth/token-login.php?t=' . $token . '&open=' . rawurlencode($id),
    ]);
} catch (Throwable $e) {
    error_log('dashboard-open-token.php: ' . $e->getMessage());
    tokenDeny(500);
}
