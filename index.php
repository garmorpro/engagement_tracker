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
<div class="card p-3 shadow" style="width:100%; max-width:425px;">
<h5 class="text-center mb-2">Welcome</h5>
<p class="text-center text-muted mb-3">Click an account to sign in with biometrics</p>

<?php if(!empty($accounts)): ?>
<div class="list-group mb-3">
<?php foreach($accounts as $account): ?>
<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
        data-user-uuid="<?= $account['user_uuid'] ?>"
        data-has-biometrics="<?= $account['has_biometrics'] ?>"
        data-account-name="<?= htmlspecialchars($account['account_name']) ?>"
        onclick="handleAccountClick(this)">
<?= htmlspecialchars($account['account_name'] ?? 'Unnamed Account') ?>
<?php if($account['has_biometrics']>0): ?>
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
<button type="button" class="btn btn-primary" onclick="showRegisterForm()">Register New Account</button>
</div>

<form id="biometricForm" class="p-4 d-none">
<input type="hidden" id="loginUserUuid">
<div class="mb-3">
<label class="form-label">Account name (optional)</label>
<input type="text" class="form-control" id="loginAccountName">
</div>
<div class="d-grid">
<button type="button" class="btn btn-dark" onclick="startRegistration()">Continue</button>
</div>
</form>
</div>
</div>

<script>
function showRegisterForm(name='', uuid=''){
    document.getElementById('biometricForm').classList.remove('d-none');
    document.getElementById('loginAccountName').value = name;
    document.getElementById('loginUserUuid').value = uuid;
}

async function handleAccountClick(btn){
    const uuid = btn.dataset.userUuid;
    const hasBiometrics = parseInt(btn.dataset.hasBiometrics);
    if(hasBiometrics){
        await loginBiometric(uuid);
    } else {
        showRegisterForm(btn.dataset.accountName, uuid);
    }
}

// ---------------- Registration ----------------
async function startRegistration(){
    const name = document.getElementById('loginAccountName').value;
    const uuid = document.getElementById('loginUserUuid').value;

    // 1. Get registration options
    const res = await fetch(`/auth/register.php?account_name=${encodeURIComponent(name)}&user_uuid=${uuid}`);
    const options = await res.json();
    if(options.error) return alert(options.error);

    options.challenge = Uint8Array.from(atob(options.challenge.replace(/-/g,'+').replace(/_/g,'/')), c=>c.charCodeAt(0));
    options.user.id = Uint8Array.from(atob(options.user.id.replace(/-/g,'+').replace(/_/g,'/')), c=>c.charCodeAt(0));
    options.excludeCredentials = options.excludeCredentials.map(c=>{
        return {...c, id: Uint8Array.from(atob(c.id.replace(/-/g,'+').replace(/_/g,'/')), d=>d.charCodeAt(0))};
    });

    const cred = await navigator.credentials.create({publicKey: options});

    const payload = {
        id: cred.id,
        rawId: btoa(String.fromCharCode(...new Uint8Array(cred.rawId))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
        type: cred.type,
        response: {
            attestationObject: btoa(String.fromCharCode(...new Uint8Array(cred.response.attestationObject))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
            clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(cred.response.clientDataJSON))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,'')
        },
        account_name: name,
        user_uuid: options.user_uuid
    };

    const verify = await fetch('/auth/register.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });
    const result = await verify.json();
    if(result.success) location.reload();
    else alert(result.error || 'Registration failed');
}

// ---------------- Login ----------------
async function loginBiometric(uuid){
    const res = await fetch(`/auth/options.php?user_uuid=${uuid}`);
    const options = await res.json();
    if(options.error) return alert(options.error);

    options.challenge = Uint8Array.from(atob(options.challenge.replace(/-/g,'+').replace(/_/g,'/')), c=>c.charCodeAt(0));
    options.user.id = Uint8Array.from(atob(options.user.id.replace(/-/g,'+').replace(/_/g,'/')), c=>c.charCodeAt(0));
    options.allowCredentials = options.allowCredentials.map(c=>{
        return {...c, id: Uint8Array.from(atob(c.id.replace(/-/g,'+').replace(/_/g,'/')), d=>d.charCodeAt(0))};
    });

    const cred = await navigator.credentials.get({publicKey: options});
    const payload = {
        id: cred.id,
        rawId: btoa(String.fromCharCode(...new Uint8Array(cred.rawId))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
        type: cred.type,
        response: {
            authenticatorData: btoa(String.fromCharCode(...new Uint8Array(cred.response.authenticatorData))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
            clientDataJSON: btoa(String.fromCharCode(...new Uint8Array(cred.response.clientDataJSON))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
            signature: btoa(String.fromCharCode(...new Uint8Array(cred.response.signature))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,''),
            userHandle: cred.response.userHandle ? btoa(String.fromCharCode(...new Uint8Array(cred.response.userHandle))).replace(/\+/g,'-').replace(/\//g,'_').replace(/=/g,'') : null
        }
    };

    const verify = await fetch('/auth/verify.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });
    const result = await verify.json();
    if(result.success) window.location.href='/pages/dashboard.php';
    else alert(result.error || 'Login failed');
}
</script>
</body>
</html>
