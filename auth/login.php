<?php
require_once '../includes/functions.php';

$error = loginUser($conn);

// If loginUser() returns an error, store it and redirect back
if ($error) {
    $_SESSION['login_error'] = $error;
    header("Location: /");
    exit;
}
