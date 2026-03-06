<?php
require_once '../path.php';
require_once '../includes/functions.php';

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
        echo json_encode(['success' => true, 'message' => 'Timeline updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>