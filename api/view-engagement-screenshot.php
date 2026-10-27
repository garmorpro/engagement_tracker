<?php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

$engagementId = trim($_GET['id'] ?? '');
$requested = basename(trim($_GET['file'] ?? ''));

if ($engagementId === '') {
    http_response_code(400);
    exit('Missing engagement id');
}

$stmt = $conn->prepare("SELECT eng_planning_doc FROM engagements WHERE eng_idno = ?");
$stmt->bind_param('s', $engagementId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$files = $row ? planningDocList($row['eng_planning_doc']) : [];
if (!$files) {
    http_response_code(404);
    exit('No planning doc on file for this engagement');
}

// Only a file that is actually in this engagement's list can be served (and
// basename() on each entry keeps it directly inside the uploads folder), so
// ?file= can't be used to read anything else. No ?file= means the first doc.
$filename = $requested === '' ? $files[0] : $requested;
if (!in_array($filename, $files, true)) {
    http_response_code(404);
    exit('That planning doc does not belong to this engagement');
}
$path = planningDocDir() . '/' . $filename;

if (!is_file($path)) {
    http_response_code(404);
    exit('Planning doc file is missing');
}

$mime = @getimagesize($path)['mime'] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Cache-Control: private, max-age=0, no-cache');
readfile($path);
