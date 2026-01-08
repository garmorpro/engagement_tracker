<?php
// declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$host = 'localhost';
$user = 'dbadmin';
$pass = 'DBadmin123!';
$db   = 'engagement_tracker';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die('❌ DB connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

echo "✅ DB connected (hardcoded)";
