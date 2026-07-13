<?php
declare(strict_types=1);

error_reporting(E_ALL);

use Dotenv\Dotenv;

/**
 * Resolve project root
 */
$root = realpath(__DIR__ . '/../../../');
if ($root === false) {
    error_log('db.php: failed to resolve project root from ' . __DIR__);
    die('❌ Server configuration error');
}

/**
 * Load Composer
 */
require_once $root . '/vendor/autoload.php';

/**
 * Load .env
 */
$dotenv = Dotenv::createImmutable($root);
$dotenv->load();

/**
 * Only show PHP errors on-screen when explicitly opted into via .env
 * (APP_DEBUG=true). Defaults to off so production never leaks stack
 * traces/file paths to visitors; errors are still logged either way.
 */
ini_set('display_errors', ($_ENV['APP_DEBUG'] ?? 'false') === 'true' ? '1' : '0');

/**
 * Read ENV
 */
$host = $_ENV['DB_HOST'] ?? null;
$user = $_ENV['DB_USER'] ?? null;
$pass = $_ENV['DB_PASSWORD'] ?? null;
$db   = $_ENV['DB_NAME'] ?? null;

if (!$host || !$user || !$pass || !$db) {
    die('❌ Missing ENV variables');
}

/**
 * Connect
 */
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('❌ DB connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// echo "✅ DB connected via .env";
