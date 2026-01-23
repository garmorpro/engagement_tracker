<?php
require_once 'includes/functions.php';
require_once 'path.php';
require_once 'includes/init.php';

// Fetch active accounts and check if biometrics exist
$result = $conn->query("
    SELECT ba.user_uuid, ba.account_name,
           COUNT(wc.id) AS has_biometrics
    FROM biometric_accounts ba
    LEFT JOIN webauthn_credentials wc ON wc.user_uuid = ba.user_uuid
    WHERE ba.status = 'active'
    GROUP BY ba.user_uuid
    ORDER BY ba.account_name
");
$accounts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - Engagement Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: #d8d8d8;">
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height:100vh;">
<div class="card p-3 shadow" style="width:100%; max-width:425px;">
    <h5 class="text-center mb-2">Welcome</h5>
    <p class="text-center text-muted mb-3">Click an account to sign in with biometrics</p>

    <?php if (!empty($accounts)): ?>
    <div class="list-group mb-3">
        <?php foreach ($accounts as $account): ?>
            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                data-user-uuid="<?= $account['user_uuid'] ?>"
                data-has-biometrics="<?= $account['has_biometrics'] ?>"
                data-account-name="<?= htmlspecialchars($account['account_name']) ?>"
                onclick="handleAccountClick(this)">
                <?= htmlspecialchars($account['account_name'] ?? 'Unnamed Account') ?>
                <?php if ($account['has_biometrics'] > 0): ?>
                    <i class="bi bi-fingerprint fs-4 text-success"></i>
                <?php else: ?>
                    <i class="bi bi-circle fs-4 text-secondary"></i>
                <?php endif; ?>
            </button>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p class="text-center text-muted">No accounts available</p>
    <?php endif; ?>

    <div class="d-grid mb-3">
        <button type="button" class="btn btn-primary" onclick="showRegisterForm()">Register New Account</button>
    </div>

    <form id="biometricForm" class="p-4 d-none" method="POST">
        <input type="hidden" name="user_uuid" id="loginUserUuid">
        <div class="mb-3">
            <label class="form-label">Account name (optional)</label>
            <input type="text" class="form-control" name="account_name" id="loginAccountName">
        </div>
        <div class="d-grid">
            <button type="button" class="btn btn-dark" onclick="startRegistration()">Continue</button>
        </div>
    </form>

    <div class="d-grid mb-3">
    <a href="/auth/authentik_redirect.php" class="btn btn-info">
        Login with Authentik
    </a>
</div>

</div>
</div>


</body>
</html>
