<?php
// api/get-time-week.php
// The Time view: every time entry for one Monday-to-Sunday week.
// GET ?start=YYYY-MM-DD   any date in the week (default: today); it is moved back to that week's Monday.
// Returns { success, weekStart, weekEnd, today, entries: [...], engagements: [...] }.
//   entries      one per logged entry, with the engagement's name, and can_edit (author or admin)
//   engagements  the non-archived engagements, for the "Add time" picker
//   taskMap      { tracker task: name of the same task in the work timesheet }
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

try {
    $startRaw = trim((string) ($_GET['start'] ?? ''));
    $day = DateTime::createFromFormat('!Y-m-d', $startRaw);
    if (!$day || $day->format('Y-m-d') !== $startRaw) {
        $day = new DateTime('today');
    }
    // ISO week: Monday first. (N: 1 = Monday ... 7 = Sunday)
    $monday = (clone $day)->modify('-' . ((int) $day->format('N') - 1) . ' days');
    $sunday = (clone $monday)->modify('+6 days');
    $weekStart = $monday->format('Y-m-d');
    $weekEnd = $sunday->format('Y-m-d');

    // Engagement names are looked up separately (no JOIN: the tables have different collations).
    $names = [];
    $pickable = [];
    $audits = [];
    $res = $conn->query("SELECT eng_idno, eng_name, eng_audit_type, eng_status FROM engagements ORDER BY eng_name");
    while ($r = $res->fetch_assoc()) {
        $names[$r['eng_idno']] = $r['eng_name'];
        $audits[$r['eng_idno']] = (string) ($r['eng_audit_type'] ?? '');
        if (($r['eng_status'] ?? '') !== 'archived') {
            $pickable[] = ['id' => $r['eng_idno'], 'name' => $r['eng_name'], 'audit' => (string) ($r['eng_audit_type'] ?? '')];
        }
    }

    $entries = [];
    $check = $conn->query("SHOW TABLES LIKE 'engagement_time_entries'");
    if ($check && $check->num_rows > 0) {
        $isAdmin = in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'], true);
        $me = (int) ($_SESSION['user_id'] ?? 0);
        $stmt = $conn->prepare("SELECT entry_id, engagement_idno, entry_date, minutes, task, note, author_user_id, author_name, source
                                FROM engagement_time_entries
                                WHERE entry_date BETWEEN ? AND ?
                                ORDER BY entry_date, entry_id");
        $stmt->bind_param('ss', $weekStart, $weekEnd);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
            $entries[] = [
                'entry_id' => (int) $r['entry_id'],
                'engagement_idno' => $r['engagement_idno'],
                'name' => $names[$r['engagement_idno']] ?? $r['engagement_idno'],
                'audit' => $audits[$r['engagement_idno']] ?? '',
                'entry_date' => $r['entry_date'],
                'minutes' => (int) $r['minutes'],
                'task' => $r['task'] ?? '',
                'note' => $r['note'] ?? '',
                'author_name' => $r['author_name'] ?? '',
                'source' => $r['source'] ?? 'tracker',
                'can_edit' => $isAdmin || (int) $r['author_user_id'] === $me,
            ];
        }
        $stmt->close();
    }

    // Saved names of the tracker's tasks in the work timesheet ("FW Calls" -> "Client Calls"); see api/save-time-task-map.php.
    $taskMap = [];
    try {
        $decoded = json_decode((string) getAppSetting($conn, 'time_task_map'), true);
        if (is_array($decoded)) {
            $taskMap = $decoded;
        }
    } catch (Throwable $e) {
    }

    echo json_encode([
        'success' => true,
        'taskMap' => (object) $taskMap,
        'weekStart' => $weekStart,
        'weekEnd' => $weekEnd,
        'today' => date('Y-m-d'),
        'entries' => $entries,
        'engagements' => $pickable,
    ]);
} catch (Throwable $e) {
    error_log('get-time-week: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load the week']);
}
