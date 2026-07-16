<?php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

$engagementId = trim($_GET['id'] ?? '');

if ($engagementId === '') {
    http_response_code(400);
    exit('Missing engagement id');
}

$stmt = $conn->prepare("SELECT eng_planning_doc FROM engagements WHERE eng_idno = ?");
$stmt->bind_param('s', $engagementId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || empty($row['eng_planning_doc'])) {
    http_response_code(404);
    exit('No screenshot on file for this engagement');
}

// basename() strips any path component, so this can only ever resolve to a
// file directly inside uploads/engagement-screenshots/ — no traversal.
$filename = basename($row['eng_planning_doc']);
$path = dirname(__DIR__) . '/uploads/engagement-screenshots/' . $filename;

if (!is_file($path)) {
    http_response_code(404);
    exit('Screenshot file is missing');
}

$mime = @getimagesize($path)['mime'] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, no-cache');
readfile($path);
