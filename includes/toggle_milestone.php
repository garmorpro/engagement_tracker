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

if (isset($_POST['set_today']) && $_POST['set_today'] == '1') {
    $today = date('Y-m-d');
    $stmt = $conn->prepare("UPDATE engagement_milestones SET is_completed = ?, due_date = ? WHERE ms_id = ?");
    $stmt->bind_param("ssi", $newValue, $today, $ms_id);
    $stmt->execute();
} else {
    $stmt = $conn->prepare("UPDATE engagement_milestones SET is_completed = ? WHERE ms_id = ?");
    $stmt->bind_param("si", $newValue, $ms_id);
    $stmt->execute();
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
