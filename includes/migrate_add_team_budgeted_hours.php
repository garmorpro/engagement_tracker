<?php
/**
 * One-time migration: add budgeted_hours to engagement_team — the hours a
 * team member is budgeted for on a given engagement, captured on the
 * Create Engagement wizard's Team step (and editable afterward via the
 * "Edit Team Member" modal). Purely informational for now: displayed on
 * the read-only Team card, not consumed by any calculation (the DOL
 * Generator's "Team Hours" step is separate and still asks for hours
 * fresh each time, on purpose — see its own field hint).
 *
 * NULL = never set. DECIMAL(6,2) to allow half-hour increments like the
 * DOL Generator's hours input already does.
 *
 * Safe to run more than once — skips if the column already exists. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_team_budgeted_hours.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$check = $conn->query("SHOW COLUMNS FROM `engagement_team` LIKE 'budgeted_hours'");
if ($check && $check->num_rows > 0) {
    echo "Column budgeted_hours already exists, skipping.\n";
    exit(0);
}

if (!$conn->query("ALTER TABLE `engagement_team` ADD COLUMN `budgeted_hours` DECIMAL(6,2) NULL")) {
    fwrite(STDERR, "Failed to add column budgeted_hours: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "Added column budgeted_hours.\n";
echo "Done.\n";
