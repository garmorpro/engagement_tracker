<?php
require_once 'db.php';
session_start();

// get engagement details

// $engagements = getAllEngagements();

// Function to get all engagements
function getAllEngagements($conn) {
    $sql = "SELECT * FROM `engagements`";

    $result = $conn->query($sql);

    $engagements = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $engagements[] = $row;
        }
        $result->free();
    }

    return $engagements;
}


// automate eng_idno

$currentYear = date('Y');

// Get highest ENG-YYYY-XXX for this year
$stmt = $mysqli->prepare("
    SELECT eng_idno
    FROM engagements
    WHERE eng_idno LIKE CONCAT('ENG-', ?, '-%')
    ORDER BY eng_idno DESC
    LIMIT 1
");
$stmt->bind_param("s", $currentYear);
$stmt->execute();
$result = $stmt->get_result();

$nextNumber = 1;

if ($row = $result->fetch_assoc()) {
    // Extract the numeric part
    $parts = explode('-', $row['eng_idno']); // ENG-2026-003
    $lastNumber = (int)$parts[2];
    $nextNumber = $lastNumber + 1;
}

// Format ENG-2026-004
$nextEngId = sprintf(
    "ENG-%s-%03d",
    $currentYear,
    $nextNumber
);