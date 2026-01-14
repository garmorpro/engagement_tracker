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
    // Only update numeric emp_id (existing team members)
    if (!is_numeric($emp_id)) continue;
    $emp_id_int = intval($emp_id);

    if (!is_array($audits)) continue;

    // Get employee info from DB to confirm they exist
    $stmt = $conn->prepare("SELECT emp_name, role FROM engagement_team WHERE emp_id = ? AND eng_id = ?");
    $stmt->bind_param("ii", $emp_id_int, $eng_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $emp = $res->fetch_assoc();
    $stmt->close();

    if (!$emp) continue; // skip if employee not found
    $empName  = $emp['emp_name'];
    $roleName = $emp['role'];

    // Update or insert DOL for each audit type
    foreach ($audits as $auditType => $dolValue) {
        $auditType = trim($auditType);
        $dolValue  = trim((string)$dolValue);
        $dolValue  = ($dolValue === '') ? null : $dolValue;

        // Check if this audit_type already exists for this emp
        $check = $conn->prepare("
            SELECT emp_id 
            FROM engagement_team 
            WHERE eng_id = ? AND emp_id = ? AND audit_type LIKE CONCAT(?, '%')
            LIMIT 1
        ");
        $check->bind_param("iis", $eng_id, $emp_id_int, $auditType);
        $check->execute();
        $result = $check->get_result();
        $existing = $result->fetch_assoc();
        $check->close();

        if ($existing) {
            // Update existing DOL
            $update = $conn->prepare("
                UPDATE engagement_team
                SET emp_dol = ?
                WHERE eng_id = ? AND emp_id = ? AND audit_type LIKE CONCAT(?, '%')
            ");
            $update->bind_param("siis", $dolValue, $eng_id, $emp_id_int, $auditType);
            $update->execute();
            $update->close();
        } else {
            // Insert new audit_type row for existing team member
            $insert = $conn->prepare("
                INSERT INTO engagement_team (eng_id, emp_id, emp_name, role, audit_type, emp_dol)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $insert->bind_param("iissss", $eng_id, $emp_id_int, $empName, $roleName, $auditType, $dolValue);
            $insert->execute();
            $insert->close();
        }
    }
}

ob_clean();
echo json_encode(['success' => true]);
exit;
