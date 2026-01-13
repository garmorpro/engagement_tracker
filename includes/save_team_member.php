<?php
// save_team_member.php
include 'db.php'; // your DB connection

$eng_id = $_POST['eng_id'];
$type = $_POST['type']; // 'senior' or 'staff'
$name = $_POST['name'];
$index = (int)$_POST['index'];

// Make column dynamic
$column = $type . $index;

$sql = "UPDATE engagements SET `$column` = ? WHERE eng_idno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $name, $eng_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
