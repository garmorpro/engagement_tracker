<?php
ob_start();
header('Content-Type: application/json');
require 'db.php';

$eng_id = isset($_POST['eng_id']) ? intval($_POST['eng_id']) : 0;
$dol    = $_POST['dol'] ?? [];

if (!$eng_id || !is_array($dol)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

foreach ($dol as $emp_id => $audits) {
    // For new "temporary" IDs, skip insert/update
    if (!is_numeric($emp_id)) continue;

    $emp_id = intval($emp_id);
    if (!is_array($audits)) continue;

    // Get employee info from engagement_team
    $stmt = $conn->prepare("SELECT emp_name, role FROM engagement_team WHERE emp_id = ? AND eng_id = ?");
    $stmt->bind_param("ii", $emp_id, $eng_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $emp = $res->fetch_assoc();
    $stmt->close();

    if (!$emp) continue; // skip if employee not found
    $empName  = $emp['emp_name'];
    $roleName = $emp['role'];

    foreach ($audits as $auditType => $dolValue) {
        $auditType = trim($auditType);
        $dolValue  = trim((string)$dolValue);
        $dolValue  = ($dolValue === '') ? null : $dolValue;

        // Check if a row already exists for this employee + audit_type
        $check = $conn->prepare("
            SELECT emp_id 
            FROM engagement_team 
            WHERE eng_id = ? AND emp_name = ? AND role = ? AND audit_type LIKE CONCAT(?, '%')
            LIMIT 1
        ");
        $check->bind_param("isss", $eng_id, $empName, $roleName, $auditType);
        $check->execute();
        $result = $check->get_result();
        $existing = $result->fetch_assoc();
        $check->close();

        if ($existing) {
            // Update existing row
            $update = $conn->prepare("
                UPDATE engagement_team
                SET emp_dol = ?
                WHERE eng_id = ? AND emp_name = ? AND role = ? AND audit_type LIKE CONCAT(?, '%')
                LIMIT 1
            ");
            $update->bind_param("sisss", $dolValue, $eng_id, $empName, $roleName, $auditType);
            $update->execute();
            $update->close();
        } else {
            // Insert new row if missing
            $insert = $conn->prepare("
                INSERT INTO engagement_team (eng_id, emp_name, role, audit_type, emp_dol)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->bind_param("issss", $eng_id, $empName, $roleName, $auditType, $dolValue);
            $insert->execute();
            $insert->close();
        }
    }
}

ob_clean();
echo json_encode(['success' => true]);
exit;
