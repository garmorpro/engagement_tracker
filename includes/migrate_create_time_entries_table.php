<?php
/**
 * One-time migration: create engagement_time_entries, the time logged against an engagement
 * (the drawer's "Time" section, and the Desk Ctrl Panel's Log time button via
 * api/dashboard-log-time.php).
 *
 * minutes (not hours) so 15-minute chunks never pick up rounding errors. entry_date is the day the
 * work was done, created_at is when it was entered. source says where it came from ('tracker' or 'desk panel').
 *
 * Safe to run more than once — CREATE TABLE IF NOT EXISTS. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_create_time_entries_table.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$sql = "
    CREATE TABLE IF NOT EXISTS engagement_time_entries (
        entry_id INT AUTO_INCREMENT PRIMARY KEY,
        engagement_idno VARCHAR(64) NOT NULL,
        entry_date DATE NOT NULL,
        minutes INT NOT NULL,
        task VARCHAR(60) NULL,
        note VARCHAR(500) NULL,
        author_user_id INT NULL,
        author_name VARCHAR(255) NULL,
        source VARCHAR(30) NOT NULL DEFAULT 'tracker',
        created_at DATETIME NOT NULL,
        INDEX idx_engagement_date (engagement_idno, entry_date),
        INDEX idx_date (entry_date)
    ) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
";

if (!$conn->query($sql)) {
    fwrite(STDERR, "Failed to create engagement_time_entries table: " . $conn->error . PHP_EOL);
    exit(1);
}

// Servers that ran the first version of this migration don't have the task column yet.
if (!$conn->query("ALTER TABLE engagement_time_entries ADD COLUMN IF NOT EXISTS task VARCHAR(60) NULL AFTER minutes")) {
    fwrite(STDERR, "Failed to add the task column: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "engagement_time_entries table ready (with task column).\n";
