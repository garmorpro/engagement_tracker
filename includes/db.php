<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Dotenv\Dotenv;

/**
 * Resolve project root
 */
$root = realpath(__DIR__ . '/../../../');
if ($root === false) {
    die('❌ Failed to resolve project root');
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

echo "✅ DB connected via .env";
