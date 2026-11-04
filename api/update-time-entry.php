<?php
// api/update-time-entry.php
// Edits one time entry. Only its author (or an admin) may change it.
// POST JSON: { entry_id, entry_date, hours, task?, note? }
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$entryId = is_array($data) ? (int) ($data['entry_id'] ?? 0) : 0;
if ($entryId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    [$date, $minutes, $note, $task] = validateTimeEntryInput($data);

    $stmt = $conn->prepare('SELECT engagement_idno, author_user_id FROM engagement_time_entries WHERE entry_id = ?');
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$entry) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Time entry not found']);
        exit;
    }
    $isAdmin = in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'], true);
    if (!$isAdmin && (int) $entry['author_user_id'] !== (int) ($_SESSION['user_id'] ?? 0)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You can only change your own time entries']);
        exit;
    }

    $noteOrNull = $note !== '' ? $note : null;
    $taskOrNull = $task !== '' ? $task : null;
    $stmt = $conn->prepare('UPDATE engagement_time_entries SET entry_date = ?, minutes = ?, task = ?, note = ? WHERE entry_id = ?');
    $stmt->bind_param('sissi', $date, $minutes, $taskOrNull, $noteOrNull, $entryId);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, 'time_entry_updated', 'engagement', $entry['engagement_idno'], 'Changed a time entry on ' . $entry['engagement_idno'] . ' to ' . round($minutes / 60, 2) . ' hrs');
    echo json_encode(['success' => true]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('update-time-entry: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update the time entry']);
}
