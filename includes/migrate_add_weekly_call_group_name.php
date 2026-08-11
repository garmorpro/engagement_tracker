<?php
/**
 * One-time migration: add weekly_status_call_group_name to
 * engagement_timeline — a human-editable label for a linked weekly status
 * call group (e.g. "LP Status Call"), separate from
 * weekly_status_call_group (the internal join key, which stays stable and
 * is never shown to the user). Lets the calendar show a short, chosen name
 * for a shared call instead of concatenating every linked engagement's
 * full name into one chip.
 *
 * Safe to run more than once — skips if the column already exists. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_weekly_call_group_name.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$check = $conn->query("SHOW COLUMNS FROM `engagement_timeline` LIKE 'weekly_status_call_group_name'");
if ($check && $check->num_rows > 0) {
    echo "Column weekly_status_call_group_name already exists, skipping.\n";
    exit(0);
}

if (!$conn->query("ALTER TABLE `engagement_timeline` ADD COLUMN `weekly_status_call_group_name` VARCHAR(100) NULL")) {
    fwrite(STDERR, "Failed to add column weekly_status_call_group_name: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "Added column weekly_status_call_group_name.\n";
echo "Done.\n";
