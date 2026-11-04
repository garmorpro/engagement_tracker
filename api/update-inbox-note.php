<?php
// api/update-inbox-note.php
// Actions on the Notes page (pages/notes.php), one endpoint, chosen by "action":
//   read      { id, read: true|false }        mark one note read / unread
//   read_all  {}                              mark every note read
//   attach    { id, engagement_idno }         attach to an engagement; '' detaches it
//   delete    { id }                          remove the note
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

$action = (string) ($data['action'] ?? '');
$id = (int) ($data['id'] ?? 0);

try {
    switch ($action) {
        case 'read':
            $read = !empty($data['read']) ? 1 : 0;
            $stmt = $conn->prepare("UPDATE inbox_notes SET is_read = ? WHERE note_id = ?");
            $stmt->bind_param('ii', $read, $id);
            break;

        case 'read_all':
            $stmt = $conn->prepare("UPDATE inbox_notes SET is_read = 1 WHERE is_read = 0");
            break;

        case 'attach':
            $idno = trim((string) ($data['engagement_idno'] ?? ''));
            if ($idno !== '') {
                $check = $conn->prepare("SELECT 1 FROM engagements WHERE eng_idno = ? AND eng_status <> 'archived'");
                $check->bind_param('s', $idno);
                $check->execute();
                $found = $check->get_result()->num_rows > 0;
                $check->close();
                if (!$found) {
                    http_response_code(404);
                    echo json_encode(['success' => false, 'message' => 'Engagement not found']);
                    exit;
                }
            }
            $idnoOrNull = $idno !== '' ? $idno : null;
            // Attaching counts as having dealt with it.
            $stmt = $conn->prepare("UPDATE inbox_notes SET engagement_idno = ?, is_read = IF(? IS NULL, is_read, 1) WHERE note_id = ?");
            $stmt->bind_param('ssi', $idnoOrNull, $idnoOrNull, $id);
            break;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM inbox_notes WHERE note_id = ?");
            $stmt->bind_param('i', $id);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Unknown action']);
            exit;
    }

    $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => true, 'unread' => getInboxUnreadCount($conn)]);
} catch (Throwable $e) {
    error_log('update-inbox-note: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not update the note']);
}
