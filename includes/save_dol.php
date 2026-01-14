<?php
// CRITICAL: no whitespace before this file
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

        /* ================================
           FETCH EMPLOYEE BY ROLE + ORDER
        ================================= */
        $stmt = $conn->prepare("
            SELECT emp_name
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

        if (!$emp || empty($emp['emp_name'])) {
            continue;
        }

        $emp_name = $emp['emp_name'];

        /* ================================
           UPDATE EACH AUDIT TYPE DOL
        ================================= */
        foreach ($auditData as $auditType => $dolValue) {

            $auditType = trim($auditType);     // "SOC 1" / "SOC 2"
            $dolValue  = trim((string)$dolValue);

            // Convert empty string → NULL
            $dolValue = ($dolValue === '') ? null : $dolValue;

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
                $emp_name,
                $roleName,
                $auditType
            );

            $update->execute();
            $update->close();
        }
    }
}

ob_clean();
echo json_encode(['success' => true]);
exit;
