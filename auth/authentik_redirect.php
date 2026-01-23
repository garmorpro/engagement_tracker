<?php
session_start();
require_once '../includes/functions.php';
require_once '../includes/init.php';

if (isset($_GET['code'], $_GET['state'])) {
    // Verify state
    if ($_GET['state'] !== $_SESSION['authentik_state']) {
        die('Invalid state');
    }

    $code = $_GET['code'];

    // Exchange code for token
    $token_url = 'http://10.10.254.198:9000/application/o/token/';
    $client_id = 'dekMyHfssWUpwBzKa42Nbfxw2OfJl8TTe78JWK7A';
    $client_secret = 'u82w0aAZPRbKahIpDFomyEtEaY4fOJQy4YyIwj6PondKZQKPoy02BmNHqNKpWXpu1Dg36yje4Z3s94EFDMi7D8cZd6xpMEnEbGswTxQ3HcBfNo4g2AUZpwi04ricKOhH';

    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => 'https://et.morganserver.com/pages/dashboard.php',
        'client_id' => $client_id,
        'client_secret' => $client_secret,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);

    if (!isset($data['access_token'])) {
        die('Token exchange failed');
    }

    // Get user info
    $userinfo_url = 'http://10.10.254.198:9000/application/o/userinfo/';
    $ch = curl_init($userinfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $data['access_token']
    ]);
    $user = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // Store user in DB
    $user_uuid = $user['sub'];
    $email = $user['email'] ?? '';
    $name = $user['name'] ?? '';

    $stmt = $conn->prepare("
        INSERT INTO authentik_users (user_uuid, email, name, access_token, refresh_token, last_login)
        VALUES (?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            email = VALUES(email),
            name = VALUES(name),
            access_token = VALUES(access_token),
            refresh_token = VALUES(refresh_token),
            last_login = NOW()
    ");
    $stmt->bind_param('sssss', $user_uuid, $email, $name, $data['access_token'], $refresh_token);
    $stmt->execute();
    $stmt->close();

    // Start session
    $_SESSION['user_uuid'] = $user_uuid;
    $_SESSION['email'] = $email;
    $_SESSION['name'] = $name;

    // Clean URL (remove code and state)
    header('Location: /pages/dashboard.php');
    exit;
}

// Rest of your dashboard code here...
