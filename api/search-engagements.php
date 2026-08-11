<?php
// api/search-engagements.php
// Used by the "link engagement" picker for the weekly status call — active
// engagements only, excluding whichever one you're already linking from.
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
$exclude = trim($_GET['exclude'] ?? '');

if ($q === '') {
    echo json_encode(['success' => true, 'engagements' => []]);
    exit;
}

try {
    $like = '%' . $q . '%';
    $stmt = $conn->prepare("SELECT eng_idno, eng_name
                             FROM engagements
                             WHERE eng_status NOT IN ('archived', 'complete')
                             AND eng_idno != ?
                             AND (eng_name LIKE ? OR eng_idno LIKE ?)
                             ORDER BY eng_name ASC LIMIT 10");
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    $stmt->bind_param('sss', $exclude, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $engagements = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'engagements' => $engagements]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
