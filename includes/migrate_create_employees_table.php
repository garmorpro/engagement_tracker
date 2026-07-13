<?php
/**
 * One-time migration: create the employees table (name + role roster used
 * for the team-member autocomplete) and seed it from the distinct names
 * already used across engagement_team, so the roster doesn't start empty.
 *
 * Safe to run more than once — uses CREATE TABLE IF NOT EXISTS and skips
 * names that already exist in employees. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_create_employees_table.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$sql = "
    CREATE TABLE IF NOT EXISTS employees (
        emp_id INT AUTO_INCREMENT PRIMARY KEY,
        emp_name VARCHAR(255) NOT NULL,
        emp_role VARCHAR(20) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_emp_name (emp_name)
    )
";
if (!$conn->query($sql)) {
    fwrite(STDERR, "Failed to create employees table: " . $conn->error . PHP_EOL);
    exit(1);
}
echo "employees table ready.\n";

// Seed from engagement_team: one row per distinct name, using the role
// from that person's most recent assignment.
$result = $conn->query("
    SELECT et.emp_name, et.role
    FROM engagement_team et
    INNER JOIN (
        SELECT emp_name, MAX(emp_id) AS latest_id
        FROM engagement_team
        WHERE emp_name IS NOT NULL AND TRIM(emp_name) != '' AND role IS NOT NULL
        GROUP BY emp_name
    ) latest ON latest.emp_name = et.emp_name AND latest.latest_id = et.emp_id
");
if (!$result) {
    fwrite(STDERR, "Failed to read engagement_team for seeding: " . $conn->error . PHP_EOL);
    exit(1);
}

$inserted = 0;
$skipped = 0;
$validRoles = ['manager', 'senior', 'staff', 'intern'];

while ($row = $result->fetch_assoc()) {
    $name = trim($row['emp_name']);
    $role = strtolower(trim($row['role']));
    if ($name === '' || !in_array($role, $validRoles, true)) {
        $skipped++;
        continue;
    }

    $stmt = $conn->prepare("INSERT IGNORE INTO employees (emp_name, emp_role) VALUES (?, ?)");
    $stmt->bind_param('ss', $name, $role);
    if (!$stmt->execute()) {
        fwrite(STDERR, "Failed to insert {$name}: " . $stmt->error . PHP_EOL);
        $stmt->close();
        continue;
    }
    if ($stmt->affected_rows > 0) $inserted++; else $skipped++;
    $stmt->close();
}

echo "Done. Seeded {$inserted} employee(s), skipped {$skipped} (already present or invalid role).\n";
