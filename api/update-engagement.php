<?php
require_once '../path.php';
require_once '../includes/functions.php';

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
        if (isset($data[$key]) && $data[$key] !== '') {
            $updateFields[] = "$column = ?";
            $params[] = $data[$key];
            $types .= 's';
        }
    }

    if (empty($updateFields)) {
        echo json_encode(['success' => false, 'message' => 'No fields to update']);
        exit;
    }

    $query = "UPDATE engagements SET " . implode(', ', $updateFields) . " WHERE engagement_idno = ?";
    $params[] = $engagement_id;
    $types .= 's';

    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Engagement updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed: ' . $stmt->error]);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>