<?php
/**
 * One-time migration: add eng_planning_doc to engagements, storing the
 * generated filename of an uploaded planning-schedule screenshot/image
 * (the actual file lives in uploads/engagement-screenshots/, outside git).
 *
 * Safe to run more than once — skips if the column already exists. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_engagement_screenshot.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$check = $conn->query("SHOW COLUMNS FROM `engagements` LIKE 'eng_planning_doc'");
if ($check && $check->num_rows > 0) {
    echo "Column eng_planning_doc already exists, skipping.\n";
    exit(0);
}

if (!$conn->query("ALTER TABLE `engagements` ADD COLUMN `eng_planning_doc` VARCHAR(255) NULL")) {
    fwrite(STDERR, "Failed to add column eng_planning_doc: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "Added column eng_planning_doc.\n";
