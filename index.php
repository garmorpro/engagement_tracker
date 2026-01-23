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
        alert('WebAuthn requires HTTPS or localhost');
        return;
    }

    // Fetch registration options from server
    let res;
    try {
        res = await fetch('/auth/webauthn_register.php', { headers: { 'Accept': 'application/json' } });
    } catch (err) {
        console.error('❌ Failed to fetch register options:', err);
        alert('Could not fetch registration options');
        return;
    }

    let options;
    try {
        options = await res.json();
    } catch (err) {
        console.error('❌ Invalid JSON from register options:', err);
        alert('Server returned invalid JSON');
        return;
    }

    console.log('📦 Register options from server:', options);

    // Convert challenge & user.id to ArrayBuffer
    options.challenge = b64ToBuf(options.challenge);
    options.user.id = b64ToBuf(options.user.id);

    let cred;
    try {
        cred = await navigator.credentials.create({ publicKey: options });
    } catch (err) {
        console.error('❌ Credential creation failed:', err);
        alert(err.message);
        return;
    }

    console.log('✅ Credential created:', cred);

    const payload = {
        rawId: bufToB64(new Uint8Array(cred.rawId)),
        attestationObject: bufToB64(new Uint8Array(cred.response.attestationObject))
    };

    console.log('📤 Sending registration payload:', payload);

    let save;
    try {
        save = await fetch('/auth/webauthn_register_save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
    } catch (err) {
        console.error('❌ Failed to send registration payload:', err);
        alert('Could not send registration data to server');
        return;
    }

    let result;
    try {
        result = await save.json();
    } catch (err) {
        console.error('❌ Invalid JSON from registration save:', err);
        alert('Server returned invalid JSON on registration save');
        return;
    }

    console.log('📥 Registration save response:', result);

    if (result.success) {
        alert('Fingerprint registered successfully');
    } else {
        alert('Registration failed: ' + (result.error || 'Unknown error'));
    }
}

/* ===============================
   LOGIN
   =============================== */
async function login() {
    console.log('▶ Starting login');

    // Fetch login options
    let res;
    try {
        res = await fetch('/auth/webauthn_login_options.php', { headers: { 'Accept': 'application/json' } });
    } catch (err) {
        console.error('❌ Failed to fetch login options:', err);
        alert('Could not fetch login options');
        return;
    }

    let optionsText;
    try {
        optionsText = await res.text();
        console.log('📦 Raw login options response:', optionsText);
    } catch (err) {
        console.error('❌ Failed to read login options text:', err);
        return;
    }

    let options;
    try {
        options = JSON.parse(optionsText);
    } catch (err) {
        console.error('❌ Invalid JSON from login options:', err);
        alert('Server returned invalid JSON for login');
        return;
    }

    if (options.error) {
        alert('Login error: ' + options.error);
        return;
    }

    // Convert challenge & credential IDs
    options.challenge = b64ToBuf(options.challenge);
    if (options.allowCredentials) {
        options.allowCredentials = options.allowCredentials.map(c => ({
            ...c,
            id: b64ToBuf(c.id)
        }));
    }

    let assertion;
    try {
        assertion = await navigator.credentials.get({ publicKey: options });
    } catch (err) {
        console.error('❌ Assertion failed:', err);
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

    let verify;
    try {
        verify = await fetch('/auth/webauthn_login_verify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
    } catch (err) {
        console.error('❌ Failed to send login payload:', err);
        alert('Could not verify login');
        return;
    }

    let result;
    try {
        const text = await verify.text();
        console.log('📥 Login verify response (raw):', text);
        result = JSON.parse(text);
    } catch (err) {
        console.error('❌ Invalid JSON from login verify:', err);
        alert('Server returned invalid JSON during login');
        return;
    }

    if (result.success) {
        alert('Login successful');
        location.href = '/pages/dashboard.php';
    } else {
        alert('Login failed: ' + (result.error || 'Unknown error'));
    }
}
</script>

</body>
</html>
