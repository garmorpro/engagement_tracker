<?php
// api/delete-time-entry.php
// Deletes one time entry. Only its author (or an admin) may delete it.
// POST JSON: { entry_id }
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
    $stmt = $conn->prepare('SELECT engagement_idno, author_user_id, minutes FROM engagement_time_entries WHERE entry_id = ?');
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
        echo json_encode(['success' => false, 'message' => 'You can only delete your own time entries']);
        exit;
    }

    $stmt = $conn->prepare('DELETE FROM engagement_time_entries WHERE entry_id = ?');
    $stmt->bind_param('i', $entryId);
    $stmt->execute();
    $stmt->close();

    logActivity($conn, 'time_entry_deleted', 'engagement', $entry['engagement_idno'], 'Deleted a ' . round($entry['minutes'] / 60, 2) . ' hr time entry on ' . $entry['engagement_idno']);
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('delete-time-entry: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not delete the time entry']);
}
