<?php
/**
 * One-time migration: add notif_field to engagement_notifications, recording
 * which specific timeline date column (for upcoming_key_date) or milestone
 * id (for upcoming_milestone) triggered a given notification.
 *
 * This existed only as engagement_idno + notif_type before, which meant (a)
 * an engagement could only ever get ONE upcoming_key_date notification for
 * its entire lifetime — completing that date and having a different one come
 * due later would never notify, since the exclusion check didn't know they
 * were different fields — and (b) there was no way to resolve a notification
 * when the specific item it was about got marked complete, since nothing
 * recorded which item that was.
 *
 * Safe to run more than once — skips if the column already exists. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_notif_field.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$check = $conn->query("SHOW COLUMNS FROM `engagement_notifications` LIKE 'notif_field'");
if ($check && $check->num_rows > 0) {
    echo "Column notif_field already exists, skipping.\n";
    exit(0);
}

if (!$conn->query("ALTER TABLE `engagement_notifications` ADD COLUMN `notif_field` VARCHAR(64) NULL")) {
    fwrite(STDERR, "Failed to add column notif_field: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "Added column notif_field.\n";
