<?php
/**
 * One-time migration: create the login_attempts table used for rate limiting
 * PIN entry (auth/login.php and auth/verify_admin_pin.php).
 *
 * Safe to run more than once — uses CREATE TABLE IF NOT EXISTS. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_create_login_attempts_table.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$sql = "
    CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(255) NOT NULL,
        attempt_type VARCHAR(20) NOT NULL,
        attempted_at DATETIME NOT NULL,
        INDEX idx_identifier_type_time (identifier, attempt_type, attempted_at)
    )
";

if (!$conn->query($sql)) {
    fwrite(STDERR, "Failed to create login_attempts table: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "login_attempts table ready.\n";
