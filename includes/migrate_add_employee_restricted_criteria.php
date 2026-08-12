<?php
/**
 * One-time migration: add emp_restricted_criteria to the employees roster
 * — a comma-separated list of DOL criteria names (e.g. "CC6,CC9,Privacy")
 * this person hasn't completed training on yet, so the DOL Generator never
 * assigns them those items until the restriction is cleared.
 *
 * Lives on employees (matched by name), not engagement_team — a
 * restriction follows the person across every engagement they're staffed
 * on, and engagement_team rows don't link back to the roster (see
 * CLAUDE.md: emp_id there is just that row's own auto-increment key, not a
 * foreign key into employees).
 *
 * Safe to run more than once — skips if the column already exists. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_employee_restricted_criteria.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$check = $conn->query("SHOW COLUMNS FROM `employees` LIKE 'emp_restricted_criteria'");
if ($check && $check->num_rows > 0) {
    echo "Column emp_restricted_criteria already exists, skipping.\n";
    exit(0);
}

if (!$conn->query("ALTER TABLE `employees` ADD COLUMN `emp_restricted_criteria` VARCHAR(500) NULL")) {
    fwrite(STDERR, "Failed to add column emp_restricted_criteria: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "Added column emp_restricted_criteria.\n";
echo "Done.\n";
