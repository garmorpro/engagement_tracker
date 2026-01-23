<?php
require_once 'includes/functions.php';
require_once 'path.php';
require_once 'includes/init.php';

// Fetch all active accounts and check if biometrics exist
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
<body style="background-color: #d8d8d8;">
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-3 shadow" style="width: 100%; max-width: 425px;">
        <h5 class="text-center mb-2">Welcome</h5>
        <p class="text-center text-muted mb-3">
            Click an account to sign in with biometrics
        </p>

        <!-- Account List -->
        <?php if (!empty($accounts)): ?>
            <div class="list-group mb-3">
                <?php foreach ($accounts as $account): ?>
                    <button
                        type="button"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                        data-user-uuid="<?= $account['user_uuid'] ?>"
                        data-has-biometrics="<?= $account['has_biometrics'] ?>"
                        data-account-name="<?= htmlspecialchars($account['account_name']) ?>"
                        onclick="handleAccountClick(this)">
                        <?= htmlspecialchars($account['account_name'] ?? 'Unnamed Account') ?>
                        <?php if ($account['has_biometrics'] > 0): ?>
                            <i class="bi bi-fingerprint fs-4 text-success"></i>
                        <?php else: ?>
                            <i class="bi bi-circle fs-4 text-secondary" title="Register Biometrics"></i>
                        <?php endif; ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No accounts available</p>
        <?php endif; ?>

        <div class="d-grid mb-3">
            <button type="button" class="btn btn-primary" onclick="showRegisterForm()">
                Register New Account
            </button>
        </div>

        <!-- Biometric Registration Form -->
        <form id="biometricForm" class="p-4 d-none">
            <div class="mb-3">
                <label class="form-label">Account name (optional)</label>
                <input type="text" class="form-control" id="loginAccountName">
            </div>

            <div class="d-grid">
                <button type="button" class="btn btn-dark" onclick="registerAccount()">
                    Continue
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Show registration form
function showRegisterForm(accountName = '', userUUID = '') {
    document.getElementById('biometricForm').classList.remove('d-none');
    document.getElementById('loginAccountName').value = accountName;
}

// Click on existing account
async function handleAccountClick(btn) {
    const userUUID = btn.dataset.userUuid;
    const hasBiometrics = parseInt(btn.dataset.hasBiometrics);
    const accountName = btn.dataset.accountName;

    if (hasBiometrics) {
        await biometricLogin(userUUID);
    } else {
        showRegisterForm(accountName, userUUID);
    }
}

// Registration
async function registerAccount() {
    const accountName = document.getElementById('loginAccountName').value || 'Unnamed Account';

    // Step 1: GET registration options from server
    const res = await fetch(`/auth/register.php?account_name=${encodeURIComponent(accountName)}`);
    const options = await res.json();
    if (options.error) return alert(options.error);

    // Convert challenge & user id to ArrayBuffer
    options.challenge = Uint8Array.from(atob(options.challenge.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));
    options.user.id = Uint8Array.from(atob(options.user.id.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));

    if (options.excludeCredentials) {
        options.excludeCredentials = options.excludeCredentials.map(c => ({
            ...c,
            id: Uint8Array.from(atob(c.id.replace(/-/g,'+').replace(/_/g,'/')), d => d.charCodeAt(0))
        }));
    }

    // Step 2: Create credential
    const credential = await navigator.credentials.create({ publicKey: options });

    // Step 3: POST credential to server
    const payload = {
        id: credential.id,
        type: credential.type,
        rawId: btoa(String.fromCharCode(...new Uint8Array(credential.rawId))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
        response: {
            authenticatorData: btoa(String.fromCharCode(...new Uint8Array(credential.response.authenticatorData))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
            clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(credential.response.clientDataJSON))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
            signature: btoa(String.fromCharCode(...new Uint8Array(credential.response.signature))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
            userHandle: credential.response.userHandle ? btoa(String.fromCharCode(...new Uint8Array(credential.response.userHandle))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,'') : null
        },
        user_uuid: options.user_uuid
    };

    const postRes = await fetch('/auth/register.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });

    const result = await postRes.json();
    if (result.success) window.location.reload();
    else alert(result.error || 'Registration failed.');
}

// Biometric login
async function biometricLogin(userUUID) {
    try {
        const res = await fetch(`/auth/options.php?user_uuid=${userUUID}`);
        const options = await res.json();
        if (options.error) return alert(options.error);

        options.challenge = Uint8Array.from(atob(options.challenge.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));
        options.user.id = Uint8Array.from(atob(options.user.id.replace(/-/g,'+').replace(/_/g,'/')), c => c.charCodeAt(0));

        if (options.allowCredentials) {
            options.allowCredentials = options.allowCredentials.map(c => ({
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

        const verifyRes = await fetch('/auth/verify.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify(payload)
        });

        const result = await verifyRes.json();
        if (result.success) window.location.href = '/pages/dashboard.php';
        else alert('Login failed.');
    } catch (err) {
        console.error(err);
        alert('Biometric login not supported on this device.');
    }
}
</script>
</body>
</html>
