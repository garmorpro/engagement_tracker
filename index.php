<?php
require_once 'includes/functions.php';
require_once 'path.php';
require_once 'includes/init.php';

// Fetch active service accounts
$result = $conn->query("
    SELECT `user_id`, `account_name`, `passcode`
    FROM `service_accounts`
    WHERE `status` = 'active'
    ORDER BY `account_name`
");
$accounts = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quick PIN Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #d8d8d8; }
        .card { max-width: 425px; }
        .pin-popup, .register-popup {
            position: fixed;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            box-shadow: 0 0 15px rgba(0,0,0,0.3);
            display: none;
            width: 300px;
            z-index: 1000;
        }
    </style>
</head>
<body>
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card p-3 shadow w-100">
        <h5 class="text-center mb-2">Welcome Back</h5>
        <p class="text-center text-muted mb-3">Click an account to sign in</p>

        <!-- Account List -->
        <?php if (!empty($accounts)): ?>
            <div class="list-group mb-3">
                <?php foreach ($accounts as $account): ?>
                    <button type="button"
                            class="list-group-item list-group-item-action"
                            data-user-id="<?= $account['user_id'] ?>"
                            data-account-name="<?= htmlspecialchars($account['account_name']) ?>"
                            onclick="openPinPopup(this)">
                        <?= htmlspecialchars($account['account_name']) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No accounts available</p>
        <?php endif; ?>

        <div class="d-grid">
            <button type="button" class="btn btn-primary" onclick="openAdminPinPopup()">Register New Account</button>
        </div>
    </div>
</div>

<!-- PIN Popup -->
<div class="pin-popup" id="pinPopup">
    <h6 id="popupAccountName" class="text-center mb-3"></h6>
    <form id="pinForm" method="POST" action="<?= BASE_URL ?>/auth/login.php">
        <input type="hidden" name="user_id" id="pinUserId">
        <div class="mb-3">
            <label for="pinInput" class="form-label">Enter 4-digit PIN</label>
            <input type="password" maxlength="4" pattern="\d{4}" class="form-control text-center fs-4" id="pinInput" name="passcode" required autofocus>
        </div>
    </form>
</div>

<!-- Admin PIN Popup -->
<div class="pin-popup" id="adminPinPopup">
    <h6 class="text-center mb-3">Enter Admin PIN</h6>
    <form id="adminPinForm">
        <div class="mb-3">
            <input type="password" maxlength="4" pattern="\d{4}" class="form-control text-center fs-4" id="adminPinInput" required autofocus>
        </div>
    </form>
</div>

<!-- Register Account Popup -->
<div class="register-popup" id="registerPopup">
    <h6 class="text-center mb-3">Create New Account</h6>
    <form id="registerForm" method="POST" action="<?= BASE_URL ?>/auth/register.php">
        <div class="mb-3">
            <label class="form-label">Account Name</label>
            <input type="text" class="form-control" name="account_name" required>
        </div>
        <div class="mb-3">
            <label class="form-label">4-digit PIN</label>
            <input type="password" maxlength="4" pattern="\d{4}" class="form-control text-center" name="passcode" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Role</label>
            <select class="form-select" name="role" required>
                <option value="standard">Standard</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="d-grid">
            <button type="submit" class="btn btn-success">Create Account</button>
        </div>
    </form>
</div>

<script>
function openPinPopup(btn) {
    const userId = btn.dataset.userId;
    const accountName = btn.dataset.accountName;

    document.getElementById('popupAccountName').innerText = accountName;
    document.getElementById('pinUserId').value = userId;

    const popup = document.getElementById('pinPopup');
    popup.style.display = 'block';

    const pinInput = document.getElementById('pinInput');
    pinInput.value = '';
    pinInput.focus();
}

// Auto-submit when 4 digits entered
document.getElementById('pinInput').addEventListener('input', function() {
    if (this.value.length === 4) {
        document.getElementById('pinForm').submit();
    }
});

// Open admin PIN popup for registration
function openAdminPinPopup() {
    const popup = document.getElementById('adminPinPopup');
    popup.style.display = 'block';
    const input = document.getElementById('adminPinInput');
    input.value = '';
    input.focus();
}

// Handle admin PIN submission
document.getElementById('adminPinInput').addEventListener('input', function() {
    if (this.value.length === 4) {
        const adminPin = this.value;
        // Replace this with actual admin PIN verification from DB
        fetch('<?= BASE_URL ?>/auth/verify_admin_pin.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({passcode: adminPin})
        })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                document.getElementById('adminPinPopup').style.display = 'none';
                document.getElementById('registerPopup').style.display = 'block';
            } else {
                alert('Invalid Admin PIN');
                this.value = '';
                this.focus();
            }
        })
        .catch(() => alert('Error verifying admin PIN'));
    }
});

// Optional: click outside popup to close
window.addEventListener('click', function(e) {
    const pinPopup = document.getElementById('pinPopup');
    const adminPopup = document.getElementById('adminPinPopup');
    const regPopup = document.getElementById('registerPopup');
    if (e.target === pinPopup) pinPopup.style.display = 'none';
    if (e.target === adminPopup) adminPopup.style.display = 'none';
    if (e.target === regPopup) regPopup.style.display = 'none';
});
</script>
</body>
</html>
