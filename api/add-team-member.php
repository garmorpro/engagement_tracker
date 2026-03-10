<?php
require_once '../path.php';
require_once '../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagementIdno = $input['engagement_idno'] ?? null;
$empName        = $input['emp_name'] ?? null;
$role           = $input['role'] ?? null;

if (!$engagementIdno || !$empName || !$role) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: engagement_idno, emp_name, role']);
    exit;
}

try {
    $empSoc1Dol    = !empty($input['emp_soc1_dol'])    ? $input['emp_soc1_dol']    : null;
    $empSoc2Dol    = !empty($input['emp_soc2_dol'])    ? $input['emp_soc2_dol']    : null;
    $empHipaaDol   = !empty($input['emp_hipaa_dol'])   ? $input['emp_hipaa_dol']   : null;
    $empHitrustDol = !empty($input['emp_hitrust_dol']) ? $input['emp_hitrust_dol'] : null;
    $empFismaDol   = !empty($input['emp_fisma_dol'])   ? $input['emp_fisma_dol']   : null;

    $query = "INSERT INTO engagement_team 
                (engagement_idno, emp_name, role, emp_soc1_dol, emp_soc2_dol, emp_hipaa_dol, emp_hitrust_dol, emp_fisma_dol, emp_created, emp_updated)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    $stmt->bind_param('sssssssss',
        $engagementIdno,
        $empName,
        $role,
        $empSoc1Dol,
        $empSoc2Dol,
        $empHipaaDol,
        $empHitrustDol,
        $empFismaDol
    );

    if ($stmt->execute()) {
        $newEmpId = $conn->insert_id;

        $member = [
            'emp_id'          => $newEmpId,
            'engagement_idno' => $engagementIdno,
            'emp_name'        => $empName,
            'role'            => $role,
            'emp_soc1_dol'    => $empSoc1Dol,
            'emp_soc2_dol'    => $empSoc2Dol,
            'emp_hipaa_dol'   => $empHipaaDol,
            'emp_hitrust_dol' => $empHitrustDol,
            'emp_fisma_dol'   => $empFismaDol
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Team member added successfully',
            'member'  => $member
        ]);
    } else {
        throw new Exception($stmt->error);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error adding team member: ' . $e->getMessage()
    ]);
}
?>