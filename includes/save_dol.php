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
    if (!is_array($audits)) continue;

    // Determine if this is an existing employee (numeric emp_id) or new member
    $isNewMember = false;
    if (is_numeric($emp_id)) {
        $emp_id_int = intval($emp_id);
        $stmt = $conn->prepare("SELECT emp_name, role FROM engagement_team WHERE emp_id = ? AND eng_id = ?");
        $stmt->bind_param("ii", $emp_id_int, $eng_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $emp = $res->fetch_assoc();
        $stmt->close();

        if (!$emp) continue; // skip if employee not found
        $empName  = $emp['emp_name'];
        $roleName = $emp['role'];
    } else {
        // new member (key like new_1)
        $isNewMember = true;

        // Expect audit form to have hidden inputs or manual entry for name + role
        // For simplicity, we try to get from POST: dol_names and dol_roles arrays
        $empName  = $_POST['dol_names'][$emp_id] ?? 'Unknown';
        $roleName = $_POST['dol_roles'][$emp_id] ?? 'staff'; // default to staff
        $emp_id_int = null; // leave null for new insert
    }

    foreach ($audits as $auditType => $dolValue) {
        $auditType = trim($auditType);
        $dolValue  = trim((string)$dolValue);
        $dolValue  = ($dolValue === '') ? null : $dolValue;

        if (!$isNewMember) {
            // Update by emp_id first
            $update = $conn->prepare("
                UPDATE engagement_team
                SET emp_dol = ?
                WHERE eng_id = ? AND emp_id = ? AND audit_type LIKE CONCAT(?, '%')
            ");
            $update->bind_param("siis", $dolValue, $eng_id, $emp_id_int, $auditType);
            $update->execute();
            $affected = $update->affected_rows;
            $update->close();

            // If no row was updated, insert new
            if ($affected === 0) {
                $insert = $conn->prepare("
                    INSERT INTO engagement_team (eng_id, emp_id, emp_name, role, audit_type, emp_dol)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $insert->bind_param("iissss", $eng_id, $emp_id_int, $empName, $roleName, $auditType, $dolValue);
                $insert->execute();
                $insert->close();
            }
        } else {
            // New member insert
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
