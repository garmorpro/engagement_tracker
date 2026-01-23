<?php
require_once 'includes/functions.php';
require_once 'path.php';
require_once 'includes/init.php';

// Fetch active service accounts
$result = $conn->query("
    SELECT `user_id`, `account_name`, `passcode`, `role`
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
body {
    background: linear-gradient(135deg, #d8e2ec, #f0f4f8);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.card {
    max-width: 450px;
    border-radius: 1rem;
    padding: 2rem;
    box-shadow: 0 12px 30px rgba(0,0,0,0.12);
    background: #ffffffee;
}

.account-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.account-item {
    background: #f8f9fa;
    border-radius: 0.75rem;
    padding: 1rem;
    text-align: center;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
    box-shadow: 0 4px 8px rgba(0,0,0,0.05);
}

.account-item:hover {
    background: #e0f0ff;
    transform: translateY(-2px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.1);
}

.account-item .role-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.register-btn {
    background: linear-gradient(to right, #3b82f6, #06b6d4);
    color: white;
    font-weight: 600;
    border-radius: 0.75rem;
    padding: 0.75rem;
    transition: all 0.2s ease-in-out;
    margin-top: 1rem;
}

.register-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.pin-popup, .register-popup {
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 2rem;
    border-radius: 0.75rem;
    box-shadow: 0 0 25px rgba(0,0,0,0.3);
    display: none;
    width: 350px;
    z-index: 1000;
}
</style>
</head>
<body>
<div class="container h-100 d-flex justify-content-center align-items-center" style="min-height: 100vh;">
    <div class="card w-100">
        <h4 class="text-center mb-3">Welcome Back</h4>
        <p class="text-center text-muted mb-4">Click an account to sign in</p>

        <!-- Account List -->
        <?php if (!empty($accounts)): ?>
            <div class="account-list">
                <?php foreach ($accounts as $account): ?>
                    <div class="account-item"
                         data-user-id="<?= $account['user_id'] ?>"
                         data-account-name="<?= htmlspecialchars($account['account_name']) ?>"
                         data-role="<?= $account['role'] ?>"
                         onclick="openPinPopup(this)">
                        <i class="bi <?= $account['role'] === 'super_admin' ? 'bi-shield-lock-fill text-danger' : 'bi-person-circle text-primary' ?> role-icon"></i>
                        <div><?= htmlspecialchars($account['account_name']) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-muted">No accounts available</p>
        <?php endif; ?>

        <div class="d-grid">
            <button type="button" class="register-btn" onclick="openAdminPinPopup()">
                <i class="bi bi-person-plus-fill me-2"></i>Register New Account
            </button>
        </div>
    </div>
</div>

<!-- PIN & Register Popups -->
<div class="pin-popup" id="pinPopup">
    <h6 id="popupAccountName" class="text-center mb-3"></h6>
    <form id="pinForm" method="POST" action="<?= BASE_URL ?>/auth/login.php">
        <input type="hidden" name="user_id" id="pinUserId">
        <div class="mb-3">
            <label for="pinInput" class="form-label" id="pinLabel">Enter PIN</label>
            <input type="password" maxlength="4" pattern="\d{4}" class="form-control text-center fs-4" id="pinInput" name="passcode" required autofocus>
        </div>
    </form>
</div>

<div class="pin-popup" id="adminPinPopup">
    <h6 class="text-center mb-3">Enter Super Admin PIN</h6>
    <form id="adminPinForm">
        <div class="mb-3">
            <input type="password" maxlength="6" pattern="\d{6}" class="form-control text-center fs-4" id="adminPinInput" required autofocus>
        </div>
    </form>
</div>

<div class="register-popup" id="registerPopup">
    <h6 class="text-center mb-3">Create New Account</h6>
    <form id="registerForm" method="POST" action="<?= BASE_URL ?>/auth/register.php">
        <!-- Name Field -->
        <div class="mb-3">
            <label class="form-label">Full Name</label>
            <input type="text" class="form-control" name="name" placeholder="John Doe" required>
        </div>

        <!-- Account Name Field -->
        <div class="mb-3">
            <label class="form-label">Account Name</label>
            <input type="text" class="form-control" name="account_name" required>
        </div>

        <!-- PIN Field -->
        <div class="mb-3">
            <label class="form-label">4-digit PIN</label>
            <input type="password" maxlength="4" pattern="\d{4}" class="form-control text-center" name="passcode" required>
        </div>

        <!-- Role Field -->
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
// --- Popup Logic ---
function openPinPopup(btn) {
    const userId = btn.dataset.userId;
    const accountName = btn.dataset.accountName;
    const role = btn.dataset.role;

    document.getElementById('popupAccountName').innerText = accountName;
    document.getElementById('pinUserId').value = userId;

    const pinInput = document.getElementById('pinInput');
    const pinLabel = document.getElementById('pinLabel');
    if(role === 'super_admin'){
        pinInput.maxLength = 6;
        pinInput.pattern = "\\d{6}";
        pinLabel.innerText = "Enter 6-digit PIN";
    } else {
        pinInput.maxLength = 4;
        pinInput.pattern = "\\d{4}";
        pinLabel.innerText = "Enter 4-digit PIN";
    }

    pinInput.value = '';
    pinInput.focus();
    document.getElementById('pinPopup').style.display = 'block';
}

document.getElementById('pinInput').addEventListener('input', function() {
    if(this.value.length == this.maxLength){
        document.getElementById('pinForm').submit();
    }
});

function openAdminPinPopup() {
    const popup = document.getElementById('adminPinPopup');
    const input = document.getElementById('adminPinInput');
    input.value = '';
    input.focus();
    popup.style.display = 'block';
}

document.getElementById('adminPinInput').addEventListener('input', function(){
    if(this.value.length === 6){
        const adminPin = this.value;
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
                alert('Invalid Super Admin PIN');
                this.value = '';
                this.focus();
            }
        })
        .catch(() => alert('Error verifying Super Admin PIN'));
    }
});

// Close popups when clicking outside
window.addEventListener('click', function(e){
    ['pinPopup','adminPinPopup','registerPopup'].forEach(id => {
        const popup = document.getElementById(id);
        if(e.target === popup) popup.style.display = 'none';
    });
});
</script>
</body>
</html>
