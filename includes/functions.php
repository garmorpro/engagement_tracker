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

function getNextEngagementId(mysqli $mysqli): string
{
    $currentYear = date('Y');

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
        $parts = explode('-', $row['eng_idno']);
        $nextNumber = ((int)$parts[2]) + 1;
    }

    return sprintf("ENG-%s-%03d", $currentYear, $nextNumber);
}