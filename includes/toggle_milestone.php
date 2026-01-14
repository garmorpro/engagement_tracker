<?php
require 'db.php';

$ms_id        = $_POST['ms_id'] ?? null;
$is_completed = $_POST['is_completed'] ?? 'N';
$setToday     = isset($_POST['set_today']);

if (!$ms_id) {
    echo json_encode(['success' => false, 'error' => 'Missing milestone']);
    exit;
}

if ($setToday && $is_completed === 'Y') {
    $sql = "
        UPDATE engagement_milestones
        SET is_completed = 'Y',
            due_date = CURDATE()
        WHERE ms_id = ?
    ";
} else {
    $sql = "
        UPDATE engagement_milestones
        SET is_completed = ?
        WHERE ms_id = ?
    ";
}

$stmt = $conn->prepare($sql);

if ($setToday && $is_completed === 'Y') {
    $stmt->bind_param('i', $ms_id);
} else {
    $stmt->bind_param('si', $is_completed, $ms_id);
}

$stmt->execute();

if ($stmt->affected_rows >= 0) {
    echo json_encode([
        'success' => true,
        'date' => $setToday ? date('M d, Y') : null
    ]);
} else {
    echo json_encode(['success' => false]);
}
