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

.modal-header h5 {
    font-size: 15.5px; font-weight: 700;
    color: var(--text); margin: 0; flex: 1;
}

.modal-subtext {
    font-size: 11.5px; color: var(--text-muted); margin-top: 2px;
}

/* PIN dot entry — replaces bullet-masked text for sign-in. The real <input>
   stays focused/typable but visually hidden; JS toggles .filled on these
   dots as digits are entered.
   The input is layered directly on top of the dots (not tucked off-screen)
   so it's a real tap target — on mobile, an off-screen/pointer-events:none
   input can never be focused by touch, and JS-triggered .focus() alone
   isn't enough to raise the on-screen keypad on most mobile browsers unless
   it happens synchronously inside a genuine tap. Tapping the dots directly
   focuses this input and reliably opens the numeric keypad. */
.pin-entry { position: relative; display: flex; justify-content: center; margin: 1.6rem 0 0.5rem; }
.pin-hidden-input {
    position: absolute; inset: 0; width: 100%; height: 100%;
    opacity: 0; border: 0; background: transparent; margin: 0; padding: 0;
    font-size: 16px; /* 16px+ stops iOS Safari auto-zooming in on focus */
    cursor: pointer; z-index: 1;
}
.pin-dots { display: flex; gap: 0.85rem; justify-content: center; }
.pin-dot {
    width: 15px; height: 15px; border-radius: 50%; border: 2px solid var(--line-strong);
    transition: all 0.15s;
}
.pin-dot.filled { background: var(--ink); border-color: var(--ink); transform: scale(1.1); }
.pin-hint { text-align: center; font-size: 11.5px; color: var(--text-muted); }

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
            <div class="pin-entry">
                <input type="text" class="pin-hidden-input" id="pinInput" inputmode="numeric" autocomplete="off" required autofocus>
                <div class="pin-dots" id="pinDots">
                    <div class="pin-dot"></div>
                    <div class="pin-dot"></div>
                    <div class="pin-dot"></div>
                    <div class="pin-dot"></div>
                </div>
            </div>
            <div class="pin-hint">Tap the dots, then type your PIN — it submits automatically</div>
        </form>
    </div>
</div>

<script>
const pinInputs = {};
const pinDotsMap = { pinInput: 'pinDots' };

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

    // Driven off the 'input' event rather than keydown: mobile/virtual
    // keyboards don't reliably fire keydown with a usable e.key for every
    // software keypad (some report "Unidentified"), but 'input' always
    // fires with the real inserted text — covers typed digits, paste,
    // autofill, and IME/voice input the same way on every platform.
    // The field is invisible (opacity: 0, dots do the actual display), so
    // there's no need to blank it after every keystroke — trim it to size
    // instead of wiping it so digits accumulate across keystrokes, and so
    // native Backspace/Delete just work without a separate handler.
    input.addEventListener('input', function() {
        const digits = input.value.replace(/\D/g, '').slice(0, maxLength);
        if (input.value !== digits) {
            input.value = digits;
        }
        pinInputs[inputId] = digits;
        updatePinDots(inputId, digits.length);

        // Auto-submit PIN form when 4 digits entered
        if (inputId === 'pinInput' && digits.length === maxLength) {
            const passcodeField = document.getElementById('pinFormPasscode');
            passcodeField.value = digits;
            setTimeout(() => {
                document.getElementById('pinForm').submit();
            }, 150);
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
    // Focused synchronously (not via setTimeout) so it stays inside the same
    // user-gesture call stack as the tap that opened this modal — that's
    // what most mobile browsers require before they'll raise the on-screen
    // keypad for a JS-triggered focus.
    document.getElementById('pinInput').focus();
}

function closePinModal() {
    document.getElementById('pinModal').classList.remove('active');
    document.getElementById('pinInput').value = '';
    pinInputs['pinInput'] = '';
    updatePinDots('pinInput', 0);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setupPinMasking('pinInput', 4);
});

// Close modal when clicking outside
window.addEventListener('click', function(e){
    if(e.target === document.getElementById('pinModal')) closePinModal();
});
</script>

</body>
</html>