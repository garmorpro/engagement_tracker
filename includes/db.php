<?php
declare(strict_types=1);

use Dotenv\Dotenv;

$appRoot = realpath(__DIR__ . '/..');
if ($appRoot === false) {
    die('Failed to resolve app root');
}

require_once $appRoot . '/../../vendor/autoload.php';

$dotenv = Dotenv::createImmutable($appRoot);
$dotenv->load();

$host   = $_ENV['DB_HOST'] ?? null;
$user   = $_ENV['DB_USER'] ?? null;
$pass   = $_ENV['DB_PASSWORD'] ?? null;
$dbname = $_ENV['DB_NAME'] ?? null;

if (!$host || !$user || !$pass || !$dbname) {
    die('Missing database env vars');
}

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
