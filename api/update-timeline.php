<?php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagement_id = $data['engagement_id'] ?? null;

if (!$engagement_id) {
    echo json_encode(['success' => false, 'message' => 'Missing engagement ID']);
    exit;
}

try {
    // Build the update query dynamically with only provided dates
    $updates = [];
    $params = [];
    $types = '';

    $dateFields = [
        'internal_planning_call_date',
        'planning_memo_date',
        'irl_due_date',
        'client_planning_call_date',
        'fieldwork_date',
        'fieldwork_client_calls_start_date',
        'fieldwork_client_calls_end_date',
        'fieldwork_documentation_start_date',
        'fieldwork_documentation_end_date',
        'leadsheet_date',
        'conclusion_memo_date',
        'draft_report_due_date',
        'final_report_date',
        'archive_date'
    ];

    foreach ($dateFields as $field) {
        if (isset($data[$field])) {
            $value = $data[$field] ?: null;
            $updates[] = "$field = ?";
            $params[] = $value;
            $types .= 's';
        }
    }

    // Handled separately from $dateFields above: it's a day-of-week index
    // (0-6, Sunday-Saturday), not a date, and 0 (Sunday) is a valid value
    // that the `?: null` falsy-check above would incorrectly wipe out —
    // PHP treats the string "0" as falsy.
    $weeklyDayChanged = false;
    $weeklyDayValue = null;
    if (isset($data['weekly_status_call_day'])) {
        $rawDay = $data['weekly_status_call_day'];
        if ($rawDay === '' || $rawDay === null) {
            $weeklyDayValue = null;
        } elseif (ctype_digit((string) $rawDay) && (int) $rawDay >= 0 && (int) $rawDay <= 6) {
            $weeklyDayValue = (int) $rawDay;
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid weekly status call day']);
            exit;
        }
        $weeklyDayChanged = true;
        $updates[] = 'weekly_status_call_day = ?';
        $params[] = $weeklyDayValue;
        $types .= 's';
    }

    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'No data to update']);
        exit;
    }

    // Add engagement_id to params and type string
    $params[] = $engagement_id;
    $types .= 's';

    // Build the query
    $query = "UPDATE engagement_timeline SET " . implode(', ', $updates) . " WHERE engagement_idno = ?";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    // Bind parameters
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // A shared weekly status call only makes sense on one day — if this
        // engagement is linked to others (via weekly_status_call_group),
        // changing its day carries the same change to everyone else in the
        // group, so they never drift apart.
        if ($weeklyDayChanged) {
            $groupStmt = $conn->prepare("SELECT weekly_status_call_group FROM engagement_timeline WHERE engagement_idno = ?");
            $groupStmt->bind_param('s', $engagement_id);
            $groupStmt->execute();
            $groupRow = $groupStmt->get_result()->fetch_assoc();
            $groupStmt->close();

            if (!empty($groupRow['weekly_status_call_group'])) {
                $groupId = $groupRow['weekly_status_call_group'];
                $syncStmt = $conn->prepare("UPDATE engagement_timeline SET weekly_status_call_day = ?
                                             WHERE weekly_status_call_group = ? AND engagement_idno != ?");
                $syncStmt->bind_param('sss', $weeklyDayValue, $groupId, $engagement_id);
                $syncStmt->execute();
                $syncStmt->close();
            }
        }
        echo json_encode(['success' => true, 'message' => 'Timeline updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>