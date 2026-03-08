<?php
require_once '../path.php';
require_once '../includes/functions.php';

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagementIdno = $input['engagement_idno'] ?? null;
$empId = $input['emp_id'] ?? null;
$empName = $input['emp_name'] ?? null;
$role = $input['role'] ?? null;

if (!$engagementIdno || !$empId || !$empName || !$role) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: engagement_idno, emp_id, emp_name, role']);
    exit;
}

try {
    // Get the DOL values from the input
    // Convert empty strings to NULL
    $empSoc1Dol = !empty($input['emp_soc1_dol']) ? $input['emp_soc1_dol'] : null;
    $empSoc2Dol = !empty($input['emp_soc2_dol']) ? $input['emp_soc2_dol'] : null;
    $empHipaaDol = !empty($input['emp_hipaa_dol']) ? $input['emp_hipaa_dol'] : null;
    $empHitrustDol = !empty($input['emp_hitrust_dol']) ? $input['emp_hitrust_dol'] : null;
    $empFismaDol = !empty($input['emp_fisma_dol']) ? $input['emp_fisma_dol'] : null;

    // Update the team member
    $query = "UPDATE engagement_team 
              SET emp_name = ?, 
                  role = ?, 
                  emp_soc1_dol = ?, 
                  emp_soc2_dol = ?, 
                  emp_hipaa_dol = ?, 
                  emp_hitrust_dol = ?, 
                  emp_fisma_dol = ?, 
                  emp_updated = NOW()
              WHERE emp_id = ? AND engagement_idno = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    // All parameters are strings (s) - NULL values are handled by MySQL
    $stmt->bind_param('sssssssss', 
        $empName, 
        $role, 
        $empSoc1Dol, 
        $empSoc2Dol, 
        $empHipaaDol, 
        $empHitrustDol, 
        $empFismaDol, 
        $empId, 
        $engagementIdno
    );
    
    if ($stmt->execute()) {
        $member = [
            'emp_id' => $empId,
            'engagement_idno' => $engagementIdno,
            'emp_name' => $empName,
            'role' => $role,
            'emp_soc1_dol' => $empSoc1Dol,
            'emp_soc2_dol' => $empSoc2Dol,
            'emp_hipaa_dol' => $empHipaaDol,
            'emp_hitrust_dol' => $empHitrustDol,
            'emp_fisma_dol' => $empFismaDol
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Team member updated successfully',
            'member' => $member
        ]);
    } else {
        throw new Exception($stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error updating team member: ' . $e->getMessage()
    ]);
}
?>