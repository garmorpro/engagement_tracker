<?php
/**
 * One-time migration: engagements.eng_timezone (VARCHAR 64, NULL) — the time zone the
 * client works in, stored as an IANA name (e.g. America/Chicago). Shown in the drawer's
 * Overview with the client's current local time, so call times can be set sensibly.
 *
 * Safe to run more than once. CLI only.
 *   php includes/migrate_add_engagement_timezone.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$col = $conn->query("SHOW COLUMNS FROM `engagements` LIKE 'eng_timezone'");
if ($col && $col->num_rows > 0) {
    echo "engagements.eng_timezone already exists, skipping.\n";
    exit(0);
}
if (!$conn->query("ALTER TABLE `engagements` ADD COLUMN `eng_timezone` VARCHAR(64) NULL")) {
    fwrite(STDERR, "Failed to add engagements.eng_timezone: " . $conn->error . PHP_EOL);
    exit(1);
}
echo "Added engagements.eng_timezone.\n";
