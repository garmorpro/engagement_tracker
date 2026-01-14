<?php
header('Content-Type: application/json');
include 'db.php';

$eng_id = $_POST['eng_id'] ?? '';
$type   = $_POST['type'] ?? ''; // "manager", "senior" or "staff"
$name   = trim($_POST['name'] ?? '');
$auditTypes = $_POST['audit_types'] ?? []; // expect array of "SOC 1" and/or "SOC 2"

// Validate input
if (!in_array($type, ['manager','senior','staff']) || $eng_id === '' || $name === '' || !is_array($auditTypes)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

// Map type to role
$role = ucfirst($type); // "Manager", "Senior", "Staff"

// Check if manager already exists (only for manager type)
if ($role === 'Manager') {
    $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM engagement_team WHERE eng_id = ? AND role = 'Manager'");
    $stmt_check->bind_param("i", $eng_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if ($result_check['count'] > 0) {
        echo json_encode(['success' => false, 'error' => 'Manager already assigned for this engagement']);
        exit;
    }
}

// Prepare insert statement
$stmt = $conn->prepare("
    INSERT INTO engagement_team (eng_id, emp_name, role, audit_type)
    VALUES (?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

$allSuccess = true;

if ($role === 'Manager') {
    // Insert only once for manager
    $stmt->bind_param("isss", $eng_id, $name, $role, $auditTypes[0] ?? ''); // optional: store first SOC type or leave blank
    $success = $stmt->execute();
    if (!$success) $allSuccess = false;
} else {
    // Insert one row per audit type for Senior/Staff
    foreach ($auditTypes as $audit) {
        $audit = trim($audit);
        if (!in_array($audit, ['SOC 1', 'SOC 2'])) continue;

        $stmt->bind_param("isss", $eng_id, $name, $role, $audit);
        $success = $stmt->execute();
        if (!$success) $allSuccess = false;
    }
}

$stmt->close();

echo json_encode(['success' => $allSuccess, 'error' => $allSuccess ? '' : $conn->error]);
exit;
