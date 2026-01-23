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
    return Uint8Array.from(atob(base64), c => c.charCodeAt(0));
}

function bufToB64(buf) {
    return btoa(String.fromCharCode(...buf))
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

/* ===============================
   REGISTER
   =============================== */
async function register() {
    console.log('▶ Starting registration');

    if (!window.isSecureContext) {
        alert('WebAuthn requires HTTPS');
        return;
    }

    const res = await fetch('/auth/webauthn_register.php', {
        headers: { 'Accept': 'application/json' }
    });

    const options = await res.json();
    console.log('📦 Register options from server:', options);

    options.challenge = b64ToBuf(options.challenge);
    options.user.id = b64ToBuf(options.user.id);

    let cred;
    try {
        cred = await navigator.credentials.create({
            publicKey: options
        });
    } catch (err) {
        console.error('❌ Registration failed:', err);
        alert(err.message);
        return;
    }

    console.log('✅ Credential created:', cred);

    const payload = {
        rawId: bufToB64(new Uint8Array(cred.rawId)),
        attestationObject: bufToB64(
            new Uint8Array(cred.response.attestationObject)
        )
    };

    console.log('📤 Sending registration payload:', payload);

    const save = await fetch('/auth/webauthn_register_save.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const result = await save.json();
    console.log('📥 Registration save response:', result);

    if (result.success) {
        alert('Fingerprint registered successfully');
    } else {
        alert('Registration failed');
    }
}

/* ===============================
   LOGIN
   =============================== */
async function login() {
    console.log('▶ Starting login');

    const res = await fetch('/auth/webauthn_login_options.php', {
        headers: { 'Accept': 'application/json' }
    });

    const options = await res.json();
    console.log('📦 Login options from server:', options);

    options.challenge = b64ToBuf(options.challenge);
    options.allowCredentials = options.allowCredentials.map(c => ({
        ...c,
        id: b64ToBuf(c.id)
    }));

    let assertion;
    try {
        assertion = await navigator.credentials.get({
            publicKey: options
        });
    } catch (err) {
        console.error('❌ Login failed:', err);
        alert(err.message);
        return;
    }

    console.log('✅ Assertion received:', assertion);

    const payload = {
        rawId: bufToB64(new Uint8Array(assertion.rawId)),
        response: {
            clientDataJSON: bufToB64(new Uint8Array(assertion.response.clientDataJSON)),
            authenticatorData: bufToB64(new Uint8Array(assertion.response.authenticatorData)),
            signature: bufToB64(new Uint8Array(assertion.response.signature))
        }
    };

    console.log('📤 Sending login payload:', payload);

    const verify = await fetch('/auth/webauthn_login_verify.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });

    const result = await verify.json();
    console.log('📥 Login verify response:', result);

    if (result.success) {
        location.href = '/pages/dashboard.php';
    } else {
        alert('Login failed');
    }
}
</script>

</body>
</html>
