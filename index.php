<!DOCTYPE html>
<html>
<head>
    <title>WebAuthn Login</title>
</head>
<body>

<button onclick="register()">Register Fingerprint</button>
<button onclick="login()">Login with Fingerprint</button>

<script>
/* ===============================
   Base64URL helpers
   =============================== */
function b64ToBuf(b64) {
    const pad = '='.repeat((4 - b64.length % 4) % 4);
    const base64 = (b64 + pad).replace(/-/g, '+').replace(/_/g, '/');
    return Uint8Array.from(atob(base64), c=>c.charCodeAt(0));
}
function bufToB64(buf) {
    return btoa(String.fromCharCode(...buf)).replace(/\+/g,'-').replace(/\//g,'_').replace(/=+$/,'');
}

async function register() {
    const res = await fetch('/auth/webauthn_register.php');
    const options = await res.json();
    options.challenge = b64ToBuf(options.challenge);
    options.user.id = b64ToBuf(options.user.id);

    const cred = await navigator.credentials.create({ publicKey: options });
    const payload = { rawId: bufToB64(new Uint8Array(cred.rawId)), attestationObject: bufToB64(new Uint8Array(cred.response.attestationObject)) };
    await fetch('/auth/webauthn_register_save.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    alert('Fingerprint registered!');
}

async function login() {
    const res = await fetch('/auth/webauthn_login_options.php');
    const options = await res.json();
    options.challenge = b64ToBuf(options.challenge);
    options.allowCredentials = options.allowCredentials.map(c=>({...c,id:b64ToBuf(c.id)}));

    const assertion = await navigator.credentials.get({ publicKey: options });
    const payload = {
        rawId: bufToB64(new Uint8Array(assertion.rawId)),
        response: {
            clientDataJSON: bufToB64(new Uint8Array(assertion.response.clientDataJSON)),
            authenticatorData: bufToB64(new Uint8Array(assertion.response.authenticatorData)),
            signature: bufToB64(new Uint8Array(assertion.response.signature))
        }
    };

    const verify = await fetch('/auth/webauthn_login_verify.php', { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload) });
    const result = await verify.json();
    if(result.success) window.location.href = 'https://et.morganserver.com/pages/dashboard.php';
    else alert('Login failed');
}

</script>

</body>
</html>
