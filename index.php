<?php
require_once 'includes/functions.php';
require_once 'path.php';
require_once 'includes/init.php';

// Fetch active biometric accounts and check if they have credentials
$bioResult = $conn->query("
    SELECT ba.user_uuid, ba.account_name,
           COUNT(wc.id) AS has_biometrics
    FROM biometric_accounts ba
    LEFT JOIN webauthn_credentials wc ON wc.user_uuid = ba.user_uuid
    WHERE ba.status = 'active'
    GROUP BY ba.user_uuid
    ORDER BY ba.account_name
");
$bioAccounts = $bioResult ? $bioResult->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Biometric Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body style="background-color: #f1f1f1;">
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="card p-3 shadow" style="width:100%; max-width:400px;">
        <h5 class="text-center mb-3">Welcome</h5>

        <?php if (!empty($bioAccounts)): ?>
            <p class="text-center text-muted mb-2">Click an account to sign in with biometrics</p>
            <div class="list-group mb-3">
            <?php foreach ($bioAccounts as $account): ?>
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                    data-user-uuid="<?= $account['user_uuid'] ?>"
                    data-has-biometrics="<?= $account['has_biometrics'] ?>"
                    data-account-name="<?= htmlspecialchars($account['account_name']) ?>"
                    onclick="handleAccountClick(this)">
                    <?= htmlspecialchars($account['account_name'] ?? 'Unnamed Account') ?>
                    <?php if ($account['has_biometrics'] > 0): ?>
                        <i class="bi bi-fingerprint text-success fs-4"></i>
                    <?php else: ?>
                        <i class="bi bi-circle text-secondary fs-4"></i>
                    <?php endif; ?>
                </button>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted mb-3">No biometric accounts available</p>
        <?php endif; ?>

        <div class="d-grid mb-3">
            <button type="button" class="btn btn-primary" onclick="showRegisterForm()">Register New Account</button>
        </div>

        <!-- Biometric Registration Form -->
        <form id="biometricForm" class="p-3 border rounded d-none" method="POST">
            <input type="hidden" name="user_uuid" id="loginUserUuid">
            <div class="mb-3">
                <label class="form-label">Account Name</label>
                <input type="text" class="form-control" name="account_name" id="loginAccountName" placeholder="Optional">
            </div>
            <div class="d-grid">
                <button type="button" class="btn btn-dark" onclick="startRegistration()">Register / Enable Biometrics</button>
            </div>
        </form>
    </div>
</div>

<script>
// Show registration form
function showRegisterForm(accountName = '', userUUID = '') {
    document.getElementById('biometricForm').classList.remove('d-none');
    document.getElementById('loginAccountName').value = accountName;
    document.getElementById('loginUserUuid').value = userUUID;
}

// Handle account click
async function handleAccountClick(btn) {
    const userUUID = btn.dataset.userUuid;
    const hasBiometrics = parseInt(btn.dataset.hasBiometrics);

    if(hasBiometrics) {
        await loginWithBiometrics(userUUID);
    } else {
        showRegisterForm(btn.dataset.accountName, userUUID);
    }
}

// Dummy function for demo (replace with real WebAuthn)
async function loginWithBiometrics(userUUID) {
    alert(`Logging in user ${userUUID} with biometrics...`);
    // Implement WebAuthn authentication flow here
}

// Dummy registration starter (replace with WebAuthn registration)
function startRegistration() {
    const userUUID = document.getElementById('loginUserUuid').value;
    const accountName = document.getElementById('loginAccountName').value;
    alert(`Registering biometric for ${accountName || 'Unnamed Account'} (${userUUID})`);
    // Implement WebAuthn registration flow here
}
</script>
</body>
</html>
