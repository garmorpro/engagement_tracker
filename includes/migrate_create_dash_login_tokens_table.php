<?php
/**
 * One-time migration: create the dash_login_tokens table used by the one-time
 * sign-in links of the Desk Ctrl Panel "open on Mac mini" feature
 * (api/dashboard-open-token.php issues them, auth/token-login.php redeems them).
 *
 * Only a SHA-256 hash of each token is stored, never the token itself.
 * Safe to run more than once — uses CREATE TABLE IF NOT EXISTS. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_create_dash_login_tokens_table.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$sql = "
    CREATE TABLE IF NOT EXISTS dash_login_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        token_hash CHAR(64) NOT NULL,
        expires_at DATETIME NOT NULL,
        used_at DATETIME NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_token_hash (token_hash),
        INDEX idx_expires (expires_at)
    )
";

if (!$conn->query($sql)) {
    fwrite(STDERR, "Failed to create dash_login_tokens table: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "dash_login_tokens table ready.\n";
