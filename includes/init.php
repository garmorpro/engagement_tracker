<?php
require_once 'functions.php';

/**
 * Authentication guard
 */

// If user is NOT logged in and not on index.php → redirect to login
if (empty($_SESSION['user_id'])) {
    if (basename($_SERVER['PHP_SELF']) !== 'index.php') {
        header("Location: /index.php");
        exit;
    }
}

// If user IS logged in and tries to access index.php → redirect to dashboard
if (!empty($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) === 'index.php') {
    header("Location: /pages/dashboard.php");
    exit;
}
