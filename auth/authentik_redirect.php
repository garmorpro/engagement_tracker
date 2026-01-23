<?php
// authentik_redirect.php
session_start();

// Replace these with your Authentik settings
$client_id = 'YOUR_CLIENT_ID';
$redirect_uri = 'https://yourdomain.com/auth/authentik_callback.php';
$auth_url = 'https://authentik.example.com/application/o/authorize/';

$state = bin2hex(random_bytes(16)); // Prevent CSRF attacks
$_SESSION['authentik_state'] = $state;

$params = http_build_query([
    'client_id' => $client_id,
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'redirect_uri' => $redirect_uri,
    'state' => $state,
]);

header("Location: $auth_url?$params");
exit;
