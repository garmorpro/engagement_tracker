<?php
// api/get-calendar-items.php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$year = (int) ($_GET['year'] ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));

if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid year/month']);
    exit;
}

try {
    $items = getCalendarItemsForMonth($conn, $year, $month);
    echo json_encode(['success' => true, 'year' => $year, 'month' => $month, 'items' => $items]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
