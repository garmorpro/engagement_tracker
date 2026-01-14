<?php
require 'db.php';

$ms_id        = $_POST['ms_id'] ?? null;
$newValue     = $_POST['is_completed'] ?? 'N';
$setToday     = isset($_POST['set_today']) && $_POST['set_today'] == '1';

if (!$ms_id) {
    echo json_encode(['success' => false, 'error' => 'Missing milestone']);
    exit;
}

if ($setToday && $newValue === 'Y') {
    // If we need to mark complete and set today
    $today = date('Y-m-d');
    $stmt = $conn->prepare("UPDATE engagement_milestones SET is_completed = ?, due_date = ? WHERE ms_id = ?");
    $stmt->bind_param("ssi", $newValue, $today, $ms_id);
} else {
    // Just toggle completed
    $stmt = $conn->prepare("UPDATE engagement_milestones SET is_completed = ? WHERE ms_id = ?");
    $stmt->bind_param("si", $newValue, $ms_id);
}

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'date' => $setToday && $newValue === 'Y' ? date('M d, Y') : null
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $stmt->error
    ]);
}

$stmt->close();
exit;
