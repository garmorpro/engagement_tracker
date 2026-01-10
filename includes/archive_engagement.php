<?php
// archive-engagement.php

header('Content-Type: application/json');

try {
    // Include your DB connection
    require_once 'db.php'; // Make sure this file sets $conn (mysqli)

    // Get the raw POST data
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['eng_id']) || empty($input['eng_id'])) {
        throw new Exception('Engagement ID is required.');
    }

    $engId = $input['eng_id'];

    // Prepare the update query
    $stmt = $conn->prepare("UPDATE engagements SET eng_status = 'archived' WHERE eng_idno = ?");
    $stmt->bind_param("s", $engId);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Engagement archived successfully.']);
    } else {
        throw new Exception('Failed to update engagement status.');
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
