<?php
require_once 'includes/functions.php';
require_once 'path.php';
require_once 'includes/init.php';

// Fetch active service accounts
$result = $conn->query("
    SELECT *
    FROM `service_accounts`
    WHERE `status` = 'active'
    ORDER BY `name`
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
    --ink: #6E9FCB;
    --paper: #10161D;
    --card: #171F28;
    --line: #2A343E;
    --line-strong: #3C4854;
    --critical: #E5766F;
    --text: #E7ECF1;
    --text-muted: #93A1AF;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    background: linear-gradient(135deg, var(--paper) 0%, var(--card) 100%);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    color: var(--text);
}

.login-container { text-align: center; margin-bottom: 2rem; }
.logo-icon {
    width: 56px; height: 56px;
    background: var(--ink);
    border-radius: 13px;
    display: flex; align-items: center; justify-content: center;
    color: var(--paper); font-size: 26px;
    margin: 0 auto 1.5rem;
    box-shadow: 0 6px 18px color-mix(in srgb, var(--ink) 35%, transparent);
}

.login-container h1 {
    font-size: 30px;
    font-weight: 700;
    letter-spacing: -0.01em;
    margin-bottom: 0.5rem;
    color: var(--text);
}

.login-container p {
    font-size: 13.5px; color: var(--text-muted);
}

.login-card {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 1.75rem;
    width: 100%;
    max-width: 440px;
    box-shadow: 0 10px 32px rgba(0, 0, 0, 0.3);
    position: relative;
}

.card-header {
    display: flex; align-items: center; gap: 0.7rem;
    margin-bottom: 1.4rem;
    padding-bottom: 1.2rem;
    border-bottom: 1px solid var(--line);
}

.card-header-icon {
    width: 38px; height: 38px;
    background: color-mix(in srgb, var(--ink) 12%, transparent);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    color: var(--ink);
    font-size: 17px;
}

.card-header h6 {
    font-size: 15px; font-weight: 700;
    color: var(--text); margin: 0;
}

.add-user-btn {
    position: absolute; top: 1.25rem; right: 1.25rem;
    width: 32px; height: 32px;
    border: 1px solid var(--line);
    background: var(--paper); border-radius: 8px;
    cursor: pointer; color: var(--text-muted);
    transition: all 0.15s; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
}

.add-user-btn:hover {
    background: color-mix(in srgb, var(--critical) 8%, transparent);
    border-color: var(--critical);
    color: var(--critical);
}

.account-list { display: flex; flex-direction: column; gap: 0.6rem; }

.account-item {
    background: transparent;
    border: 1px solid var(--line);
    border-radius: 11px;
    padding: 0.85rem 0.9rem;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex; align-items: center; gap: 0.85rem;
}

.account-item:hover {
    background: color-mix(in srgb, var(--ink) 5%, transparent);
    border-color: var(--ink);
    transform: translateX(2px);
}

.account-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; flex-shrink: 0;
}

.account-icon.user {
    background: var(--ink);
    color: var(--paper);
}

.account-icon.admin {
    background: var(--critical);
    color: var(--paper);
}

.account-info { flex: 1; text-align: left; }
.account-name { font-size: 13.5px; font-weight: 700; color: var(--text); margin-bottom: 0.15rem; }
.account-email { font-size: 11.5px; color: var(--text-muted); }

.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(10, 15, 22, 0.55);
    display: none; align-items: center; justify-content: center;
    z-index: 1000;
}

.modal-overlay.active { display: flex; }

.modal-box {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 16px;
    padding: 1.9rem;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
    position: relative;
}

.modal-close {
    position: absolute; top: 1rem; right: 1rem;
    width: 30px; height: 30px;
    border: none; background: transparent;
    color: var(--text-muted); font-size: 20px;
    cursor: pointer; transition: all 0.15s;
    padding: 0; line-height: 1; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
}

.modal-close:hover { color: var(--text); background: var(--paper); }

.modal-header {
    display: flex; align-items: center; gap: 0.75rem;
    margin-bottom: 1.4rem;
    padding-bottom: 1.2rem;
    border-bottom: 1px solid var(--line);
}

.modal-header-icon {
    width: 42px; height: 42px;
    background: color-mix(in srgb, var(--ink) 14%, transparent);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    color: var(--ink);
    font-size: 19px;
    flex-shrink: 0;
}

.modal-header-icon.admin {
    background: color-mix(in srgb, var(--critical) 14%, transparent);
    color: var(--critical);
}

.modal-header h5 {
    font-size: 15.5px; font-weight: 700;
    color: var(--text); margin: 0; flex: 1;
}

.modal-subtext {
    font-size: 11.5px; color: var(--text-muted); margin-top: 2px;
}

.form-label {
    font-size: 11.5px; color: var(--text-muted);
    text-transform: uppercase; letter-spacing: 0.5px;
    margin-bottom: 0.5rem; font-weight: 700;
}

.form-control {
    background: var(--paper);
    border: 1px solid var(--line);
    color: var(--text);
    border-radius: 8px;
    padding: 0.75rem 1rem;
    font-size: 14px;
}

.form-control::placeholder { color: var(--text-muted); }

.form-control:focus {
    background: var(--paper);
    border-color: var(--ink);
    color: var(--text);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 10%, transparent);
    outline: none;
}

.pin-field {
    letter-spacing: 0.2em;
    font-family: 'Courier New', monospace;
    font-size: 24px !important;
    font-weight: 600;
    text-align: center;
}

/* PIN dot entry — replaces bullet-masked text for the sign-in and admin-verify
   PINs. The real <input> stays focused/typable but visually hidden; JS toggles
   .filled on these dots as digits are entered. Account-creation/edit PIN
   fields keep the plain .pin-field text input above — you're setting a new
   PIN there, not authenticating with one you already know. */
.pin-hidden-input {
    position: absolute; opacity: 0; pointer-events: none; height: 1px; width: 1px;
}
.pin-dots { display: flex; gap: 0.85rem; justify-content: center; margin: 1.6rem 0 0.5rem; }
.pin-dot {
    width: 15px; height: 15px; border-radius: 50%; border: 2px solid var(--line-strong);
    transition: all 0.15s;
}
.pin-dot.filled { background: var(--ink); border-color: var(--ink); transform: scale(1.1); }
.pin-hint { text-align: center; font-size: 11.5px; color: var(--text-muted); }
.admin-note { font-size: 11.5px; color: var(--text-muted); text-align: center; margin-top: 1.1rem; line-height: 1.5; }

.btn-primary {
    background: var(--ink);
    border: none; color: var(--paper);
    font-weight: 700; border-radius: 8px;
    padding: 0.75rem 1.5rem;
    transition: all 0.15s;
}

.btn-primary:hover {
    background: color-mix(in srgb, var(--ink) 85%, black);
    box-shadow: 0 4px 16px color-mix(in srgb, var(--ink) 30%, transparent);
}

.btn-secondary {
    background: var(--paper);
    border: 1px solid var(--line);
    color: var(--text-muted);
    font-weight: 700; border-radius: 8px;
    padding: 0.75rem 1.5rem;
    transition: all 0.15s;
}

.btn-secondary:hover {
    background: var(--line);
    color: var(--text);
}

.button-group {
    display: flex; gap: 1rem;
    margin-top: 1.5rem;
}

.button-group button { flex: 1; }

.demo-credentials {
    background: color-mix(in srgb, var(--ink) 6%, var(--card));
    border: 1px solid var(--line);
    border-radius: 11px;
    padding: 1rem 1.15rem;
    margin-top: 1.5rem;
    text-align: center;
    width: 100%;
    max-width: 440px;
}

.demo-credentials h6 {
    color: var(--text);
    font-size: 12px;
    font-weight: 700;
    display: inline;
}

.demo-credentials p {
    font-size: 12px;
    color: var(--text-muted);
    margin: 0.25rem 0 0;
}

.mb-3 { margin-bottom: 1rem; }

/* Dashboard list styling */
.dashboard-user-item {
    background: transparent;
    border: 1px solid var(--line);
    border-radius: 10px;
    padding: 0.7rem 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
    transition: all 0.15s ease;
}

.dashboard-user-item:hover {
    border-color: var(--ink);
    background: color-mix(in srgb, var(--ink) 5%, transparent);
}

.dashboard-user-item .account-icon {
    width: 32px;
    height: 32px;
    font-size: 12px;
}

.dashboard-user-info {
    flex: 1;
}

.dashboard-user-name {
    font-size: 12.5px;
    font-weight: 700;
    color: var(--text);
    margin-bottom: 0.15rem;
}

.dashboard-user-email {
    font-size: 11px;
    color: var(--text-muted);
}

.user-actions {
    display: flex;
    gap: 4px;
}

.user-actions button {
    background: var(--paper);
    border: 1px solid var(--line);
    color: var(--text-muted);
    cursor: pointer;
    font-size: 12px;
    width: 28px; height: 28px;
    border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s;
}

.user-actions button:hover {
    border-color: var(--ink);
    color: var(--ink);
}

.user-actions button.delete:hover {
    border-color: var(--critical);
    color: var(--critical);
}

/* SweetAlert2 Dark Theme Customization */
.swal2-popup {
    background: var(--card) !important;
    border: 1px solid var(--line) !important;
}

.swal2-title {
    color: var(--text) !important;
}

.swal2-html-container {
    color: var(--text-muted) !important;
}

.swal2-confirm {
    background: var(--ink) !important;
    color: var(--paper) !important;
    box-shadow: 0 4px 12px color-mix(in srgb, var(--ink) 30%, transparent) !important;
}

.swal2-confirm:hover {
    background: color-mix(in srgb, var(--ink) 85%, black) !important;
}

.swal2-cancel {
    background: var(--paper) !important;
    border: 1px solid var(--line) !important;
    color: var(--text-muted) !important;
}

.swal2-cancel:hover {
    background: var(--line) !important;
    color: var(--text) !important;
}

.timeout-alert {
    background: color-mix(in srgb, var(--critical) 10%, transparent);
    border: 1px solid var(--critical);
    color: var(--critical);
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-size: 13px;
    margin-bottom: 1.5rem;
    text-align: center;
    font-weight: 600;
    animation: fadeIn 0.4s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-5px); }
    to { opacity: 1; transform: translateY(0); }
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
    <?php if (isset($_GET['timeout'])): ?>
    <div class="timeout-alert">
        You were logged out due to inactivity. Please login again.
    </div>
<?php endif; ?>
</div>

<div class="login-card">
    <div class="card-header">
        <div class="card-header-icon">
            <i class="bi bi-person-circle"></i>
        </div>
        <h6>Select Account</h6>
    </div>
    <button class="add-user-btn" onclick="openAdminVerification()" title="Admin access">
        <i class="bi bi-shield-fill"></i>
    </button>

    <?php if (!empty($accounts)): ?>
        <div class="account-list">
            <?php foreach ($accounts as $account): ?>
                <?php if ($account['role'] === 'super_admin') continue; ?>
                <?php
                    $accountInitials = '';
                    foreach (explode(' ', trim($account['name'])) as $part) {
                        if ($part !== '') $accountInitials .= strtoupper($part[0]);
                    }
                ?>
                <div class="account-item"
                     data-user-id="<?= $account['user_id'] ?>"
                     data-account-name="<?= htmlspecialchars($account['name']) ?>"
                     data-role="<?= $account['role'] ?>"
                     onclick="openPinModal(this)">
                    <div class="account-icon user"><?= htmlspecialchars($accountInitials) ?></div>
                    <div class="account-info">
                        <div class="account-name"><?= htmlspecialchars($account['name']) ?></div>
                        <div class="account-email"><?= htmlspecialchars($account['email']) ?></div>
                    </div>
                    <i class="bi bi-chevron-right" style="color: var(--text-muted); font-size: 13px;"></i>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: var(--text-muted);">No accounts available</p>
    <?php endif; ?>
</div>

<div class="demo-credentials">
    <h6>Sign in:</h6>
    <p>Select your account, then enter your PIN.</p>
</div>

<!-- PIN Entry Modal -->
<div class="modal-overlay" id="pinModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closePinModal()">×</button>
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="bi bi-lock-fill"></i>
            </div>
            <div>
                <h5 id="modalAccountName">Enter PIN</h5>
                <div class="modal-subtext">Enter your 4-digit PIN</div>
            </div>
        </div>
        <form id="pinForm" method="POST" action="<?= BASE_URL ?>/auth/login.php">
            <input type="hidden" name="user_id" id="pinUserId">
            <input type="hidden" name="passcode" id="pinFormPasscode">
            <input type="text" class="pin-hidden-input" id="pinInput" inputmode="numeric" autocomplete="off" required autofocus>
            <div class="pin-dots" id="pinDots">
                <div class="pin-dot"></div>
                <div class="pin-dot"></div>
                <div class="pin-dot"></div>
                <div class="pin-dot"></div>
            </div>
            <div class="pin-hint">Type your PIN — it submits automatically</div>
        </form>
    </div>
</div>

<!-- Admin Verification Modal (PIN Entry) -->
<div class="modal-overlay" id="adminVerifyModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeAdminVerify()">×</button>
        <div class="modal-header">
            <div class="modal-header-icon admin">
                <i class="bi bi-shield-fill"></i>
            </div>
            <div>
                <h5>Admin Verification</h5>
                <div class="modal-subtext">Required to manage accounts</div>
            </div>
        </div>

        <input type="text" class="pin-hidden-input" id="adminVerifyPinInput" inputmode="numeric" autocomplete="off" required autofocus>
        <div class="pin-dots" id="adminPinDots">
            <div class="pin-dot"></div>
            <div class="pin-dot"></div>
            <div class="pin-dot"></div>
            <div class="pin-dot"></div>
            <div class="pin-dot"></div>
            <div class="pin-dot"></div>
        </div>
        <div class="pin-hint">Enter your 6-digit super-admin PIN</div>
        <p class="admin-note">
            This unlocks the account management screen. It is not part of everyday sign-in.
        </p>
    </div>
</div>

<!-- Admin Dashboard Modal (User Management) -->
<div class="modal-overlay" id="adminDashboardModal">
    <div class="modal-box" style="max-width: 480px;">
        <button class="modal-close" onclick="closeAdminDashboard()">×</button>
        <div class="modal-header">
            <div class="modal-header-icon admin">
                <i class="bi bi-shield-fill"></i>
            </div>
            <div>
                <h5>Admin Dashboard</h5>
                <div class="modal-subtext">Manage sign-in accounts</div>
            </div>
        </div>

        <div style="margin-bottom: 1.5rem;">
            <button type="button" class="btn btn-primary" style="width: 100%;" onclick="openAddUserModal()">
                <i class="bi bi-person-fill-add"></i> Add New User
            </button>
        </div>

        <div style="margin-bottom: 0.6rem;">
            <h6 style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted);">Active Users</h6>
        </div>

        <div id="accountsList" style="max-height: 400px; overflow-y: auto;">
            <!-- Accounts will be dynamically populated here -->
        </div>

        <div style="margin: 1.5rem 0 0.6rem; padding-top: 1.25rem; border-top: 1px solid var(--line);">
            <h6 style="font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: var(--text-muted); margin: 0 0 0.6rem;">Slack Notifications</h6>
            <label class="form-label">Incoming Webhook URL</label>
            <input type="text" class="form-control" id="slackWebhookInput" placeholder="https://hooks.slack.com/services/...">
            <p style="font-size: 11px; color: var(--text-muted); margin: 0.5rem 0 0;">Leave blank to turn Slack notifications off. Applies to upcoming key dates, upcoming milestones, and ready-to-archive alerts.</p>
            <div class="button-group">
                <button type="button" class="btn btn-secondary" id="slackTestBtn">Send Test</button>
                <button type="button" class="btn btn-primary" id="slackSaveBtn">Save</button>
            </div>
            <p id="slackStatusMsg" style="font-size: 11.5px; margin: 0.6rem 0 0; display: none;"></p>
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
                <input type="text" class="form-control" name="name" required>
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
        
        <form id="editForm" onsubmit="submitEditForm(event)">
            <input type="hidden" name="user_id" id="editUserId">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" id="editAccountName" name="name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" class="form-control" id="editEmail" name="email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">4-Digit PIN (leave blank to keep current PIN)</label>
                <input type="text" class="form-control text-center pin-field"
                       id="editPasscode" name="passcode" maxlength="4" placeholder="••••">
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
const pinDotsMap = { pinInput: 'pinDots', adminVerifyPinInput: 'adminPinDots' };

function updatePinDots(inputId, length) {
    const dotsId = pinDotsMap[inputId];
    if (!dotsId) return;
    document.querySelectorAll('#' + dotsId + ' .pin-dot').forEach((dot, i) => {
        dot.classList.toggle('filled', i < length);
    });
}

function setupPinMasking(inputId, maxLength = 4) {
    const input = document.getElementById(inputId);
    if (!input) return;

    pinInputs[inputId] = '';
    updatePinDots(inputId, 0);

    input.addEventListener('keydown', function(e) {
        const key = e.key;

        // Allow backspace
        if (key === 'Backspace') {
            e.preventDefault();
            pinInputs[inputId] = pinInputs[inputId].slice(0, -1);
            updatePinDots(inputId, pinInputs[inputId].length);
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
        updatePinDots(inputId, pinInputs[inputId].length);

        // Auto-submit PIN form when 4 digits entered
        if (inputId === 'pinInput' && pinInputs[inputId].length === 4) {
            const passcodeField = document.getElementById('pinFormPasscode');
            passcodeField.value = pinInputs[inputId];
            setTimeout(() => {
                document.getElementById('pinForm').submit();
            }, 150);
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
    updatePinDots('pinInput', 0);
    document.getElementById('pinModal').classList.add('active');
    setTimeout(() => document.getElementById('pinInput').focus(), 100);
}

function closePinModal() {
    document.getElementById('pinModal').classList.remove('active');
    document.getElementById('pinInput').value = '';
    pinInputs['pinInput'] = '';
    updatePinDots('pinInput', 0);
}

function openAdminVerification() {
    document.getElementById('adminVerifyModal').classList.add('active');
    document.getElementById('adminVerifyPinInput').value = '';
    pinInputs['adminVerifyPinInput'] = '';
    updatePinDots('adminVerifyPinInput', 0);
    setupPinMasking('adminVerifyPinInput', 6);
    setTimeout(() => document.getElementById('adminVerifyPinInput').focus(), 100);
}

function closeAdminVerify() {
    document.getElementById('adminVerifyModal').classList.remove('active');
    document.getElementById('adminVerifyPinInput').value = '';
    pinInputs['adminVerifyPinInput'] = '';
    updatePinDots('adminVerifyPinInput', 0);
}

function verifyAdminPin(pin) {
    const apiUrl = getApiUrl('verify_admin_pin.php');

    fetch(apiUrl, {
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
            Swal.fire({
                icon: 'error',
                title: 'Invalid PIN',
                text: 'That super-admin PIN is incorrect.',
                background: 'var(--card)',
                color: 'var(--text)',
                confirmButtonColor: 'var(--ink)'
            });
            document.getElementById('adminVerifyPinInput').value = '';
            pinInputs['adminVerifyPinInput'] = '';
            updatePinDots('adminVerifyPinInput', 0);
            document.getElementById('adminVerifyPinInput').focus();
        }
    })
    .catch(() => Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Error verifying the super-admin PIN.',
        background: 'var(--card)',
        color: 'var(--text)',
        confirmButtonColor: 'var(--ink)'
    }));
}

function openAdminDashboard() {
    document.getElementById('adminDashboardModal').classList.add('active');
    loadAccountsList();
    loadSlackSettings();
}

function showSlackStatus(message, isError) {
    const el = document.getElementById('slackStatusMsg');
    el.textContent = message;
    el.style.color = isError ? 'var(--critical)' : 'var(--good, #1F7A54)';
    el.style.display = 'block';
}

function loadSlackSettings() {
    fetch(getApiUrl('get_slack_webhook.php'))
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('slackWebhookInput').value = data.webhook_url || '';
            }
        })
        .catch(() => {});
}

document.getElementById('slackSaveBtn').addEventListener('click', () => {
    const webhookUrl = document.getElementById('slackWebhookInput').value.trim();
    fetch(getApiUrl('update_slack_webhook.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ webhook_url: webhookUrl })
    })
        .then(res => res.json())
        .then(data => showSlackStatus(data.message, !data.success))
        .catch(() => showSlackStatus('Failed to save Slack webhook', true));
});

document.getElementById('slackTestBtn').addEventListener('click', () => {
    const webhookUrl = document.getElementById('slackWebhookInput').value.trim();
    showSlackStatus('Sending…', false);
    fetch(getApiUrl('test_slack_webhook.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ webhook_url: webhookUrl })
    })
        .then(res => res.json())
        .then(data => showSlackStatus(data.message, !data.success))
        .catch(() => showSlackStatus('Failed to send test message', true));
});

function closeAdminDashboard() {
    document.getElementById('adminDashboardModal').classList.remove('active');
}

function openAddUserModal() {
    document.getElementById('addUserModal').classList.add('active');
    document.querySelector('#registerForm input[name="name"]').focus();
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
    const apiUrl = getApiUrl('get_accounts.php');
    
    fetch(apiUrl)
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            const accountsList = document.getElementById('accountsList');
            accountsList.innerHTML = '';
            
            if (data.accounts.length === 0) {
                accountsList.innerHTML = '<p style="color: var(--text-muted); font-size: 12px;">No users found</p>';
                return;
            }
            
            data.accounts.forEach(account => {
                const initials = (account.name || '').split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('');
                const accountEl = document.createElement('div');
                accountEl.className = 'dashboard-user-item';
                accountEl.innerHTML = `
                    <div class="account-icon user">${initials}</div>
                    <div class="dashboard-user-info">
                        <div class="dashboard-user-name">${account.name}</div>
                        <div class="dashboard-user-email">${account.email}</div>
                    </div>
                    <div class="user-actions">
                        <button type="button" title="Edit" onclick="editAccount(${account.user_id}, '${account.name}')">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                        <button type="button" class="delete" title="Delete" onclick="deleteAccount(${account.user_id}, '${account.name}')">
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

// Helper function to get correct API URL
function getApiUrl(endpoint) {
    const protocol = window.location.protocol;
    const host = window.location.host;
    
    // Build direct URL to auth endpoint
    // Result: https://engagements.morganserver.com/auth/[endpoint]
    return protocol + '//' + host + '/auth/' + endpoint;
}

function editAccount(userId, accountName) {
    // Fetch account details from server
    const apiUrl = getApiUrl('get_account_details.php');
    
    console.log('Fetching from:', apiUrl);
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({user_id: userId})
    })
    .then(res => {
        console.log('Response status:', res.status);
        console.log('Response headers:', res.headers.get('content-type'));
        return res.text(); // Get as text first
    })
    .then(text => {
        console.log('Raw response text:', text);
        
        // Try to parse as JSON
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Text was:', text);
            Swal.fire({
                icon: 'error',
                title: 'Invalid Response',
                text: 'Server returned invalid data. Check console for details.',
                background: 'var(--card)',
                color: 'var(--text)',
                confirmButtonColor: 'var(--ink)'
            });
            return;
        }
        
        console.log('Parsed data:', data);
        
        if(data.success && data.account) {
            const account = data.account;
            document.getElementById('editUserId').value = account.user_id;
            document.getElementById('editAccountName').value = account.name;
            document.getElementById('editEmail').value = account.email;
            document.getElementById('editPasscode').value = '';
            document.getElementById('editUserModal').classList.add('active');
            setTimeout(() => document.getElementById('editAccountName').focus(), 100);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to load user details',
                background: 'var(--card)',
                color: 'var(--text)',
                confirmButtonColor: 'var(--ink)'
            });
        }
    })
    .catch((error) => {
        console.error('Fetch error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error loading user details: ' + error.message,
            background: 'var(--card)',
            color: 'var(--text)',
            confirmButtonColor: 'var(--ink)'
        });
    });
}

function submitEditForm(event) {
    event.preventDefault();
    
    const userId = document.getElementById('editUserId').value;
    const name = document.getElementById('editAccountName').value;
    const email = document.getElementById('editEmail').value;
    const passcode = document.getElementById('editPasscode').value;

    if (!userId || !name || !email) {
        Swal.fire({
            icon: 'error',
            title: 'Missing Fields',
            text: 'Please fill in all fields',
            background: 'var(--card)',
            color: 'var(--text)',
            confirmButtonColor: 'var(--ink)'
        });
        return;
    }

    if (passcode && !/^\d{4}$/.test(passcode)) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid PIN',
            text: 'PIN must be exactly 4 digits',
            background: 'var(--card)',
            color: 'var(--text)',
            confirmButtonColor: 'var(--ink)'
        });
        return;
    }

    const apiUrl = getApiUrl('update_account.php');
    
    console.log('Submitting update to:', apiUrl);
    console.log('Data:', { user_id: userId, name, email, passcode });
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            user_id: parseInt(userId),
            name: name,
            email: email,
            passcode: passcode
        })
    })
    .then(res => {
        console.log('Update response status:', res.status);
        return res.json();
    })
    .then(data => {
        console.log('Update response data:', data);
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Updated!',
                text: 'Account updated successfully',
                background: 'var(--card)',
                color: 'var(--text)',
                confirmButtonColor: 'var(--ink)'
            }).then(() => {
                closeEditUserModal();
                loadAccountsList();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to update account',
                background: 'var(--card)',
                color: 'var(--text)',
                confirmButtonColor: 'var(--ink)'
            });
        }
    })
    .catch((error) => {
        console.error('Update error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error updating account: ' + error.message,
            background: 'var(--card)',
            color: 'var(--text)',
            confirmButtonColor: 'var(--ink)'
        });
    });
}

function deleteAccount(userId, accountName) {
    Swal.fire({
        title: 'Delete User?',
        text: `Are you sure you want to delete "${accountName}"? This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--critical)',
        cancelButtonColor: 'var(--line)',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel',
        background: 'var(--card)',
        color: 'var(--text)',
        customClass: {
            popup: 'swal2-dark',
            title: 'swal2-title-custom',
            htmlContainer: 'swal2-html-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            const apiUrl = getApiUrl('delete_account.php');
            
            fetch(apiUrl, {
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
                        background: 'var(--card)',
                        color: 'var(--text)',
                        confirmButtonColor: 'var(--ink)'
                    }).then(() => {
                        // Refresh page to close all modals
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Error deleting account',
                        background: 'var(--card)',
                        color: 'var(--text)',
                        confirmButtonColor: 'var(--ink)'
                    });
                }
            })
            .catch(() => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error deleting account',
                    background: 'var(--card)',
                    color: 'var(--text)',
                    confirmButtonColor: 'var(--ink)'
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