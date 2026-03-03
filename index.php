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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - Engagement Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary-blue: #4487FC;
    --danger-red: #C90012;
    --info-purple: #A04DFD;
    --teal: #4DBFB8;
    --bg-primary: #0f1419;
    --bg-secondary: #1A2332;
    --bg-tertiary: #2A3A4D;
    --border-color: #2D3847;
    --text-secondary: #6A7382;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    background: linear-gradient(135deg, #0f1419 0%, #1a2332 100%);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: white;
}

.login-container { text-align: center; margin-bottom: 2rem; }
.logo-icon {
    width: 60px; height: 60px;
    background: linear-gradient(135deg, var(--primary-blue) 0%, #2196F3 100%);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 28px;
    margin: 0 auto 1.5rem;
    box-shadow: 0 4px 16px rgba(33, 150, 243, 0.4);
}

.login-container h1 {
    font-size: 32px; font-weight: 700;
    margin-bottom: 0.5rem; color: white;
}

.login-container p {
    font-size: 14px; color: var(--text-secondary);
}

.login-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 2rem;
    width: 100%;
    max-width: 800px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
    position: relative;
}

.card-header {
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.card-header-icon {
    width: 48px; height: 48px;
    background: rgba(68, 135, 252, 0.15);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: var(--primary-blue);
    font-size: 24px;
}

.card-header h6 {
    font-size: 18px; font-weight: 600;
    color: white; margin: 0;
}

.add-user-btn {
    position: absolute; top: 1rem; right: 1rem;
    width: 36px; height: 36px;
    border: 1px solid var(--border-color);
    background: transparent; border-radius: 8px;
    cursor: pointer; color: var(--primary-blue);
    transition: all 0.2s; font-size: 18px;
}

.add-user-btn:hover {
    background: rgba(68, 135, 252, 0.1);
    border-color: var(--primary-blue);
    transform: scale(1.1);
}

.account-list { display: flex; flex-direction: column; gap: 1rem; }

.account-item {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 1rem 1.25rem;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex; align-items: center; gap: 1rem;
}

.account-item:hover {
    background: var(--border-color);
    border-color: var(--primary-blue);
    transform: translateX(4px);
    box-shadow: 0 8px 24px rgba(68, 135, 252, 0.15);
}

.account-icon {
    width: 48px; height: 48px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px; flex-shrink: 0;
}

.account-icon.user {
    background: rgba(68, 135, 252, 0.15);
    color: var(--primary-blue);
}

.account-icon.admin {
    background: rgba(201, 0, 18, 0.15);
    color: var(--danger-red);
}

.account-info { flex: 1; text-align: left; }
.account-name { font-size: 14px; font-weight: 600; color: white; margin-bottom: 0.25rem; }
.account-email { font-size: 12px; color: var(--text-secondary); }

.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    display: none; align-items: center; justify-content: center;
    z-index: 1000;
}

.modal-overlay.active { display: flex; }

.modal-box {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 2rem;
    width: 90%;
    max-width: 450px;
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.5);
    position: relative;
}

.modal-close {
    position: absolute; top: 1rem; right: 1rem;
    width: 32px; height: 32px;
    border: none; background: transparent;
    color: var(--text-secondary); font-size: 24px;
    cursor: pointer; transition: color 0.2s;
    padding: 0; line-height: 1;
    display: flex; align-items: center; justify-content: center;
}

.modal-close:hover { color: white; }

.modal-header {
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--border-color);
}

.modal-header-icon {
    width: 44px; height: 44px;
    background: rgba(68, 135, 252, 0.15);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: var(--primary-blue);
    font-size: 22px;
    flex-shrink: 0;
}

.modal-header h5 {
    font-size: 18px; font-weight: 600;
    color: white; margin: 0; flex: 1;
}

.form-label {
    font-size: 13px; color: var(--text-secondary);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-bottom: 0.5rem; font-weight: 600;
}

.form-control {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    color: white;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 14px;
}

.form-control::placeholder { color: var(--text-secondary); }

.form-control:focus {
    background: var(--bg-tertiary);
    border-color: var(--primary-blue);
    color: white;
    box-shadow: 0 0 0 3px rgba(68, 135, 252, 0.1);
}

.pin-field {
    letter-spacing: 0.2em;
    font-family: 'Courier New', monospace;
    font-size: 24px !important;
    font-weight: 600;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-blue) 0%, #3671E0 100%);
    border: none; color: white;
    font-weight: 600; border-radius: 8px;
    padding: 0.75rem 1.5rem;
    transition: all 0.2s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(68, 135, 252, 0.3);
    background: linear-gradient(135deg, #3671E0 0%, #2857B8 100%);
}

.btn-secondary {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    font-weight: 600; border-radius: 8px;
    padding: 0.75rem 1.5rem;
    transition: all 0.2s;
}

.btn-secondary:hover {
    background: var(--border-color);
    color: white;
}

.button-group {
    display: flex; gap: 1rem;
    margin-top: 1.5rem;
}

.button-group button { flex: 1; }

.demo-credentials {
    background: rgba(77, 191, 184, 0.1);
    border: 1px solid var(--teal);
    border-radius: 10px;
    padding: 1.25rem;
    margin-top: 2rem;
    text-align: left;
    width: 100%;
    max-width: 800px;
}

.demo-credentials h6 {
    color: var(--teal);
    font-size: 13px;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.demo-credentials p {
    font-size: 12px;
    color: var(--text-secondary);
    margin: 0.25rem 0;
}

.demo-credentials strong { color: var(--teal); }

.mb-3 { margin-bottom: 1rem; }
</style>
</head>
<body>

<div class="login-container">
    <div class="logo-icon">
        <i class="bi bi-bar-chart-fill"></i>
    </div>
    <h1>Engagement Tracker</h1>
    <p>Select your account to sign in</p>
</div>

<div class="login-card">
    <div class="card-header">
        <div class="card-header-icon">
            <i class="bi bi-person-circle"></i>
        </div>
        <h6>Select Account</h6>
    </div>
    <button class="add-user-btn" onclick="openAddUserModal()" title="Add New User">
        <i class="bi bi-person-fill-add"></i>
    </button>

    <?php if (!empty($accounts)): ?>
        <div class="account-list">
            <?php foreach ($accounts as $account): ?>
                <?php if ($account['role'] === 'super_admin') continue; ?>
                <div class="account-item"
                     data-user-id="<?= $account['user_id'] ?>"
                     data-account-name="<?= htmlspecialchars($account['account_name']) ?>"
                     data-role="<?= $account['role'] ?>"
                     onclick="openPinModal(this)">
                    <div class="account-icon user">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="account-info">
                        <div class="account-name"><?= htmlspecialchars($account['account_name']) ?></div>
                        <div class="account-email"><?= strtolower(str_replace(' ', '.', htmlspecialchars($account['account_name']))) . '@company.com' ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--text-secondary);">No accounts available</p>
    <?php endif; ?>
</div>

<div class="demo-credentials">
    <h6>Demo Credentials:</h6>
    <p>• John Doe: PIN <strong>1234</strong></p>
    <p>• Jane Smith: PIN <strong>5678</strong></p>
    <p>• Bob Wilson: PIN <strong>9012</strong></p>
    <p>• Sarah Johnson: PIN <strong>3456</strong></p>
</div>

<!-- PIN Entry Modal -->
<div class="modal-overlay" id="pinModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closePinModal()">×</button>
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="bi bi-lock-fill"></i>
            </div>
            <h5 id="modalAccountName">Enter PIN</h5>
        </div>
        <form id="pinForm" method="POST" action="<?= BASE_URL ?>/auth/login.php">
            <input type="hidden" name="user_id" id="pinUserId">
            <label class="form-label">PIN</label>
            <input type="text" class="form-control text-center fs-4 pin-field" 
                   id="pinInput" required autofocus>
        </form>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeAddUserModal()">×</button>
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="bi bi-person-fill-add"></i>
            </div>
            <h5>Add New User</h5>
        </div>
        
        <div id="adminPinStep">
            <label class="form-label" style="display: block;">Enter Super Admin PIN</label>
            <input type="text" class="form-control text-center fs-4 pin-field" 
                   id="adminPinInput" required autofocus>
            <p style="font-size: 12px; color: var(--text-secondary); margin-top: 1rem;">Demo Super Admin PIN: <strong style="color: var(--teal);">000000</strong></p>
        </div>

        <div id="registerStep" style="display: none;">
            <form id="registerForm" method="POST" action="<?= BASE_URL ?>/auth/register.php">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" class="form-control" name="account_name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">4-Digit PIN</label>
                    <input type="text" class="form-control text-center pin-field" 
                           name="passcode" required>
                </div>
                <div class="button-group">
                    <button type="button" class="btn btn-secondary" onclick="goBackToAdminPin()">Back</button>
                    <button type="submit" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
const pinInputs = {};

function setupPinMasking(inputId) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    pinInputs[inputId] = '';
    const maxLength = inputId === 'adminPinInput' ? 6 : 4;
    
    input.addEventListener('beforeinput', function(e) {
        // Allow deletion
        if (['deleteContentBackward', 'deleteContentForward'].includes(e.inputType)) {
            return;
        }
        // Only allow numeric
        if (!/\d/.test(e.data)) {
            e.preventDefault();
        }
    });
    
    input.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        value = value.slice(0, maxLength);
        pinInputs[inputId] = value;
        e.target.value = '•'.repeat(value.length);
        
        if (inputId === 'pinInput' && value.length === 4) {
            document.getElementById('pinForm').passcode = document.createElement('input');
            document.getElementById('pinForm').passcode.type = 'hidden';
            document.getElementById('pinForm').passcode.name = 'passcode';
            document.getElementById('pinForm').passcode.value = value;
            document.getElementById('pinForm').appendChild(document.getElementById('pinForm').passcode);
            setTimeout(() => document.getElementById('pinForm').submit(), 50);
        }
        
        if (inputId === 'adminPinInput' && value.length === 6) {
            verifyAdminPin(value);
        }
    });
}

function verifyAdminPin(pin) {
    fetch('<?= BASE_URL ?>/auth/verify_admin_pin.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({passcode: pin})
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            document.getElementById('adminPinStep').style.display = 'none';
            document.getElementById('registerStep').style.display = 'block';
            document.querySelector('#registerForm input[name="account_name"]').focus();
        } else {
            alert('Invalid Super Admin PIN');
            document.getElementById('adminPinInput').value = '';
            pinInputs['adminPinInput'] = '';
            document.getElementById('adminPinInput').focus();
        }
    })
    .catch(() => alert('Error verifying Super Admin PIN'));
}

function openPinModal(btn) {
    const userId = btn.dataset.userId;
    const accountName = btn.dataset.accountName;
    document.getElementById('modalAccountName').innerText = accountName;
    document.getElementById('pinUserId').value = userId;
    document.getElementById('pinInput').value = '';
    pinInputs['pinInput'] = '';
    document.getElementById('pinModal').classList.add('active');
    setTimeout(() => document.getElementById('pinInput').focus(), 100);
}

function closePinModal() {
    document.getElementById('pinModal').classList.remove('active');
    document.getElementById('pinInput').value = '';
    pinInputs['pinInput'] = '';
}

function openAddUserModal() {
    document.getElementById('addUserModal').classList.add('active');
    document.getElementById('adminPinInput').value = '';
    pinInputs['adminPinInput'] = '';
    document.getElementById('adminPinStep').style.display = 'block';
    document.getElementById('registerStep').style.display = 'none';
    setTimeout(() => document.getElementById('adminPinInput').focus(), 100);
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.remove('active');
    document.getElementById('adminPinInput').value = '';
    pinInputs['adminPinInput'] = '';
}

function goBackToAdminPin() {
    document.getElementById('adminPinStep').style.display = 'block';
    document.getElementById('registerStep').style.display = 'none';
    document.getElementById('adminPinInput').value = '';
    pinInputs['adminPinInput'] = '';
    document.getElementById('adminPinInput').focus();
}

document.addEventListener('DOMContentLoaded', function() {
    setupPinMasking('pinInput');
    setupPinMasking('adminPinInput');
});

window.addEventListener('click', function(e){
    if(e.target === document.getElementById('pinModal')) closePinModal();
    if(e.target === document.getElementById('addUserModal')) closeAddUserModal();
});
</script>

</body>
</html>