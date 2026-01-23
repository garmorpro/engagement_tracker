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
    const optRes = await fetch('/auth/webauthn_login_options.php');
    const options = await optRes.json();

    options.challenge = b64ToBuf(options.challenge);
    options.allowCredentials.forEach(c => c.id = b64ToBuf(c.id));

    const cred = await navigator.credentials.get({ publicKey: options });

    const res = await fetch('/auth/webauthn_login_verify.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            id: cred.id,
            rawId: bufToB64(cred.rawId),
            response: {
                authenticatorData: bufToB64(cred.response.authenticatorData),
                clientDataJSON: bufToB64(cred.response.clientDataJSON),
                signature: bufToB64(cred.response.signature)
            }
        })
    });

    const data = await res.json();
    if (data.success) location.href = '/pages/dashboard.php';
    else alert('Login failed');
}
</script>

</body>
</html>
