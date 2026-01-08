<?php
declare(strict_types=1);

use Dotenv\Dotenv;

/**
 * Resolve project root:
 * includes → engagement_tracker → public_html → project root
 */
$rootPath = realpath(__DIR__ . '/../../../');
if ($rootPath === false) {
    die('Failed to resolve project root');
}

/**
 * Load Composer
 */
$autoload = $rootPath . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    die('Missing vendor/autoload.php at ' . $autoload);
}

require_once $autoload;

/**
 * Load .env
 */
$envFile = $rootPath . '/.env';
if (!file_exists($envFile)) {
    die('Missing .env file at ' . $envFile);
}

$dotenv = Dotenv::createImmutable($rootPath);
$dotenv->load();

/**
 * Read ENV
 */
$host   = $_ENV['DB_HOST'] ?? null;
$user   = $_ENV['DB_USER'] ?? null;
$pass   = $_ENV['DB_PASSWORD'] ?? null;
$dbname = $_ENV['DB_NAME'] ?? null;

if (!$host || !$user || !$pass || !$dbname) {
    die('Database ENV variables missing');
}

/**
 * Connect
 */
$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
