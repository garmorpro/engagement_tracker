<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');

require 'db.php';

$eng_id = isset($_POST['eng_id']) ? intval($_POST['eng_id']) : 0;
$dol    = $_POST['dol'] ?? [];

if (!$eng_id || !is_array($dol)) {
    ob_clean();
    echo json_encode([
        'success' => false,
        'error'   => 'Invalid input'
    ]);
    exit;
}

foreach (['senior', 'staff'] as $role) {

    if (empty($dol[$role]) || !is_array($dol[$role])) {
        continue;
    }

    foreach ($dol[$role] as $idx => $auditData) {
        $index = intval($idx);
        if (!is_array($auditData)) continue;

        // Determine employee name by index (match existing or empty slot)
        $stmt = $conn->prepare("
            SELECT emp_id, emp_name
            FROM engagement_team
            WHERE eng_id = ?
              AND role = ?
            ORDER BY emp_id ASC
            LIMIT 1 OFFSET ?
        ");
        $roleName = ucfirst($role);
        $stmt->bind_param("isi", $eng_id, $roleName, $index);
        $stmt->execute();
        $result = $stmt->get_result();
        $emp = $result->fetch_assoc();
        $stmt->close();

        foreach ($auditData as $auditType => $dolValue) {
            $auditType = trim($auditType); // "SOC 1" / "SOC 2"
            $dolValue  = trim((string)$dolValue);
            $dolValue = ($dolValue === '') ? null : $dolValue;

            if ($emp && !empty($emp['emp_name'])) {
                // UPDATE existing row
                $update = $conn->prepare("
                    UPDATE engagement_team
                    SET emp_dol = ?
                    WHERE eng_id = ?
                      AND emp_name = ?
                      AND role = ?
                      AND audit_type LIKE CONCAT(?, '%')
                    LIMIT 1
                ");
                $update->bind_param(
                    "sisss",
                    $dolValue,
                    $eng_id,
                    $emp['emp_name'],
                    $roleName,
                    $auditType
                );
                $update->execute();
                $update->close();
            } else {
                // INSERT new row if name exists in form
                $empName = $_POST['dol_name'][$role][$idx] ?? '';
                $empName = trim($empName);
                if (!$empName) continue;

                $insert = $conn->prepare("
                    INSERT INTO engagement_team
                        (eng_id, emp_name, role, audit_type, emp_dol)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $insert->bind_param(
                    "issss",
                    $eng_id,
                    $empName,
                    $roleName,
                    $auditType,
                    $dolValue
                );
                $insert->execute();
                $insert->close();
            }
        }
    }
}

ob_clean();
echo json_encode(['success' => true]);
exit;
