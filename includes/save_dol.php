<?php
ob_start();
header('Content-Type: application/json');
require 'db.php';

$eng_id = isset($_POST['eng_id']) ? intval($_POST['eng_id']) : 0;
$dol    = $_POST['dol'] ?? [];

if (!$eng_id || !is_array($dol)) {
    echo json_encode(['success'=>false,'error'=>'Invalid input']);
    exit;
}

foreach ($dol as $emp_id => $audits) {
    $emp_id = intval($emp_id);
    if (!is_array($audits)) continue;

    // Get role and name for this employee
    $stmt = $conn->prepare("SELECT emp_name, role FROM engagement_team WHERE emp_id = ? AND eng_id = ?");
    $stmt->bind_param("ii", $emp_id, $eng_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $emp = $res->fetch_assoc();
    $stmt->close();

    if (!$emp) continue;
    $roleName = $emp['role'];
    $empName  = $emp['emp_name'];

    foreach ($audits as $auditType => $dolValue) {
        $auditType = trim($auditType);
        $dolValue  = trim((string)$dolValue);
        $dolValue  = ($dolValue === '') ? null : $dolValue;

        // Update existing row
        $update = $conn->prepare("
            UPDATE engagement_team
            SET emp_dol = ?
            WHERE eng_id = ?
              AND emp_name = ?
              AND role = ?
              AND audit_type LIKE CONCAT(?, '%')
            LIMIT 1
        ");
        $update->bind_param("sisss", $dolValue, $eng_id, $empName, $roleName, $auditType);
        $update->execute();
        $update->close();
    }
}

ob_clean();
echo json_encode(['success'=>true]);
exit;
