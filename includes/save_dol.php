<?php
include '../db.php';

header('Content-Type: application/json');

$eng_id = intval($_POST['eng_id'] ?? 0);
$dol    = $_POST['dol'] ?? [];

if (!$eng_id || empty($dol)) {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

/*
Expected POST structure:

dol[senior][0][SOC 1] = CO1
dol[senior][0][SOC 2] = CC1
dol[staff][1][SOC 1]  = CO2
*/

foreach (['senior', 'staff'] as $role) {

    if (empty($dol[$role])) continue;

    foreach ($dol[$role] as $idx => $auditData) {

        // Get employee name by index (1-based in UI)
        $index = intval($idx) + 1;

        $nameStmt = $conn->prepare("
            SELECT emp_name 
            FROM engagement_team 
            WHERE eng_id = ? 
              AND role = ? 
            ORDER BY emp_id ASC 
            LIMIT 1 OFFSET ?
        ");

        $roleName = ucfirst($role);
        $offset   = $index - 1;

        $nameStmt->bind_param("isi", $eng_id, $roleName, $offset);
        $nameStmt->execute();
        $nameResult = $nameStmt->get_result();
        $empRow = $nameResult->fetch_assoc();

        if (!$empRow || empty($empRow['emp_name'])) continue;

        $emp_name = $empRow['emp_name'];

        // Save each audit type separately
        foreach ($auditData as $auditType => $dolValue) {

            $auditType = trim($auditType); // "SOC 1" / "SOC 2"
            $dolValue  = trim($dolValue);

            if ($dolValue === '') continue;

            $stmt = $conn->prepare("
                UPDATE engagement_team
                SET emp_dol = ?
                WHERE eng_id = ?
                  AND emp_name = ?
                  AND role = ?
                  AND audit_type LIKE CONCAT(?, '%')
                LIMIT 1
            ");

            $stmt->bind_param(
                "sisss",
                $dolValue,
                $eng_id,
                $emp_name,
                $roleName,
                $auditType
            );

            $stmt->execute();
        }
    }
}

echo json_encode(['success' => true]);
