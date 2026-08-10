<?php
// Use absolute path instead of relative path
$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

/**
 * Create a notification in the database
 *
 * @param string $engagement_idno - Engagement ID
 * @param string $notif_type - Type: upcoming_key_date, upcoming_milestone, ready_to_archive
 * @param string $notif_title - Notification title
 * @param string $notif_message - Notification message
 * @param string|null $notif_field - Which specific item this is about: the
 *        timeline date column for upcoming_key_date, the milestone id (as a
 *        string) for upcoming_milestone, or null for ready_to_archive. Used
 *        both to scope the "already notified" check to that specific item
 *        (rather than the whole engagement) and to resolve this notification
 *        automatically when that item gets marked complete.
 * @return bool - Success status
 */
function createNotification($engagement_idno, $notif_type, $notif_title, $notif_message, $notif_field = null) {
    global $conn;

    // Check if table exists
    $tableCheckQuery = "SHOW TABLES LIKE 'engagement_notifications'";
    $tableCheckResult = $conn->query($tableCheckQuery);

    if (!$tableCheckResult || $tableCheckResult->num_rows === 0) {
        return false; // Table doesn't exist yet
    }

    $query = "INSERT INTO engagement_notifications
              (engagement_idno, notif_type, notif_title, notif_message, notif_field, is_read, notif_timestamp)
              VALUES (?, ?, ?, ?, ?, 'N', NOW())";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('sssss', $engagement_idno, $notif_type, $notif_title, $notif_message, $notif_field);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        sendSlackNotification($conn, "*{$notif_title}*\n{$notif_message}");
        sendNtfyNotification($conn, $notif_title, $notif_message);
    }

    return $result;
}

/**
 * Marks any open notification(s) matching $notif_type (+ $notif_field, if
 * given) as read. Called from the endpoints that actually mark the
 * underlying item complete (timeline checkbox, milestone checkbox, archive),
 * so a notification disappears when the thing it was about gets resolved
 * instead of sitting there — possibly showing increasingly stale info —
 * until someone happens to click it.
 */
function resolveNotifications($engagement_idno, $notif_type, $notif_field = null) {
    global $conn;

    if ($notif_field !== null) {
        $stmt = $conn->prepare("UPDATE engagement_notifications SET is_read = 'Y'
                                 WHERE engagement_idno = ? AND notif_type = ? AND notif_field = ? AND is_read = 'N'");
        $stmt->bind_param('sss', $engagement_idno, $notif_type, $notif_field);
    } else {
        $stmt = $conn->prepare("UPDATE engagement_notifications SET is_read = 'Y'
                                 WHERE engagement_idno = ? AND notif_type = ? AND is_read = 'N'");
        $stmt->bind_param('ss', $engagement_idno, $notif_type);
    }
    $stmt->execute();
    $stmt->close();
}

/**
 * upcoming_key_date notifications can now bundle several date fields into
 * one message, so completing a single field shouldn't resolve the whole
 * notification unless every field it covers is complete — otherwise you'd
 * lose the alert for the other still-open items in that bundle. Checks the
 * live timeline state for every field named in each candidate notification's
 * notif_field list before resolving it.
 */
function resolveKeyDateNotification($engagement_idno, $completedField) {
    global $conn;

    $completedColMap = [
        'internal_planning_call_date' => 'internal_planning_call_completed_at',
        'planning_memo_date' => 'planning_memo_completed_at',
        'irl_due_date' => 'irl_completed_at',
        'client_planning_call_date' => 'client_planning_call_completed_at',
        'fieldwork_date' => 'fieldwork_completed_at',
        'fieldwork_client_calls_date' => 'fieldwork_client_calls_completed_at',
        'fieldwork_documentation_date' => 'fieldwork_documentation_completed_at',
        'leadsheet_date' => 'leadsheet_completed_at',
        'conclusion_memo_date' => 'conclusion_memo_completed_at',
        'draft_report_due_date' => 'draft_report_completed_at',
        'final_report_date' => 'final_report_completed_at',
        'archive_date' => 'archive_completed_at',
    ];

    $stmt = $conn->prepare("SELECT notif_id, notif_field FROM engagement_notifications
                             WHERE engagement_idno = ? AND notif_type = 'upcoming_key_date' AND is_read = 'N'
                             AND FIND_IN_SET(?, notif_field)");
    $stmt->bind_param('ss', $engagement_idno, $completedField);
    $stmt->execute();
    $candidates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (!$candidates) return;

    $tlStmt = $conn->prepare("SELECT * FROM engagement_timeline WHERE engagement_idno = ?");
    $tlStmt->bind_param('s', $engagement_idno);
    $tlStmt->execute();
    $timeline = $tlStmt->get_result()->fetch_assoc();
    $tlStmt->close();
    if (!$timeline) return;

    foreach ($candidates as $row) {
        $allDone = true;
        foreach (explode(',', $row['notif_field']) as $field) {
            $completedCol = $completedColMap[trim($field)] ?? null;
            if (!$completedCol || empty($timeline[$completedCol])) {
                $allDone = false;
                break;
            }
        }
        if ($allDone) {
            $upd = $conn->prepare("UPDATE engagement_notifications SET is_read = 'Y' WHERE notif_id = ?");
            $upd->bind_param('i', $row['notif_id']);
            $upd->execute();
            $upd->close();
        }
    }
}

/**
 * Convert timeline column name to readable title
 */
function getTimelineTitle($columnName) {
    $titles = [
        'internal_planning_call_date' => 'Internal Planning Call',
        'planning_memo_date' => 'Planning Memo',
        'irl_due_date' => 'IRL Due Date',
        'client_planning_call_date' => 'Client Planning Call',
        'fieldwork_date' => 'Fieldwork',
        'leadsheet_date' => 'Leadsheet',
        'conclusion_memo_date' => 'Conclusion Memo',
        'draft_report_due_date' => 'Draft Report Due',
        'final_report_date' => 'Final Report',
        'archive_date' => 'Archive'
    ];
    
    return $titles[$columnName] ?? $columnName;
}

/**
 * Check for upcoming key dates from engagement_timeline.
 * Notifies when a specific date field is 1-7 days away, not completed, and
 * hasn't already been notified on *for that specific field* — tracked via
 * notif_field, so completing one date and having a different one come due
 * later still notifies (the old version excluded the whole engagement
 * forever after a single notification, regardless of which field it was
 * about).
 */
function checkUpcomingKeyDates() {
    global $conn;

    // All non-archived, non-complete engagements with a timeline row
    $query = "SELECT t.*, e.eng_name
              FROM engagement_timeline t
              JOIN engagements e ON t.engagement_idno = e.eng_idno
              WHERE e.eng_status NOT IN ('archived', 'complete')";
    $result = $conn->query($query);

    $dateFields = [
        'internal_planning_call_date' => 'internal_planning_call_completed_at',
        'planning_memo_date' => 'planning_memo_completed_at',
        'irl_due_date' => 'irl_completed_at',
        'client_planning_call_date' => 'client_planning_call_completed_at',
        'fieldwork_date' => 'fieldwork_completed_at',
        'fieldwork_client_calls_date' => 'fieldwork_client_calls_completed_at',
        'fieldwork_documentation_date' => 'fieldwork_documentation_completed_at',
        'leadsheet_date' => 'leadsheet_completed_at',
        'conclusion_memo_date' => 'conclusion_memo_completed_at',
        'draft_report_due_date' => 'draft_report_completed_at',
        'final_report_date' => 'final_report_completed_at',
        'archive_date' => 'archive_completed_at'
    ];

    $titleMap = [
        'internal_planning_call_date' => 'Internal Planning Call',
        'planning_memo_date' => 'Planning Memo',
        'irl_due_date' => 'IRL Due Date',
        'client_planning_call_date' => 'Client Planning Call',
        'fieldwork_date' => 'Fieldwork',
        'fieldwork_client_calls_date' => 'Fieldwork - Client Calls',
        'fieldwork_documentation_date' => 'Fieldwork - Documentation',
        'leadsheet_date' => 'Leadsheet',
        'conclusion_memo_date' => 'Conclusion Memo',
        'draft_report_due_date' => 'Draft Report Due',
        'final_report_date' => 'Final Report',
        'archive_date' => 'Archive'
    ];

    // (engagement_idno|field) pairs already notified on — checked against
    // every field named in a notif_field list (which may cover more than one
    // field, since qualifying items for the same engagement get bundled into
    // a single notification below), so each field is only ever notified
    // once, independently of the others.
    $already = [];
    $notifiedResult = $conn->query("SELECT engagement_idno, notif_field FROM engagement_notifications
                                     WHERE notif_type = 'upcoming_key_date' AND notif_field IS NOT NULL");
    if ($notifiedResult) {
        while ($row = $notifiedResult->fetch_assoc()) {
            foreach (explode(',', $row['notif_field']) as $f) {
                $already[$row['engagement_idno'] . '|' . trim($f)] = true;
            }
        }
    }

    if ($result) {
        while ($timeline = $result->fetch_assoc()) {
            $engagement_idno = $timeline['engagement_idno'];
            $eng_name = $timeline['eng_name'];

            // Collect every not-yet-notified field currently due within the
            // window for this engagement, then send one notification/Slack
            // message covering all of them instead of one per field.
            $dueItems = [];
            foreach ($dateFields as $dateCol => $completedCol) {
                $dateValue = $timeline[$dateCol];
                $completedValue = $timeline[$completedCol];

                if ($dateValue && !$completedValue) {
                    $daysUntilDate = round((strtotime($dateValue) - time()) / 86400);
                    if ($daysUntilDate >= 1 && $daysUntilDate <= 7 && !isset($already[$engagement_idno . '|' . $dateCol])) {
                        $dueItems[] = [
                            'field' => $dateCol,
                            'title' => $titleMap[$dateCol],
                            'date' => $dateValue,
                            'daysUntil' => $daysUntilDate,
                        ];
                    }
                }
            }

            if (empty($dueItems)) continue;

            $notifField = implode(',', array_column($dueItems, 'field'));

            if (count($dueItems) === 1) {
                $item = $dueItems[0];
                $title = 'Upcoming Key Date';
                $message = $eng_name . ' - ' . $item['title'] . ' is due ' . formatKeyDate($item['date']) . ' (' . formatDaysAway($item['daysUntil']) . ')';
            } else {
                $title = 'Upcoming Key Dates';
                $lines = array_map(
                    fn($item) => '• ' . $item['title'] . ' — ' . formatKeyDate($item['date']) . ' (' . formatDaysAway($item['daysUntil']) . ')',
                    $dueItems
                );
                $message = $eng_name . ' has ' . count($dueItems) . ' upcoming key dates:' . "\n" . implode("\n", $lines);
            }

            createNotification($engagement_idno, 'upcoming_key_date', $title, $message, $notifField);
        }
    }
}

function formatKeyDate($dateValue) {
    return date('M j, Y', strtotime($dateValue));
}

function formatDaysAway($days) {
    return $days . ' day' . ($days == 1 ? '' : 's');
}

/**
 * Check for upcoming milestones.
 * Notifies when a milestone's due date is 1-5 days away, not completed, and
 * hasn't already been notified on for that specific milestone (tracked via
 * notif_field = the milestone's ms_id) — same per-item fix as key dates.
 */
function checkUpcomingMilestones() {
    global $conn;

    $query = "
        SELECT m.ms_id, m.engagement_idno, m.milestone_type, m.due_date, e.eng_name
        FROM engagement_milestones m
        JOIN engagements e ON m.engagement_idno = e.eng_idno
        WHERE m.is_completed = 'N'
        AND m.due_date IS NOT NULL
    ";
    $result = $conn->query($query);

    $already = [];
    $notifiedResult = $conn->query("SELECT notif_field FROM engagement_notifications
                                     WHERE notif_type = 'upcoming_milestone' AND notif_field IS NOT NULL");
    if ($notifiedResult) {
        while ($row = $notifiedResult->fetch_assoc()) {
            $already[$row['notif_field']] = true;
        }
    }

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $msKey = (string) $row['ms_id'];
            if (isset($already[$msKey])) continue;

            $daysUntilDate = round((strtotime($row['due_date']) - time()) / 86400);

            // Notify when date is 1-5 days away
            if ($daysUntilDate >= 1 && $daysUntilDate <= 5) {
                // Convert milestone_type from snake_case to Title Case
                $milestoneTitle = implode(' ', array_map('ucfirst', explode('_', strtolower($row['milestone_type']))));

                $title = 'Upcoming Milestone';
                $message = $row['eng_name'] . ' - ' . $milestoneTitle . ' due in ' . $daysUntilDate . ' days';

                createNotification(
                    $row['engagement_idno'],
                    'upcoming_milestone',
                    $title,
                    $message,
                    $msKey
                );
            }
        }
    }
}

/**
 * Check for completed engagements that should be archived
 * Notifies when engagement has been complete for 3+ days
 * Runs daily - prevents duplicate notifications on same day
 */
function checkEngagementsReadyToArchive() {
    global $conn;
    
    $query = "
        SELECT eng_idno, eng_name, eng_complete_date
        FROM engagements 
        WHERE eng_status = 'complete'
        AND eng_complete_date IS NOT NULL
        AND DATE(eng_complete_date) <= DATE_SUB(CURDATE(), INTERVAL 3 DAY)
        AND eng_idno NOT IN (
            SELECT DISTINCT engagement_idno 
            FROM engagement_notifications 
            WHERE notif_type = 'ready_to_archive'
        )
    ";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $title = 'Ready to Archive';
            $message = $row['eng_name'] . ' has been complete for 3+ days and is ready to archive';
            
            createNotification(
                $row['eng_idno'],
                'ready_to_archive',
                $title,
                $message
            );
        }
    }
}

/**
 * Clear old read notifications (older than 30 days)
 * Run periodically to keep the table clean
 */
function clearOldNotifications($days = 30) {
    global $conn;
    
    // Check if table exists first
    $tableCheckQuery = "SHOW TABLES LIKE 'engagement_notifications'";
    $tableCheckResult = $conn->query($tableCheckQuery);
    
    if (!$tableCheckResult || $tableCheckResult->num_rows === 0) {
        return false;
    }
    
    $query = "DELETE FROM engagement_notifications 
              WHERE is_read = 'Y' 
              AND notif_timestamp < DATE_SUB(NOW(), INTERVAL ? DAY)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $days);
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}
?>