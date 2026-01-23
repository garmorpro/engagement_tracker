<?php
require_once 'includes/functions.php';
require_once 'path.php';
require_once 'includes/init.php';

// Fetch all active biometric accounts and check if biometrics exist
$result = $conn->query("
    SELECT ba.id, ba.user_uuid, ba.account_name,
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
<body style="background-color: rgba(216, 216, 216, 1);">
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-3 shadow" style="width: 100%; max-width: 425px;">
        <h5 class="text-center mb-2">Welcome Back</h5>
        <p class="text-center text-muted mb-3">
            Click an account to sign in
        </p>

        <!-- Account List -->
        <?php if (!empty($accounts)): ?>
            <div class="list-group mb-3">
                <?php foreach ($accounts as $account): ?>
                    <button
                        type="button"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                        data-user-id="<?= $account['user_id'] ?>"
                        data-has-biometrics="<?= $account['has_biometrics'] ?>"
                        data-account-name="<?= htmlspecialchars($account['account_name']) ?>"
                        onclick="handleAccountClick(this)">
                        <?= htmlspecialchars($account['account_name']) ?>
                        <?php if ($account['has_biometrics'] > 0): ?>
                            <i class="bi bi-fingerprint fs-4 text-success"></i>
                        <?php else: ?>
                            <i class="bi bi-circle fs-4 text-secondary" title="Enable Face ID / Touch ID"></i>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No accounts available</p>
            <div class="d-grid mb-3">
                <button type="button" class="btn btn-primary" onclick="showRegisterForm()">
                    Register Account
                </button>
            </div>
        <?php endif; ?>

        <!-- Password Login / Registration Form -->
        <form id="passwordLoginForm" class="p-4 d-none" method="POST" action="<?= BASE_URL ?>/auth/login.php">
            <input type="hidden" name="user_id" id="loginUserId">
            <input type="hidden" name="enable_biometrics" id="enableBiometricsFlag" value="0">
            <input type="hidden" name="is_register" id="isRegisterFlag" value="0">

            <div class="mb-3">
                <label class="form-label">Account name</label>
                <input type="text" class="form-control" name="account_name" id="loginAccountName" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn" style="background-color: rgb(23,62,70); color: white;">
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showPasswordLogin(accountName = '', userId = 0) {
    document.getElementById('passwordLoginForm').classList.remove('d-none');
    document.getElementById('loginAccountName').value = accountName;
    document.getElementById('loginUserId').value = userId;
    document.getElementById('enableBiometricsFlag').value = '1'; // enable biometrics after login
    document.getElementById('isRegisterFlag').value = '0'; // existing account
}

function showRegisterForm() {
    document.getElementById('passwordLoginForm').classList.remove('d-none');
    document.getElementById('loginAccountName').value = '';
    document.getElementById('loginUserId').value = '0';
    document.getElementById('enableBiometricsFlag').value = '1';
    document.getElementById('isRegisterFlag').value = '1'; // new account
}

// Handle click on existing account
async function handleAccountClick(btn) {
    const userId = btn.dataset.userId;
    const hasBiometrics = parseInt(btn.dataset.hasBiometrics, 10);
    const accountName = btn.dataset.accountName;

    if (hasBiometrics) {
        await biometricLogin(userId);
    } else {
        // First time login → show password form and register biometrics
        showPasswordLogin(accountName, userId);
    }
}

// Biometric login for existing credentials
async function biometricLogin(userId) {
    try {
        const res = await fetch(`/webauthn/options.php?user_id=${userId}`);
        const options = await res.json();
        if (options.error) return showPasswordLogin(options.accountName, userId);

        // Convert challenge & user ID to ArrayBuffer
        options.challenge = Uint8Array.from(atob(options.challenge.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));
        options.user.id = Uint8Array.from(atob(options.user.id.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));

        if (options.excludeCredentials) {
            options.excludeCredentials = options.excludeCredentials.map(c => ({
                ...c,
                id: Uint8Array.from(atob(c.id.replace(/-/g,'+').replace(/_/g,'/')), d => d.charCodeAt(0))
            }));
        }

        const credential = await navigator.credentials.get({ publicKey: options });

        const payload = {
            id: credential.id,
            type: credential.type,
            rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
            response: {
                authenticatorData: btoa(String.fromCharCode(...new Uint8Array(credential.response.authenticatorData))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
                clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
                signature: btoa(String.fromCharCode(...new Uint8Array(credential.response.signature))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
                userHandle: credential.response.userHandle ? btoa(String.fromCharCode(...new Uint8Array(credential.response.userHandle))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,'') : null
            }
        };

        const verifyRes = await fetch('/webauthn/verify.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });

        const result = await verifyRes.json();
        if (result.success) window.location.href = '/pages/dashboard.php';
        else alert('Biometric authentication failed.');
    } catch (err) {
        console.error(err);
        alert('Biometric login not available on this device.');
    }
}
</script>
</body>
</html>
