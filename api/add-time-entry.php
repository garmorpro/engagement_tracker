<?php
// api/add-time-entry.php
// Logs time against an engagement (the drawer's Time section).
// POST JSON: { engagement_idno, entry_date: "YYYY-MM-DD", hours: 1.5, task?: "Fieldwork", note?: "..." }
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagementIdno = trim((string) ($data['engagement_idno'] ?? ''));
try {
    if ($engagementIdno === '') throw new InvalidArgumentException('Missing engagement');
    [$date, $minutes, $note, $task] = validateTimeEntryInput($data);

    $stmt = $conn->prepare('SELECT 1 FROM engagements WHERE eng_idno = ? LIMIT 1');
    $stmt->bind_param('s', $engagementIdno);
    $stmt->execute();
    $exists = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    if (!$exists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Engagement not found']);
        exit;
    }

    $authorUserId = $_SESSION['user_id'] ?? null;
    $authorName = $_SESSION['name'] ?? 'Unknown';
    $noteOrNull = $note !== '' ? $note : null;
    $taskOrNull = $task !== '' ? $task : null;
    $stmt = $conn->prepare("INSERT INTO engagement_time_entries (engagement_idno, entry_date, minutes, task, note, author_user_id, author_name, source, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'tracker', NOW())");
    $stmt->bind_param('ssissis', $engagementIdno, $date, $minutes, $taskOrNull, $noteOrNull, $authorUserId, $authorName);
    $stmt->execute();
    $entryId = (int) $conn->insert_id;
    $stmt->close();

    logActivity($conn, 'time_entry_added', 'engagement', $engagementIdno, 'Logged ' . round($minutes / 60, 2) . ' hrs on ' . $engagementIdno);
    echo json_encode(['success' => true, 'entry_id' => $entryId]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('add-time-entry: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not save the time entry']);
}
