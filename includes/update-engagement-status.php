<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['eng_id'], $input['status'])) {
  echo json_encode(['success' => false, 'message' => 'Invalid input']);
  exit;
}

include 'db.php';

$stmt = $conn->prepare(
  "UPDATE engagements SET eng_status = ? WHERE eng_id = ?"
);
$stmt->bind_param("ss", $input['status'], $input['eng_id']);

if ($stmt->execute()) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false]);
}