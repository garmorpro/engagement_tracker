<?php
error_reporting(E_ERROR);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) exit(json_encode(['success'=>false,'error'=>'No input']));

// TODO: verify signature / challenge properly here
// For demo, mark as success
echo json_encode(['success'=>true]);
exit;