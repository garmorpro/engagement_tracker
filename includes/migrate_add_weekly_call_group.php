<?php
/**
 * One-time migration: add weekly_status_call_group to engagement_timeline —
 * a shared, arbitrary group value used to link two or more engagements
 * that share the same standing weekly status call (e.g. two engagements
 * for the same client, same call, same day), so the calendar shows them
 * as one combined entry instead of duplicate near-identical chips.
 *
 * NULL means "not linked to anything." Two engagements are considered
 * linked when this column holds the same non-null value. Populated via
 * api/link-weekly-status-call.php, not directly editable — the value
 * itself is just an internal join key (this migration seeds it as
 * whichever engagement first established the link's own eng_idno; see
 * that endpoint for the actual linking logic).
 *
 * Safe to run more than once — skips if the column already exists. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_weekly_call_group.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$check = $conn->query("SHOW COLUMNS FROM `engagement_timeline` LIKE 'weekly_status_call_group'");
if ($check && $check->num_rows > 0) {
    echo "Column weekly_status_call_group already exists, skipping.\n";
    exit(0);
}

if (!$conn->query("ALTER TABLE `engagement_timeline` ADD COLUMN `weekly_status_call_group` VARCHAR(64) NULL")) {
    fwrite(STDERR, "Failed to add column weekly_status_call_group: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "Added column weekly_status_call_group.\n";
echo "Done.\n";
