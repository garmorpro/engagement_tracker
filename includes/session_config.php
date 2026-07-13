<?php
/**
 * Central session cookie configuration. No dependencies (safe to require
 * from anywhere without pulling in db.php), so every session_start() call
 * site in the app should go through startSecureSession() instead of calling
 * session_start() directly, to guarantee these params are set first.
 */
function startSecureSession()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => true,   // cookie only sent over HTTPS
            'httponly' => true, // not readable via JS
            'samesite' => 'Lax', // blocks cross-site POST/fetch (CSRF), allows normal link navigation
        ]);
        session_start();
    }
}
