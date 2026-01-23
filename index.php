<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>

<button onclick="login()">Login with Fingerprint</button>

<script>
function base64urlToUint8Array(base64url) {
    const padding = '='.repeat((4 - base64url.length % 4) % 4);
    const base64 = (base64url + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const raw = atob(base64);
    return Uint8Array.from([...raw].map(c => c.charCodeAt(0)));
}

function uint8ArrayToBase64url(buffer) {
    let binary = '';
    buffer.forEach(b => binary += String.fromCharCode(b));
    return btoa(binary)
        .replace(/\+/g, '-')
        .replace(/\//g, '_')
        .replace(/=+$/, '');
}

async function login() {
    const res = await fetch('/auth/webauthn_login_options.php');
    const options = await res.json();

    console.log('OPTIONS:', options); // 👈 DEBUG

    options.challenge = base64urlToUint8Array(options.challenge);

    options.allowCredentials = options.allowCredentials.map(c => ({
        ...c,
        id: base64urlToUint8Array(c.id)
    }));

    const cred = await navigator.credentials.get({
        publicKey: options
    });

    const payload = {
        rawId: uint8ArrayToBase64url(new Uint8Array(cred.rawId)),
        response: {
            clientDataJSON: uint8ArrayToBase64url(new Uint8Array(cred.response.clientDataJSON)),
            authenticatorData: uint8ArrayToBase64url(new Uint8Array(cred.response.authenticatorData)),
            signature: uint8ArrayToBase64url(new Uint8Array(cred.response.signature))
        }
    };

    const verify = await fetch('/auth/webauthn_login_verify.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify(payload)
    });

    const result = await verify.json();
    if (result.success) location.href = '/pages/dashboard.php';
    else alert('Login failed');
}

</script>

</body>
</html>
