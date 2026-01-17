<?php
require_once '../includes/functions.php';

$error = loginUser($conn);

if ($error) {
    $_SESSION['login_error'] = $error;
    header("Location: /index.php");
    exit;
}
