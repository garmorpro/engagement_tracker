<?php
// api/dashboard-feed.php
// Read-only summary for the Desk Ctrl Panel iPad dashboard (n8n calls this).
// It is NOT a logged-in-user endpoint, so it has its own two safeguards:
//   1. Home network only: Cloudflare adds CF-* headers to every request it forwards, so anything
//      that came through the public tunnel is refused. Only direct LAN requests get through.
//   2. A shared secret: DASH_API_KEY in .env (24+ characters), sent as the X-Dash-Key header.
// It only reads. It never writes, and it returns no PINs, notes, files or team details.
require_once '../path.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

function dashDeny(): void
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Not available']);
    exit;
}

// 1. Refuse anything that arrived via Cloudflare (i.e. from the internet).
foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_CF_RAY', 'HTTP_CF_VISITOR', 'HTTP_CDN_LOOP'] as $cfHeader) {
    if (!empty($_SERVER[$cfHeader])) dashDeny();
}

// 2. Check the shared key (constant-time compare).
$expectedKey = (string) ($_ENV['DASH_API_KEY'] ?? '');
$givenKey = (string) ($_SERVER['HTTP_X_DASH_KEY'] ?? '');
if (strlen($expectedKey) < 24 || !hash_equals($expectedKey, $givenKey)) dashDeny();

try {
    // Look 120 days ahead so every engagement has a "next due" date. The "due" count and the
    // item list only include what is overdue or due within DUE_SOON_DAYS (the tracker's own logic).
    $dueSoonDays = 7;
    $summary = getDueItemsSummary($conn, 120);

    $byEngagement = [];
    foreach (['overdue' => $summary['overdue'], 'upcoming' => $summary['upcoming']] as $kind => $items) {
        foreach ($items as $item) {
            $id = $item['engagement_idno'];
            $byEngagement[$id] ??= ['overdue' => 0, 'open' => 0, 'soonest' => null, 'minDays' => null, 'items' => []];
            $e = &$byEngagement[$id];
            if ($e['minDays'] === null || $item['days_until'] < $e['minDays']) {
                $e['minDays'] = $item['days_until'];
                $e['soonest'] = $item['due_date'];
            }
            if ($item['days_until'] <= $dueSoonDays) {
                $e['open']++;
                if ($kind === 'overdue') $e['overdue']++;
                $e['items'][] = ['title' => $item['title'], 'dueDate' => $item['due_date'], 'daysUntil' => $item['days_until']];
            }
            unset($e);
        }
    }

    $phaseLabels = ['planning' => 'Planning', 'in-progress' => 'In Progress', 'in-review' => 'In Review'];

    $engagements = [];
    $clients = [];
    $result = $conn->query(
        "SELECT eng_idno, eng_name, eng_audit_type, eng_status
         FROM engagements
         WHERE eng_status NOT IN ('archived', 'complete')
         ORDER BY eng_name"
    );
    foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
        $due = $byEngagement[$row['eng_idno']] ?? null;
        $status = 'on_track';
        if ($due) {
            if ($due['overdue'] > 0) $status = 'overdue';
            elseif ($due['minDays'] !== null && $due['minDays'] <= 3) $status = 'at_risk';
        }
        $slug = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($row['eng_name'])), '-');

        $engagements[] = [
            'id' => $row['eng_idno'],
            'clientId' => $slug,
            'client' => $row['eng_name'],
            'auditType' => $row['eng_audit_type'],
            'phase' => $phaseLabels[$row['eng_status']] ?? ucfirst((string) $row['eng_status']),
            // Field names kept from the dashboard's feed schema. Here they mean "due items".
            'pbcOutstanding' => $due ? $due['open'] : 0,
            'pbcOverdue' => $due ? $due['overdue'] : 0,
            'nextDue' => $due ? $due['soonest'] : null,
            'status' => $status,
            'dueItems' => $due ? array_slice($due['items'], 0, 6) : [],
        ];
        $clients[$slug] = ['id' => $slug, 'name' => $row['eng_name']];
    }

    // Time logged today (engagement_time_entries), for the dashboard's "Time today" button.
    // Stays empty if the table (or its task column) doesn't exist yet.
    $names = [TIME_NO_ENGAGEMENT_ID => TIME_NO_ENGAGEMENT_NAME];
    foreach ($engagements as $e) {
        $names[$e['id']] = $e['client'];
    }
    $today = date('Y-m-d');
    $timeToday = ['date' => $today, 'minutes' => 0, 'entries' => []];
    try {
        $check = $conn->query("SHOW TABLES LIKE 'engagement_time_entries'");
        if ($check && $check->num_rows > 0) {
            $stmt = $conn->prepare("SELECT engagement_idno, task, SUM(minutes) AS minutes
                                    FROM engagement_time_entries
                                    WHERE entry_date = ?
                                    GROUP BY engagement_idno, task
                                    ORDER BY minutes DESC");
            $stmt->bind_param('s', $today);
            $stmt->execute();
            foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $r) {
                $timeToday['minutes'] += (int) $r['minutes'];
                $timeToday['entries'][] = [
                    'engagementId' => $r['engagement_idno'],
                    'client' => $names[$r['engagement_idno']] ?? $r['engagement_idno'],
                    'task' => ($r['task'] !== null && $r['task'] !== '') ? $r['task'] : 'No task',
                    'minutes' => (int) $r['minutes'],
                ];
            }
            $stmt->close();
        }
    } catch (Throwable $e) {
        error_log('dashboard-feed time today: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'generatedAt' => date('c'),
        'timeToday' => $timeToday,
        'clients' => array_values($clients),
        'engagements' => $engagements,
    ]);
} catch (Throwable $e) {
    error_log('dashboard-feed.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error']);
}
