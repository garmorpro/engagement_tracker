<?php
// api/add-engagement-note.php
// Logs one entry in an engagement's Notes & Meetings log, replacing both
// the old single eng_notes text field and the old per-person independence
// popup. A single transaction, not three chained client-side calls, so a
// "log this meeting" action can't end up half-saved:
//
//   1. Always: insert the engagement_notes row itself.
//   2. Only for note_type 'planning': write each team member's independence
//      answer through to engagement_team.emp_independent (the live,
//      current-answer source the Team card reads) AND capture the same
//      answers as a JSON snapshot on the note itself (the "what did we
//      know at the time of this meeting" audit record — those two things
//      necessarily agree at the moment of saving, but the live table can
//      keep changing after while the snapshot stays fixed).
//   3. Only for note_type 'planning' or 'client': mark the matching
//      timeline step (Internal Planning Call / Client Planning Call)
//      complete, the same way the timeline's own click-to-complete
//      checkbox does (api/update-timeline-checkbox.php).
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagementIdno = trim($data['engagement_idno'] ?? '');
$noteType = trim($data['note_type'] ?? '');
$noteText = trim($data['note_text'] ?? '');
$independence = $data['independence'] ?? []; // [{emp_ids:[int,...], emp_name, value: 'Y'|'N'|null}, ...] — 'planning' only

$validTypes = ['planning', 'client', 'weekly', 'general'];
if ($engagementIdno === '' || !in_array($noteType, $validTypes, true) || $noteText === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing engagement, note type, or note text']);
    exit;
}

// note_type -> the timeline date field its "completed" checkbox lives on.
// Only these two types correspond to an actual timeline step; weekly/general
// notes don't touch the timeline at all.
$timelineDateFieldByType = [
    'planning' => 'internal_planning_call_date',
    'client' => 'client_planning_call_date',
];
$timelineCompletedFieldByType = [
    'planning' => 'internal_planning_call_completed_at',
    'client' => 'client_planning_call_completed_at',
];

try {
    $conn->begin_transaction();

    $independenceSnapshot = null;
    if ($noteType === 'planning' && !empty($independence)) {
        $snapshot = [];
        foreach ($independence as $entry) {
            $empIds = array_filter(array_map('intval', $entry['emp_ids'] ?? []));
            $empName = trim($entry['emp_name'] ?? '');
            $value = $entry['value'] ?? null; // 'Y' | 'N' | null
            if ($value !== null && !in_array($value, ['Y', 'N'], true)) $value = null;
            if ($empName !== '') $snapshot[$empName] = $value;

            if (!empty($empIds)) {
                $placeholders = implode(',', array_fill(0, count($empIds), '?'));
                $types = str_repeat('i', count($empIds));
                $stmt = $conn->prepare("UPDATE engagement_team SET emp_independent = ? WHERE emp_id IN ($placeholders)");
                if (!$stmt) throw new Exception('Prepare failed (independence): ' . $conn->error);
                $stmt->bind_param('s' . $types, $value, ...$empIds);
                if (!$stmt->execute()) throw new Exception($stmt->error);
                $stmt->close();
            }
        }
        if (!empty($snapshot)) {
            $independenceSnapshot = json_encode($snapshot);
        }
    }

    $authorUserId = $_SESSION['user_id'] ?? null;
    $authorName = $_SESSION['name'] ?? 'Unknown';

    // 'client' = visible on every engagement of the same client. Only meaningful if
    // this engagement HAS a client and the columns are migrated; otherwise it is
    // saved as a normal per-engagement note.
    $noteScope = 'engagement';
    $hasScopeColumn = $conn->query("SHOW COLUMNS FROM engagement_notes LIKE 'note_scope'")->num_rows > 0;
    if ($hasScopeColumn && ($data['note_scope'] ?? '') === 'client') {
        $stmt = $conn->prepare("SELECT eng_client FROM engagements WHERE eng_idno = ?");
        $stmt->bind_param('s', $engagementIdno);
        $stmt->execute();
        $client = trim((string) ($stmt->get_result()->fetch_assoc()['eng_client'] ?? ''));
        $stmt->close();
        if ($client !== '') {
            $noteScope = 'client';
        }
    }

    if ($hasScopeColumn) {
        $stmt = $conn->prepare("INSERT INTO engagement_notes
            (engagement_idno, note_type, note_text, independence_snapshot, author_user_id, author_name, note_scope, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) throw new Exception('Prepare failed (insert note): ' . $conn->error);
        $stmt->bind_param('ssssiss', $engagementIdno, $noteType, $noteText, $independenceSnapshot, $authorUserId, $authorName, $noteScope);
    } else {
        $stmt = $conn->prepare("INSERT INTO engagement_notes
            (engagement_idno, note_type, note_text, independence_snapshot, author_user_id, author_name, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) throw new Exception('Prepare failed (insert note): ' . $conn->error);
        $stmt->bind_param('ssssis', $engagementIdno, $noteType, $noteText, $independenceSnapshot, $authorUserId, $authorName);
    }
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $noteId = $conn->insert_id;
    $stmt->close();

    if (isset($timelineCompletedFieldByType[$noteType])) {
        $completedField = $timelineCompletedFieldByType[$noteType];
        $stmt = $conn->prepare("UPDATE engagement_timeline SET `$completedField` = NOW() WHERE engagement_idno = ?");
        if (!$stmt) throw new Exception('Prepare failed (timeline): ' . $conn->error);
        $stmt->bind_param('s', $engagementIdno);
        if (!$stmt->execute()) throw new Exception($stmt->error);
        $stmt->close();
    }

    $conn->commit();

    $label = ['planning' => 'Planning Meeting', 'client' => 'Client Planning Meeting', 'weekly' => 'Weekly Status Call', 'general' => 'Note'][$noteType];
    logActivity($conn, 'engagement_note_added', 'engagement', $engagementIdno, "Logged a {$label} on {$engagementIdno}" . ($noteScope === 'client' ? ' (shared with the whole client)' : ''));

    echo json_encode(['success' => true, 'note_id' => $noteId]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
