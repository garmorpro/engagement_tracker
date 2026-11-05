<?php
// api/dashboard-log-time.php
// The Desk Ctrl Panel's Log time button (via n8n) writes a time entry here.
// POST JSON: { engagement: "ENG-2026-001", minutes: 30, task?: "Fieldwork", note?: "..." }
// engagement "ADMIN" logs time that belongs to no engagement.
//
// Same safeguards as dashboard-feed.php / dashboard-open-token.php:
//   1. Home network only: anything forwarded by Cloudflare (CF-* headers) is refused.
//   2. Shared secret: DASH_API_KEY in .env, sent as the X-Dash-Key header.
// The entry is attributed to the account named by DASH_LOGIN_USER_ID in .env.
require_once '../path.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function logTimeReply(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') logTimeReply(405, ['success' => false, 'message' => 'Not available']);

foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_CF_RAY', 'HTTP_CF_VISITOR', 'HTTP_CDN_LOOP'] as $cfHeader) {
    if (!empty($_SERVER[$cfHeader])) logTimeReply(403, ['success' => false, 'message' => 'Not available']);
}

$expectedKey = (string) ($_ENV['DASH_API_KEY'] ?? '');
$givenKey = (string) ($_SERVER['HTTP_X_DASH_KEY'] ?? '');
if (strlen($expectedKey) < 24 || !hash_equals($expectedKey, $givenKey)) logTimeReply(403, ['success' => false, 'message' => 'Not available']);

$data = json_decode((string) file_get_contents('php://input'), true);
$engagementIdno = is_array($data) ? trim((string) ($data['engagement'] ?? '')) : '';
$minutes = is_array($data) ? (int) ($data['minutes'] ?? 0) : 0;
$note = is_array($data) ? mb_substr(trim((string) ($data['note'] ?? '')), 0, 500) : '';
$task = is_array($data) ? mb_substr(trim((string) ($data['task'] ?? '')), 0, 60) : '';
$userId = (int) ($_ENV['DASH_LOGIN_USER_ID'] ?? 0);

if (!preg_match('/^[A-Za-z0-9_-]{1,40}$/', $engagementIdno) || $minutes < 1 || $minutes > 720 || $userId <= 0) {
    logTimeReply(400, ['success' => false, 'message' => 'Invalid request']);
}

try {
    // "ADMIN" is the reserved id for time that belongs to no engagement.
    $exists = $engagementIdno === TIME_NO_ENGAGEMENT_ID;
    if (!$exists) {
        $stmt = $conn->prepare("SELECT 1 FROM engagements WHERE eng_idno = ? AND eng_status <> 'archived' LIMIT 1");
        $stmt->bind_param('s', $engagementIdno);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_row();
        $stmt->close();
    }
    if (!$exists) logTimeReply(404, ['success' => false, 'message' => 'Engagement not found']);

    $stmt = $conn->prepare("SELECT `name` FROM `service_accounts` WHERE `user_id` = ? AND `status` = 'active' LIMIT 1");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $account = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$account) logTimeReply(500, ['success' => false, 'message' => 'Account not found']);

    $noteOrNull = $note !== '' ? $note : null;
    $taskOrNull = $task !== '' ? $task : null;
    $today = date('Y-m-d');
    $stmt = $conn->prepare("INSERT INTO engagement_time_entries (engagement_idno, entry_date, minutes, task, note, author_user_id, author_name, source, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'desk panel', NOW())");
    $stmt->bind_param('ssissis', $engagementIdno, $today, $minutes, $taskOrNull, $noteOrNull, $userId, $account['name']);
    $stmt->execute();
    $entryId = (int) $conn->insert_id;
    $stmt->close();

    logTimeReply(201, ['success' => true, 'entry_id' => $entryId]);
} catch (Throwable $e) {
    error_log('dashboard-log-time: ' . $e->getMessage());
    logTimeReply(500, ['success' => false, 'message' => 'Could not save the time entry']);
}
