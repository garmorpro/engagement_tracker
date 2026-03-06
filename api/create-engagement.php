<?php
require_once '../path.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$eng_name = $data['eng_name'] ?? null;
$eng_location = $data['eng_location'] ?? null;
$eng_poc = $data['eng_poc'] ?? null;
$eng_repeat = $data['eng_repeat'] ?? null;
$eng_audit_type = $data['eng_audit_type'] ?? null;
$eng_soc_type = $data['eng_soc_type'] ?? null;
$eng_scope = $data['eng_scope'] ?? null;
$eng_tsc = $data['eng_tsc'] ?? null;
$eng_start_period = $data['eng_start_period'] ?? null;
$eng_end_period = $data['eng_end_period'] ?? null;
$eng_as_of_date = $data['eng_as_of_date'] ?? null;
$eng_status = $data['eng_status'] ?? 'planning';

if (!$eng_name) {
    echo json_encode(['success' => false, 'message' => 'Engagement name is required']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Insert into engagements table
    $query = "INSERT INTO engagements (
        eng_name, eng_location, eng_poc, eng_repeat, eng_audit_type, 
        eng_soc_type, eng_scope, eng_tsc, eng_start_period, eng_end_period, 
        eng_as_of_date, eng_status, eng_created, eng_updated
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        'ssssssssssss',
        $eng_name, $eng_location, $eng_poc, $eng_repeat, $eng_audit_type,
        $eng_soc_type, $eng_scope, $eng_tsc, $eng_start_period, $eng_end_period,
        $eng_as_of_date, $eng_status
    );

    if (!$stmt->execute()) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to create engagement: ' . $stmt->error]);
        $stmt->close();
        exit;
    }

    // Get the generated eng_idno (if auto-increment ID)
    $engagement_id = $conn->insert_id;
    $stmt->close();

    // Get the actual eng_idno from the database (in case it's a custom format)
    $selectQuery = "SELECT eng_idno FROM engagements WHERE eng_id = ?";
    $stmt = $conn->prepare($selectQuery);
    $stmt->bind_param('i', $engagement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $eng_idno = $row['eng_idno'];
    $stmt->close();

    // Automatically create timeline row for this engagement
    $timelineQuery = "INSERT INTO engagement_timeline (engagement_idno) VALUES (?)";
    $stmt = $conn->prepare($timelineQuery);
    $stmt->bind_param('s', $eng_idno);

    if (!$stmt->execute()) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Failed to create timeline: ' . $stmt->error]);
        $stmt->close();
        exit;
    }
    $stmt->close();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Engagement created successfully',
        'engagement_id' => $eng_idno
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>