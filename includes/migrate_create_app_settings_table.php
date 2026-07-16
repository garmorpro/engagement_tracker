<?php
/**
 * One-time migration: create app_settings, a simple key/value store for
 * app-wide config (starting with the Slack webhook URL) that's editable
 * from the Admin Dashboard instead of living in a server file.
 *
 * Safe to run more than once — uses CREATE TABLE IF NOT EXISTS. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_create_app_settings_table.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$sql = "
    CREATE TABLE IF NOT EXISTS app_settings (
        setting_key VARCHAR(100) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )
";
if (!$conn->query($sql)) {
    fwrite(STDERR, "Failed to create app_settings table: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "app_settings table ready.\n";
