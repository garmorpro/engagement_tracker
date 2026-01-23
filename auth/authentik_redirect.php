<?php
// authentik_redirect.php
session_start();

$client_id = 'dekMyHfssWUpwBzKa42Nbfxw2OfJl8TTe78JWK7A';
$redirect_uri = 'https://et.morganserver.com/auth/authentik_callback.php'; // ← updated
$auth_url = 'http://10.10.254.198:9000/application/o/authorize/';

$state = bin2hex(random_bytes(16));
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
