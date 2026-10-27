<?php
// Clean any output buffer to ensure clean JSON response. Guarded: with no
// buffer active (output_buffering=0, as in the Docker image) a bare
// ob_clean() raises a Notice, and with display_errors on that notice is
// sent before session_start(), which then fails — every request came back
// "Not authenticated" even when logged in.
if (ob_get_level() > 0) {
    ob_clean();
}

require_once '../path.php';
require_once '../includes/functions.php';
require_once '../includes/engagement_export.php';
requireApiAuth();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$engagement_id = $data['engagement_id'] ?? null;

if (!$engagement_id) {
    echo json_encode(['success' => false, 'message' => 'Missing engagement ID']);
    exit;
}

try {
    // Removes the timeline, milestones, team, notes log and planning doc file
    // together (see exportRemoveEngagement() for why the notes and file matter).
    $name = exportRemoveEngagement($conn, (string) $engagement_id);
    logActivity($conn, 'engagement_deleted', 'engagement', (string) $engagement_id, "Removed {$engagement_id} ({$name}) from the tracker");

    echo json_encode([
        'success' => true,
        'message' => 'Engagement deleted successfully'
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
