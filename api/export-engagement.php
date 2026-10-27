<?php
// Archive & export, step 1: builds the engagement's history folder (PDF
// synopsis + planning doc) as a ZIP and streams it to the browser. Read-only —
// nothing is archived or removed here; the page asks before calling
// delete-engagement.php, so a failed download can never cost the data.
require_once '../path.php';
require_once '../includes/functions.php';
require_once '../includes/engagement_export.php';
requireApiAuth();

$data = json_decode(file_get_contents('php://input'), true);
$engagementId = trim((string) ($data['engagement_id'] ?? ''));

if ($engagementId === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing engagement ID']);
    exit;
}

$tmp = tempnam(sys_get_temp_dir(), 'engexp_');

try {
    $filename = exportBuildZip($conn, $engagementId, (string) ($_SESSION['name'] ?? ''), $tmp);
} catch (Throwable $e) {
    @unlink($tmp);
    error_log('export-engagement.php: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Could not build the export: ' . $e->getMessage()]);
    exit;
}

logActivity($conn, 'engagement_exported', 'engagement', $engagementId, "Exported {$engagementId} as a PDF synopsis folder");

// ASCII fallback plus the real (possibly non-ASCII) name for browsers that read filename*.
$ascii = preg_replace('/[^\x20-\x7e]/', '_', $filename);
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $ascii) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
header('X-Export-Filename: ' . rawurlencode($filename));
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store');
readfile($tmp);
unlink($tmp);
exit;
