<?php
require 'db.php'; // your DB connection

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 3) {
    echo json_encode([]);
    exit;
}

// Search engagements that don't have an archive date in milestones
$stmt = $conn->prepare("
    SELECT e.eng_id, e.eng_idno, e.eng_name
    FROM engagements e
    INNER JOIN engagement_milestones m ON e.eng_id = m.eng_id
    WHERE m.archive_date IS NULL
      AND (e.eng_name LIKE ? OR e.eng_idno LIKE ?)
    GROUP BY e.eng_id
    LIMIT 20
");

$searchTerm = "%$q%";
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$res = $stmt->get_result();

$results = [];
while ($row = $res->fetch_assoc()) {
    $results[] = $row;
}

echo json_encode($results);
