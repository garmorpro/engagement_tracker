<?php
/**
 * One-time migration: create the activity_log table, used for the new
 * Activity Log page (pages/activity-log.php, admin-only) — a record of
 * who changed what and when for the actions that matter most for
 * accountability: engagement status changes, account
 * create/update/delete/role-change, and DOL edits.
 *
 * actor_name is a snapshot taken at write time (not a live join to
 * service_accounts) so the log still reads correctly even after an
 * account is later deleted.
 *
 * Safe to run more than once — CREATE TABLE IF NOT EXISTS. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_create_activity_log_table.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$sql = "
    CREATE TABLE IF NOT EXISTS activity_log (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        event_type VARCHAR(50) NOT NULL,
        actor_user_id INT NULL,
        actor_name VARCHAR(255) NOT NULL,
        target_type VARCHAR(50) NULL,
        target_id VARCHAR(100) NULL,
        description TEXT NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_at (created_at),
        INDEX idx_target (target_type, target_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";

if ($conn->query($sql)) {
    echo "activity_log table ready.\n";
} else {
    fwrite(STDERR, "Failed to create activity_log: " . $conn->error . PHP_EOL);
    exit(1);
}
