<?php
// api/get-due-items.php
// Live snapshot for the login "what's due" popup — see getDueItemsSummary()
// in includes/functions.php for why this doesn't read engagement_notifications.
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

try {
    $daysAheadSetting = getAppSetting($conn, 'due_popup_days_ahead');
    $daysAhead = ($daysAheadSetting !== null && ctype_digit($daysAheadSetting)) ? (int) $daysAheadSetting : 7;

    $summary = getDueItemsSummary($conn, $daysAhead);
    echo json_encode([
        'success' => true,
        'days_ahead' => $daysAhead,
        'overdue' => $summary['overdue'],
        'upcoming' => $summary['upcoming'],
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
