<?php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

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
    // Fetch the current status (if this update touches it) so we can log
    // an old -> new transition rather than just "status was set."
    $oldStatus = null;
    if (isset($data['eng_status'])) {
        $stmt = $conn->prepare("SELECT eng_status FROM engagements WHERE eng_idno = ?");
        $stmt->bind_param('s', $engagement_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $oldStatus = $row['eng_status'] ?? null;
    }

    // Build the update query dynamically based on provided fields
    $updateFields = [];
    $params = [];
    $types = '';

    // Map of field names to database columns
    $fieldMap = [
        'eng_name' => 'eng_name',
        'eng_location' => 'eng_location',
        'eng_poc' => 'eng_poc',
        'eng_status' => 'eng_status',
        'eng_tsc' => 'eng_tsc',
        'eng_audit_type' => 'eng_audit_type',
        'eng_soc_type' => 'eng_soc_type',
        'eng_scope' => 'eng_scope',
        'eng_as_of_date' => 'eng_as_of_date',
        'eng_start_period' => 'eng_start_period',
        'eng_end_period' => 'eng_end_period',
        'eng_repeat' => 'eng_repeat',
        'eng_notes' => 'eng_notes'
    ];

    foreach ($fieldMap as $key => $column) {
        if (isset($data[$key])) {
            $updateFields[] = "$column = ?";
            // Allow null values for all fields except eng_name
            // Convert empty strings to null for database storage
            $value = $data[$key] === '' ? null : $data[$key];
            $params[] = $value;
            $types .= 's';
        }
    }

    // If status is being updated to 'complete', also set the complete date
    if (isset($data['eng_status']) && $data['eng_status'] === 'complete') {
        $updateFields[] = "eng_complete_date = NOW()";
    }

    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $query = "UPDATE engagements SET " . implode(', ', $updateFields) . " WHERE eng_idno = ?";
    $params[] = $engagement_id;
    $types .= 's';

    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        // The PCI ROC delivery date is saved on its own so a server that hasn't
        // run the migration yet can still save every other edit.
        if (array_key_exists('eng_client', $data)) {
            try {
                $client = trim((string) $data['eng_client']);
                $client = $client === '' ? null : $client;
                $clientStmt = $conn->prepare("UPDATE engagements SET eng_client = ? WHERE eng_idno = ?");
                $clientStmt->bind_param('ss', $client, $engagement_id);
                $clientStmt->execute();
                $clientStmt->close();
            } catch (Throwable $e) {
                error_log('update-engagement.php: could not save eng_client: ' . $e->getMessage());
            }
        }
        if (array_key_exists('eng_roc_delivery_date', $data)) {
            try {
                $roc = ($data['eng_roc_delivery_date'] === '' || $data['eng_roc_delivery_date'] === null) ? null : $data['eng_roc_delivery_date'];
                $rocStmt = $conn->prepare("UPDATE engagements SET eng_roc_delivery_date = ? WHERE eng_idno = ?");
                $rocStmt->bind_param('ss', $roc, $engagement_id);
                $rocStmt->execute();
                $rocStmt->close();
            } catch (Throwable $e) {
                error_log('update-engagement.php: could not save eng_roc_delivery_date: ' . $e->getMessage());
            }
        }
        if ($oldStatus !== null && $data['eng_status'] !== $oldStatus) {
            logActivity(
                $conn,
                'engagement_status_change',
                'engagement',
                $engagement_id,
                "Changed status of {$engagement_id} from '{$oldStatus}' to '{$data['eng_status']}'"
            );
        }
        echo json_encode(['success' => true, 'message' => 'Engagement updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>