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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

/* Dashboard list styling */
.dashboard-user-item {
    background: var(--bg-tertiary);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.75rem;
    transition: all 0.2s ease;
}

.dashboard-user-item:hover {
    background: var(--border-color);
    border-color: var(--primary-blue);
}

.dashboard-user-item .account-icon {
    width: 40px;
    height: 40px;
    font-size: 20px;
}

.dashboard-user-info {
    flex: 1;
}

.dashboard-user-name {
    font-size: 13px;
    font-weight: 600;
    color: white;
    margin-bottom: 0.25rem;
}

.dashboard-user-email {
    font-size: 12px;
    color: var(--text-secondary);
}

.user-actions {
    display: flex;
    gap: 0.5rem;
}

.user-actions button {
    background: none;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 16px;
    padding: 0.5rem;
    transition: color 0.2s;
}

.user-actions button:hover {
    color: var(--primary-blue);
}

.user-actions button.delete:hover {
    color: var(--danger-red);
}

/* SweetAlert2 Dark Theme Customization */
.swal2-popup {
    background: var(--bg-secondary) !important;
    border: 1px solid var(--border-color) !important;
}

.swal2-title {
    color: white !important;
}

.swal2-html-container {
    color: var(--text-secondary) !important;
}

.swal2-confirm {
    background: var(--primary-blue) !important;
    box-shadow: 0 4px 12px rgba(68, 135, 252, 0.3) !important;
}

.swal2-confirm:hover {
    background: #3671E0 !important;
}

.swal2-cancel {
    background: var(--bg-tertiary) !important;
    border: 1px solid var(--border-color) !important;
    color: var(--text-secondary) !important;
}

.swal2-cancel:hover {
    background: var(--border-color) !important;
    color: white !important;
}
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
    <button class="add-user-btn" onclick="openAdminVerification()" title="Admin Panel">
        <i class="bi bi-shield-fill"></i>
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
                        <div class="account-email"><?= htmlspecialchars(strtolower(str_replace(' ', '.', $account['account_name'])) . '@company.com') ?></div>
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
            <input type="hidden" name="passcode" id="pinFormPasscode">
            <label class="form-label">PIN</label>
            <input type="text" class="form-control text-center fs-4 pin-field" 
                   id="pinInput" required autofocus>
        </form>
    </div>
</div>

<!-- Admin Verification Modal (PIN Entry) -->
<div class="modal-overlay" id="adminVerifyModal">
    <div class="modal-box" style="max-width: 500px;">
        <button class="modal-close" onclick="closeAdminVerify()">×</button>
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="bi bi-shield-fill"></i>
            </div>
            <h5>Admin Verification</h5>
        </div>
        
        <label class="form-label" style="display: block;">Enter Super Admin PIN</label>
        <input type="text" class="form-control text-center fs-4 pin-field" 
               id="adminVerifyPinInput" required autofocus>
        <p style="font-size: 12px; color: var(--text-secondary); margin-top: 1rem;">Demo Super Admin PIN: <strong style="color: var(--teal);">000000</strong></p>
    </div>
</div>

<!-- Admin Dashboard Modal (User Management) -->
<div class="modal-overlay" id="adminDashboardModal">
    <div class="modal-box" style="max-width: 600px;">
        <button class="modal-close" onclick="closeAdminDashboard()">×</button>
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="bi bi-shield-fill"></i>
            </div>
            <h5>Admin Dashboard</h5>
        </div>
        
        <div style="margin-bottom: 1.5rem;">
            <button type="button" class="btn btn-primary" style="width: 100%;" onclick="openAddUserModal()">
                <i class="bi bi-person-fill-add"></i> Add New User
            </button>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <h6 style="font-size: 14px; color: var(--text-secondary); margin-bottom: 0.75rem;">ACTIVE USERS</h6>
        </div>
        
        <div id="accountsList" style="max-height: 400px; overflow-y: auto;">
            <!-- Accounts will be dynamically populated here -->
        </div>
    </div>
</div>

<!-- Add New User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-box" style="max-width: 500px;">
        <button class="modal-close" onclick="closeAddUserModal()">×</button>
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="bi bi-person-fill-add"></i>
            </div>
            <h5>Add New User</h5>
        </div>
        
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
                       name="passcode" maxlength="4" required>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Add User</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal-box" style="max-width: 500px;">
        <button class="modal-close" onclick="closeEditUserModal()">×</button>
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="bi bi-pencil-square"></i>
            </div>
            <h5>Edit User</h5>
        </div>
        
        <form id="editForm" method="POST" action="<?= BASE_URL ?>/auth/update_account.php">
            <input type="hidden" name="user_id" id="editUserId">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" id="editAccountName" name="account_name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" id="editEmail" name="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">4-Digit PIN</label>
                <input type="text" class="form-control text-center pin-field" 
                       id="editPasscode" name="passcode" maxlength="4" required>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<script>
const pinInputs = {};

function setupPinMasking(inputId, maxLength = 4) {
    const input = document.getElementById(inputId);
    if (!input) return;
    
    pinInputs[inputId] = '';
    
    input.addEventListener('keydown', function(e) {
        const key = e.key;
        
        // Allow backspace
        if (key === 'Backspace') {
            e.preventDefault();
            pinInputs[inputId] = pinInputs[inputId].slice(0, -1);
            e.target.value = '•'.repeat(pinInputs[inputId].length);
            return;
        }
        
        // Only allow numbers
        if (!/\d/.test(key)) {
            e.preventDefault();
            return;
        }
        
        // Don't exceed max length
        if (pinInputs[inputId].length >= maxLength) {
            e.preventDefault();
            return;
        }
        
        e.preventDefault();
        
        // Add the digit
        pinInputs[inputId] += key;
        e.target.value = '•'.repeat(pinInputs[inputId].length);
        
        // Auto-submit PIN form when 4 digits entered
        if (inputId === 'pinInput' && pinInputs[inputId].length === 4) {
            const passcodeField = document.getElementById('pinFormPasscode');
            passcodeField.value = pinInputs[inputId];
            setTimeout(() => {
                document.getElementById('pinForm').submit();
            }, 100);
        }
        
        // Auto-verify admin PIN when 6 digits entered
        if (inputId === 'adminVerifyPinInput' && pinInputs[inputId].length === 6) {
            verifyAdminPin(pinInputs[inputId]);
        }
    });
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

function openAdminVerification() {
    document.getElementById('adminVerifyModal').classList.add('active');
    document.getElementById('adminVerifyPinInput').value = '';
    pinInputs['adminVerifyPinInput'] = '';
    setupPinMasking('adminVerifyPinInput', 6);
    setTimeout(() => document.getElementById('adminVerifyPinInput').focus(), 100);
}

function closeAdminVerify() {
    document.getElementById('adminVerifyModal').classList.remove('active');
    document.getElementById('adminVerifyPinInput').value = '';
    pinInputs['adminVerifyPinInput'] = '';
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
            closeAdminVerify();
            openAdminDashboard();
        } else {
            alert('Invalid Super Admin PIN');
            document.getElementById('adminVerifyPinInput').value = '';
            pinInputs['adminVerifyPinInput'] = '';
            document.getElementById('adminVerifyPinInput').focus();
        }
    })
    .catch(() => alert('Error verifying Super Admin PIN'));
}

function openAdminDashboard() {
    document.getElementById('adminDashboardModal').classList.add('active');
    loadAccountsList();
}

function closeAdminDashboard() {
    document.getElementById('adminDashboardModal').classList.remove('active');
}

function openAddUserModal() {
    document.getElementById('addUserModal').classList.add('active');
    document.querySelector('#registerForm input[name="account_name"]').focus();
}

function closeAddUserModal() {
    document.getElementById('addUserModal').classList.remove('active');
    document.getElementById('registerForm').reset();
}

function closeEditUserModal() {
    document.getElementById('editUserModal').classList.remove('active');
    document.getElementById('editForm').reset();
}

function loadAccountsList() {
    fetch('<?= BASE_URL ?>/auth/get_accounts.php')
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const accountsList = document.getElementById('accountsList');
            accountsList.innerHTML = '';
            
            if (data.accounts.length === 0) {
                accountsList.innerHTML = '<p style="color: var(--text-secondary); font-size: 12px;">No users found</p>';
                return;
            }
            
            data.accounts.forEach(account => {
                const accountEl = document.createElement('div');
                accountEl.className = 'dashboard-user-item';
                accountEl.innerHTML = `
                    <div class="account-icon user">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <div class="dashboard-user-info">
                        <div class="dashboard-user-name">${account.account_name}</div>
                        <div class="dashboard-user-email">${account.email}</div>
                    </div>
                    <div class="user-actions">
                        <button type="button" title="Edit" onclick="editAccount(${account.user_id}, '${account.account_name}')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button type="button" class="delete" title="Delete" onclick="deleteAccount(${account.user_id}, '${account.account_name}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
                accountsList.appendChild(accountEl);
            });
        }
    })
    .catch(() => alert('Error loading accounts'));
}

function editAccount(userId, accountName) {
    // Fetch account details from server
    fetch('<?= BASE_URL ?>/auth/get_account_details.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({user_id: userId})
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const account = data.account;
            document.getElementById('editUserId').value = account.user_id;
            document.getElementById('editAccountName').value = account.account_name;
            document.getElementById('editEmail').value = account.email;
            document.getElementById('editPasscode').value = account.passcode;
            document.getElementById('editUserModal').classList.add('active');
            setTimeout(() => document.getElementById('editAccountName').focus(), 100);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load user details',
                background: 'var(--bg-secondary)',
                color: 'white'
            });
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error loading user details',
            background: 'var(--bg-secondary)',
            color: 'white'
        });
    });
}

function deleteAccount(userId, accountName) {
    Swal.fire({
        title: 'Delete User?',
        text: `Are you sure you want to delete "${accountName}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--danger-red)',
        cancelButtonColor: 'var(--border-color)',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        background: 'var(--bg-secondary)',
        color: 'white',
        customClass: {
            popup: 'swal2-dark',
            title: 'swal2-title-custom',
            htmlContainer: 'swal2-html-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('<?= BASE_URL ?>/auth/delete_account.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({user_id: userId})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Deleted!',
                        text: 'Account deleted successfully',
                        background: 'var(--bg-secondary)',
                        color: 'white',
                        confirmButtonColor: 'var(--primary-blue)'
                    }).then(() => {
                        // Refresh page to close all modals
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error deleting account',
                        background: 'var(--bg-secondary)',
                        color: 'white',
                        confirmButtonColor: 'var(--primary-blue)'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error deleting account',
                    background: 'var(--bg-secondary)',
                    color: 'white',
                    confirmButtonColor: 'var(--primary-blue)'
                });
            });
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setupPinMasking('pinInput', 4);
});

// Close modals when clicking outside
window.addEventListener('click', function(e){
    if(e.target === document.getElementById('pinModal')) closePinModal();
    if(e.target === document.getElementById('adminVerifyModal')) closeAdminVerify();
    if(e.target === document.getElementById('addUserModal')) closeAddUserModal();
    if(e.target === document.getElementById('editUserModal')) closeEditUserModal();
    if(e.target === document.getElementById('adminDashboardModal')) closeAdminDashboard();
});
</script>

</body>
</html>