<?php
$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';

echo "=== ENGAGEMENTS TABLE STRUCTURE ===\n\n";

$query = "DESCRIBE engagements";
$result = $conn->query($query);

while ($row = $result->fetch_assoc()) {
    echo "Field: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Key: {$row['Key']}\n";
}

echo "\n=== ALL COLUMNS ===\n";
$columns = [];
$result = $conn->query("DESCRIBE engagements");
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo implode(", ", $columns);
echo "\n";
?>