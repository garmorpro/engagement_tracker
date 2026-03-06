<?php
$basePath = dirname(dirname(__FILE__));
require_once $basePath . '/path.php';

echo "=== ENGAGEMENT_TIMELINE TABLE STRUCTURE ===\n\n";

$query = "DESCRIBE engagement_timeline";
$result = $conn->query($query);

if (!$result) {
    echo "❌ Table does not exist\n";
    exit(1);
}

while ($row = $result->fetch_assoc()) {
    echo "Field: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Key: {$row['Key']}\n";
}

echo "\n=== ALL COLUMNS ===\n";
$columns = [];
$result = $conn->query("DESCRIBE engagement_timeline");
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo implode(", ", $columns);
echo "\n\n";

echo "=== SAMPLE DATA ===\n";
$query = "SELECT * FROM engagement_timeline LIMIT 5";
$result = $conn->query($query);
if ($result->num_rows === 0) {
    echo "No data in table\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "Row: " . json_encode($row) . "\n";
    }
}

echo "\n=== ENGAGEMENT_MILESTONES TABLE STRUCTURE ===\n\n";

$query = "DESCRIBE engagement_milestones";
$result = $conn->query($query);

if (!$result) {
    echo "❌ Table does not exist\n";
    exit(1);
}

while ($row = $result->fetch_assoc()) {
    echo "Field: {$row['Field']}, Type: {$row['Type']}, Null: {$row['Null']}, Key: {$row['Key']}\n";
}

echo "\n=== ALL COLUMNS ===\n";
$columns = [];
$result = $conn->query("DESCRIBE engagement_milestones");
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo implode(", ", $columns);
echo "\n\n";

echo "=== SAMPLE DATA ===\n";
$query = "SELECT * FROM engagement_milestones LIMIT 5";
$result = $conn->query($query);
if ($result->num_rows === 0) {
    echo "No data in table\n";
} else {
    while ($row = $result->fetch_assoc()) {
        echo "Row: " . json_encode($row) . "\n";
    }
}
?>