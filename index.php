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
    // -------------------------
// WebAuthn helper functions
// -------------------------

// Convert base64url string to ArrayBuffer
function base64urlToBuffer(base64url) {
    const base64 = base64url.replace(/-/g, '+').replace(/_/g, '/');
    const padded = base64.padEnd(base64.length + (4 - base64.length % 4) % 4, '=');
    const binary = atob(padded);
    const buffer = new ArrayBuffer(binary.length);
    const bytes = new Uint8Array(buffer);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return buffer;
}

// Convert ArrayBuffer to base64url string
function bufferToBase64url(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

</script>



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

async function loginWithBiometrics(userUUID) {
    // Step 1: fetch login options from server
    const res = await fetch('/auth/login_biometric.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ user_uuid: userUUID })
    });
    const options = await res.json();

    // Convert challenge and allowedCredentials id(s) to ArrayBuffer
    options.challenge = base64urlToBuffer(options.challenge);
    options.allowCredentials.forEach(c => {
        c.id = base64urlToBuffer(c.id);
    });

    // Step 2: call WebAuthn API
    const assertion = await navigator.credentials.get({ publicKey: options });

    // Step 3: convert assertion to base64url
    const assertionData = {
        user_uuid: userUUID,
        id: assertion.id,
        rawId: bufferToBase64url(assertion.rawId),
        type: assertion.type,
        response: {
            authenticatorData: bufferToBase64url(assertion.response.authenticatorData),
            clientDataJSON: bufferToBase64url(assertion.response.clientDataJSON),
            signature: bufferToBase64url(assertion.response.signature),
            userHandle: assertion.response.userHandle ? bufferToBase64url(assertion.response.userHandle) : null
        }
    };

    // Step 4: send to verify_login.php
    const verifyRes = await fetch('/auth/verify_login.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(assertionData)
    });
    const verifyData = await verifyRes.json();
    if (verifyData.success) {
        window.location.href = '/pages/dashboard.php';
    } else {
        alert('Login failed: ' + verifyData.error);
    }
}


async function startRegistration() {
    const userUUID = document.getElementById('loginUserUuid').value;
    const accountName = document.getElementById('loginAccountName').value;

    // Step 1: fetch options from server
    const res = await fetch('/auth/register_biometric.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ user_uuid: userUUID, account_name: accountName })
    });
    const options = await res.json();

    // Step 2: convert challenge and user.id to ArrayBuffer
    options.challenge = base64urlToBuffer(options.challenge);
    options.user.id = base64urlToBuffer(options.user.id);

    // Step 3: call WebAuthn API
    const credential = await navigator.credentials.create({ publicKey: options });

    // Step 4: convert credential data to base64url for server
    const credentialData = {
        user_uuid: userUUID,
        account_name: accountName,
        id: credential.id,
        rawId: bufferToBase64url(credential.rawId),
        type: credential.type,
        response: {
            attestationObject: bufferToBase64url(credential.response.attestationObject),
            clientDataJSON: bufferToBase64url(credential.response.clientDataJSON)
        }
    };

    // Step 5: send to verify_registration.php
    const verifyRes = await fetch('/auth/verify_registration.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(credentialData)
    });
    const verifyData = await verifyRes.json();
    if (verifyData.success) {
        alert('Biometric registration successful!');
        window.location.reload();
    } else {
        alert('Registration failed: ' + verifyData.error);
    }
}


</script>
</body>
</html>
