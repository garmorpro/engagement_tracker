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

function getNextEngagementId($conn): string
{
    $currentYear = date('Y');

    $sql = "
        SELECT eng_idno
        FROM engagements
        WHERE eng_idno LIKE CONCAT('ENG-', ?, '-%')
        ORDER BY eng_idno DESC
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $currentYear);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $nextNumber = 1;

    if ($row = mysqli_fetch_assoc($result)) {
        $parts = explode('-', $row['eng_idno']);
        $nextNumber = ((int)$parts[2]) + 1;
    }

    mysqli_stmt_close($stmt);

    return sprintf('ENG-%s-%03d', $currentYear, $nextNumber);
}