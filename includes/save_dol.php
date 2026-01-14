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
    if (!is_numeric($emp_id)) continue;
    $emp_id_int = intval($emp_id);

    if (!is_array($audits)) continue;

    // Get employee info from DB
    $stmt = $conn->prepare("SELECT emp_name, role FROM engagement_team WHERE emp_id = ? AND eng_id = ?");
    $stmt->bind_param("ii", $emp_id_int, $eng_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $emp = $res->fetch_assoc();
    $stmt->close();

    if (!$emp) continue; // skip if not found
    $empName  = $emp['emp_name'];
    $roleName = $emp['role'];

    foreach ($audits as $auditType => $dolValue) {
        $auditType = trim($auditType);
        $dolValue  = trim((string)$dolValue);
        $dolValue  = ($dolValue === '') ? null : $dolValue;

        // ✅ Update row for the specific employee + audit type
        $update = $conn->prepare("
            UPDATE engagement_team
            SET emp_dol = ?
            WHERE eng_id = ? AND emp_name = ? AND audit_type = ?
        ");
        $update->bind_param("siss", $dolValue, $eng_id, $empName, $auditType);
        $update->execute();
        $update->close();
    }
}

ob_clean();
echo json_encode(['success' => true]);
exit;
