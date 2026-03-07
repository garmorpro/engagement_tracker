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
    // Convert empty strings to NULL
    $eng_location = $eng_location ?: null;
    $eng_poc = $eng_poc ?: null;
    $eng_repeat = $eng_repeat ?: null;
    $eng_audit_type = $eng_audit_type ?: null;
    $eng_soc_type = $eng_soc_type ?: null;
    $eng_scope = $eng_scope ?: null;
    $eng_tsc = $eng_tsc ?: null;
    $eng_start_period = $eng_start_period ?: null;
    $eng_end_period = $eng_end_period ?: null;
    $eng_as_of_date = $eng_as_of_date ?: null;
    
    // Start transaction
    $conn->begin_transaction();

    // Insert into engagements table
    $query = "INSERT INTO engagements (
        eng_name, eng_location, eng_poc, eng_repeat, eng_audit_type, 
        eng_soc_type, eng_scope, eng_tsc, eng_start_period, eng_end_period, 
        eng_as_of_date, eng_status, eng_created, eng_updated
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    $stmt->bind_param(
        'ssssssssssss',
        $eng_name, $eng_location, $eng_poc, $eng_repeat, $eng_audit_type,
        $eng_soc_type, $eng_scope, $eng_tsc, $eng_start_period, $eng_end_period,
        $eng_as_of_date, $eng_status
    );

    if (!$stmt->execute()) {
        $conn->rollback();
        throw new Exception('Failed to insert engagement: ' . $stmt->error);
    }

    // Get the generated eng_id (auto-increment primary key)
    $engagement_id = $conn->insert_id;
    $stmt->close();

    if (!$engagement_id) {
        $conn->rollback();
        throw new Exception('Failed to get engagement ID');
    }

    // Get the actual eng_idno from the database (the formatted ID)
    $selectQuery = "SELECT eng_idno FROM engagements WHERE eng_id = ?";
    $stmt = $conn->prepare($selectQuery);
    
    if (!$stmt) {
        $conn->rollback();
        throw new Exception('Prepare failed on select: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $engagement_id);
    
    if (!$stmt->execute()) {
        $conn->rollback();
        throw new Exception('Failed to select engagement: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) {
        $conn->rollback();
        throw new Exception('Engagement not found after insert');
    }
    
    $eng_idno = $row['eng_idno'];

    // Automatically create timeline row for this engagement
    $timelineQuery = "INSERT INTO engagement_timeline (engagement_idno) VALUES (?)";
    $stmt = $conn->prepare($timelineQuery);
    
    if (!$stmt) {
        $conn->rollback();
        throw new Exception('Prepare failed on timeline: ' . $conn->error);
    }
    
    $stmt->bind_param('s', $eng_idno);

    if (!$stmt->execute()) {
        $conn->rollback();
        throw new Exception('Failed to create timeline: ' . $stmt->error);
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
    if ($conn->connect_errno === 0) {
        $conn->rollback();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>