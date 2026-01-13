<?php
include 'db.php'; // your DB connection

$eng_id = $_POST['eng_id'];
$type = $_POST['type']; // 'senior' or 'staff'
$name = $_POST['name'];
$index = (int)$_POST['index'];

// Determine column
$column = $type . $index; // e.g., senior1 or staff2
$sql = "UPDATE engagements SET `$column` = ? WHERE eng_idno = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("si", $name, $eng_id);
$success = $stmt->execute();

echo json_encode(['success' => $success]);
