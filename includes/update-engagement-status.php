<?php
header('Content-Type: application/json');

require_once __DIR__ . '/db_connection.php';

// Read JSON body
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'No JSON payload received'
    ]);
    exit;
}

$engId = $input['eng_id'] ?? null;
$newStatus = $input['status'] ?? null;

if (!$engId || !$newStatus) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing eng_id or status'
    ]);
    exit;
}

// Validate allowed statuses (VERY IMPORTANT)
$allowedStatuses = ['planning', 'in-progress', 'review', 'completed', 'archived'];

if (!in_array($newStatus, $allowedStatuses)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid status value'
    ]);
    exit;
}

// Prepare update
$stmt = $conn->prepare("
    UPDATE engagements
    SET eng_status = ?
    WHERE eng_idno = ?
");

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Prepare failed: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param('ss', $newStatus, $engId);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'affected_rows' => $stmt->affected_rows
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Execute failed: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
