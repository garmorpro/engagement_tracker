<?php
require_once 'includes/functions.php';
require_once 'path.php';
require_once 'includes/init.php';

// Fetch active accounts for biometric login list
$result = $conn->query("
    SELECT user_id, account_name
    FROM service_accounts
    WHERE status = 'active'
    ORDER BY account_name
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

        <!-- Logo (optional) -->
        <!-- <img src="../assets/images/aarc-360-logo-1.webp" class="mx-auto d-block" style="width:50%;"> -->

        <div class="mt-4"></div>

        <h5 class="text-center mb-2">Welcome Back</h5>
        <p class="text-center text-muted mb-3">
            Select an account to sign in with biometrics
        </p>

        <!-- Account List (Biometric Login) -->
        <?php if (!empty($accounts)): ?>
            <div class="list-group mb-3">
                <?php foreach ($accounts as $account): ?>
                    <button
                        type="button"
                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                        onclick="biometricLogin(<?= (int)$account['user_id'] ?>)">
                        <?= htmlspecialchars($account['account_name']) ?>
                        <i class="bi bi-fingerprint fs-4"></i>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No accounts available</p>
        <?php endif; ?>

        <!-- Divider -->
        <div class="text-center text-muted my-3">OR</div>

        <!-- Password Login Toggle -->
        <div class="d-grid mb-3">
            <button type="button" class="btn btn-outline-secondary" onclick="showPasswordLogin()">
                Sign in with password
            </button>
        </div>

        <!-- Password Login Form (Hidden by default) -->
        <form id="passwordLoginForm"
              class="p-4 d-none"
              method="POST"
              action="<?= BASE_URL ?>/auth/login.php">

            <div class="mb-3">
                <label class="form-label">Account name</label>
                <input type="text" class="form-control" name="account_name" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn" style="background-color: rgb(23,62,70); color: white;">
                    Sign In
                </button>
            </div>
        </form>

        <p class="text-center text-muted mt-3">
            Contact your administrator for account setup.
        </p>

        <p class="text-center text-muted" style="font-size: 10px;">
            This is a demo application. In production, users would be created by administrators.
        </p>
    </div>
</div>

<!-- JS -->
<script>
function showPasswordLogin() {
    document.getElementById('passwordLoginForm').classList.remove('d-none');
}

async function biometricLogin(userId) {
    try {
        const options = await fetch(`/webauthn/options.php?user_id=${userId}`)
            .then(res => res.json());

        const credential = await navigator.credentials.get({
            publicKey: options
        });

        const response = await fetch('/webauthn/verify.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(credential)
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = '/dashboard.php';
        } else {
            alert('Biometric authentication failed.');
        }
    } catch (err) {
        console.error(err);
        alert('Biometric login is not available on this device.');
    }
}
</script>

</body>
</html>
