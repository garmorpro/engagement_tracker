<?php
header('Content-Type: application/json');
include 'db.php';

$eng_id = $_POST['eng_id'] ?? '';
$type   = $_POST['type'] ?? ''; // "manager", "senior" or "staff"
$name   = trim($_POST['name'] ?? '');
$audit_types = $_POST['audit_types'] ?? []; // optional: array of SOCs

// Validate input
if (!in_array($type, ['manager','senior','staff']) || $eng_id === '' || $name === '') {
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

// Determine audit types for this member
// For manager: always just one entry
if ($role === 'Manager') {
    $audits_to_insert = ['']; // empty string or NULL if you don't store audit type
} else {
    // If audit_types provided (from JS), use them; otherwise default to SOC 1/SOC 2 for this engagement
    if (!empty($audit_types) && is_array($audit_types)) {
        $audits_to_insert = $audit_types;
    } else {
        // fetch from engagement table
        $stmt_eng = $conn->prepare("SELECT eng_audit_type FROM engagements WHERE eng_id = ?");
        $stmt_eng->bind_param("i", $eng_id);
        $stmt_eng->execute();
        $eng_result = $stmt_eng->get_result()->fetch_assoc();
        $stmt_eng->close();

        $eng_audits = explode(',', $eng_result['eng_audit_type'] ?? '');
        $audits_to_insert = [];
        foreach($eng_audits as $audit) {
            $audit = trim($audit);
            if (stripos($audit, 'SOC 1') !== false) $audits_to_insert[] = 'SOC 1';
            elseif (stripos($audit, 'SOC 2') !== false) $audits_to_insert[] = 'SOC 2';
        }
        $audits_to_insert = array_unique($audits_to_insert);
    }
}

// Insert one row per audit type (except manager: only one)
$success = true;
$last_id = null;

$stmt = $conn->prepare("INSERT INTO engagement_team (eng_id, emp_name, role, audit_type) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => $conn->error]);
    exit;
}

foreach ($audits_to_insert as $audit) {
    $audit_val = $role === 'Manager' ? null : $audit;
    $stmt->bind_param("isss", $eng_id, $name, $role, $audit_val);
    if (!$stmt->execute()) {
        $success = false;
        break;
    }
    $last_id = $stmt->insert_id;
}

$stmt->close();

echo json_encode(['success' => $success, 'emp_id' => $last_id, 'error' => $stmt->error ?? '']);
exit;
