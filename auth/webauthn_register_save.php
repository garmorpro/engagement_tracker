<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/init.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

if (empty($input['rawId']) || empty($input['attestationObject'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing credential data']);
    exit;
}

try {
    $rawId = $input['rawId'];
    $attestationObject = $input['attestationObject'];

    // ⚠️ TEMP: store raw credential without full cryptographic verification
    // This is OK for initial testing
    $stmt = $conn->prepare("
        INSERT INTO webauthn_credentials 
        (credential_id, public_key)
        VALUES (?, ?)
    ");

    $stmt->bind_param('ss', $rawId, $attestationObject);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'Credential stored'
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
