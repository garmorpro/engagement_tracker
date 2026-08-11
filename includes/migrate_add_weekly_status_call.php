<?php
/**
 * One-time migration: add weekly_status_call_day to engagement_timeline —
 * a day-of-week index (0 = Sunday .. 6 = Saturday, matching JS's
 * Date.getDay() and PHP's date('w')), or NULL if the engagement doesn't
 * have a standing weekly status call. Unlike every other column on this
 * table, this isn't a one-time date — the calendar recurs it every week
 * (on that weekday) for as long as the engagement stays active. No
 * companion *_completed_at column: it's a standing calendar marker, not a
 * deliverable with a due/complete state.
 *
 * Safe to run more than once — skips if the column already exists. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_weekly_status_call.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$check = $conn->query("SHOW COLUMNS FROM `engagement_timeline` LIKE 'weekly_status_call_day'");
if ($check && $check->num_rows > 0) {
    echo "Column weekly_status_call_day already exists, skipping.\n";
    exit(0);
}

if (!$conn->query("ALTER TABLE `engagement_timeline` ADD COLUMN `weekly_status_call_day` TINYINT NULL")) {
    fwrite(STDERR, "Failed to add column weekly_status_call_day: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "Added column weekly_status_call_day.\n";
echo "Done.\n";
