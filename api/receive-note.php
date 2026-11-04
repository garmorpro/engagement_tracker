<?php
// api/receive-note.php
// Where another app sends notes to the Notes page (pages/notes.php). Not session
// based: the sender proves itself with a shared secret, NOTES_API_KEY in the
// server's .env, sent as either header
//     X-API-Key: <key>            or        Authorization: Bearer <key>
//
// POST JSON (or a normal form post):
//     text         required  the note (up to 20,000 characters)
//     title        optional  a short heading
//     source       optional  which app/person sent it, shown on the note ("Voice memos")
//     engagement   optional  an ENG id ("ENG-2026-015") or an engagement/client name
//     external_id  optional  the sender's own id for the note; sending the same
//                            source + external_id again is ignored, so retries are safe
//
// A note with no engagement, or one that matches nothing (or several), simply lands
// in the inbox unattached; what the sender wrote is kept as the "hint" so it can be
// attached by hand. An exact match on exactly one engagement attaches it right away.
//
//   curl -X POST https://engagements.morganserver.com/api/receive-note.php \
//        -H "X-API-Key: $NOTES_API_KEY" -H "Content-Type: application/json" \
//        -d '{"text":"Call Todd about the SOC 2 scope","source":"Phone","engagement":"ENG-2026-015"}'
require_once '../path.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

function noteReply(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    noteReply(405, ['success' => false, 'message' => 'POST only']);
}

$expectedKey = (string) ($_ENV['NOTES_API_KEY'] ?? getenv('NOTES_API_KEY') ?: '');
if (strlen($expectedKey) < 16) {
    noteReply(503, ['success' => false, 'message' => 'Notes API key is not set up on the server']);
}

$ip = getClientIp();
try {
    if (isRateLimited($conn, $ip, 'notes_api', 10, 15 * 60)) {
        noteReply(429, ['success' => false, 'message' => 'Too many failed attempts. Try again later.']);
    }
} catch (Throwable $e) {
    // login_attempts missing: skip the limit rather than refuse every note
}

$givenKey = trim((string) ($_SERVER['HTTP_X_API_KEY'] ?? ''));
if ($givenKey === '' && preg_match('/^Bearer\s+(.+)$/i', (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? ''), $m)) {
    $givenKey = trim($m[1]);
}
if ($givenKey === '' || !hash_equals($expectedKey, $givenKey)) {
    try {
        recordFailedAttempt($conn, $ip, 'notes_api');
    } catch (Throwable $e) {
    }
    noteReply(401, ['success' => false, 'message' => 'Invalid API key']);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}

$text = trim((string) ($data['text'] ?? $data['note_text'] ?? $data['note'] ?? ''));
if ($text === '') {
    noteReply(400, ['success' => false, 'message' => 'Missing note text']);
}
if (mb_strlen($text) > 20000) {
    noteReply(413, ['success' => false, 'message' => 'Note is too long (20,000 characters max)']);
}
$title = mb_substr(trim((string) ($data['title'] ?? '')), 0, 255);
$source = mb_substr(trim((string) ($data['source'] ?? '')), 0, 100);
$hint = mb_substr(trim((string) ($data['engagement'] ?? $data['engagement_idno'] ?? '')), 0, 255);
$externalId = mb_substr(trim((string) ($data['external_id'] ?? '')), 0, 100);

try {
    // A retry of a note we already have: report it, don't add it twice.
    if ($externalId !== '') {
        $stmt = $conn->prepare("SELECT note_id, engagement_idno FROM inbox_notes WHERE source = ? AND external_id = ?");
        $stmt->bind_param('ss', $source, $externalId);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($existing) {
            noteReply(200, ['success' => true, 'duplicate' => true, 'note_id' => (int) $existing['note_id'], 'engagement' => $existing['engagement_idno']]);
        }
    }

    // Attach only on an unambiguous match: the ENG id, else a name that is exactly one
    // engagement's name, else a client name that has exactly one engagement.
    $attachTo = null;
    if ($hint !== '') {
        foreach (['eng_idno', 'eng_name', 'eng_client'] as $column) {
            try {
                $stmt = $conn->prepare("SELECT eng_idno FROM engagements WHERE LOWER(`$column`) = LOWER(?) AND eng_status <> 'archived'");
                $stmt->bind_param('s', $hint);
                $stmt->execute();
                $matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
            } catch (Throwable $e) {
                continue; // e.g. eng_client not migrated yet
            }
            if (count($matches) === 1) {
                $attachTo = $matches[0]['eng_idno'];
                break;
            }
            if (count($matches) > 1) {
                break; // ambiguous: leave it for a person to attach
            }
        }
    }

    $titleOrNull = $title !== '' ? $title : null;
    $hintOrNull = $hint !== '' ? $hint : null;
    $externalOrNull = $externalId !== '' ? $externalId : null;
    $stmt = $conn->prepare("INSERT INTO inbox_notes (note_title, note_text, source, external_id, engagement_hint, engagement_idno, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('ssssss', $titleOrNull, $text, $source, $externalOrNull, $hintOrNull, $attachTo);
    $stmt->execute();
    $noteId = (int) $conn->insert_id;
    $stmt->close();

    noteReply(201, ['success' => true, 'note_id' => $noteId, 'engagement' => $attachTo]);
} catch (Throwable $e) {
    error_log('receive-note: ' . $e->getMessage());
    noteReply(500, ['success' => false, 'message' => 'Could not save the note']);
}
