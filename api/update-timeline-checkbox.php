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
$date_field = $data['date_field'] ?? null;
$completed_field = $data['completed_field'] ?? null;
$completed_datetime = $data['completed_datetime'] ?? null;

if (!$engagement_id || !$date_field || !$completed_field) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // Validate field names to prevent SQL injection
    $validDateFields = [
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
    
    $validCompletedFields = [
        'internal_planning_call_completed_at',
        'planning_memo_completed_at',
        'irl_completed_at',
        'client_planning_call_completed_at',
        'fieldwork_completed_at',
        'leadsheet_completed_at',
        'conclusion_memo_completed_at',
        'draft_report_completed_at',
        'final_report_completed_at',
        'archive_completed_at'
    ];
    
    if (!in_array($date_field, $validDateFields) || !in_array($completed_field, $validCompletedFields)) {
        echo json_encode(['success' => false, 'message' => 'Invalid field name']);
        exit;
    }
    
    // Build the update query
    if ($completed_datetime) {
        $query = "UPDATE engagement_timeline SET $completed_field = ? WHERE engagement_idno = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ss', $completed_datetime, $engagement_id);
    } else {
        $query = "UPDATE engagement_timeline SET $completed_field = NULL WHERE engagement_idno = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('s', $engagement_id);
    }
    
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