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

// auth/login.php already sets $_SESSION['error'] on a failed PIN or a
// rate-limit hit and redirects back here — this page used to never read
// it, so a wrong PIN silently dumped you back at the picker with zero
// feedback. Read it once, then clear it so a refresh doesn't re-show a
// stale message.
$loginError = $_SESSION['error'] ?? null;
unset($_SESSION['error']);

// Role -> CSS var for each account's avatar + a plain-text role label,
// matching the color convention already used for role badges/avatars
// elsewhere (dashboard.php, engagement-details.php).
$roleColorVar = [
    'manager' => 'var(--manager)',
    'senior'  => 'var(--senior)',
    'staff'   => 'var(--staff)',
    'intern'  => 'var(--intern)',
];
$roleLabel = [
    'manager' => 'Manager',
    'senior'  => 'Senior',
    'staff'   => 'Staff',
    'intern'  => 'Intern',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign in - Engagement Tracker</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --ink: #1B3A5C;
    --ink-soft: #4A6483;
    --paper: #F4F6F8;
    --card: #FFFFFF;
    --line: #DCE1E7;
    --line-strong: #C2CAD3;
    --text: #16202B;
    --text-muted: #5B6B7C;
    --critical: #B3261E;
    --critical-tint: rgba(179, 38, 30, 0.07);
    --caution: #A66A00;
    --caution-tint: rgba(166, 106, 0, 0.08);
    --good: #1F7A54;

    --manager: var(--ink);
    --staff: var(--good);
    --intern: var(--caution);
    --senior: #7A4FB0;
}
body.dark-mode {
    --ink: #6E9FCB;
    --ink-soft: #7C93AA;
    --paper: #10161D;
    --card: #171F28;
    --line: #2A343E;
    --line-strong: #3C4854;
    --text: #E7ECF1;
    --text-muted: #93A1AF;
    --critical: #E5766F;
    --critical-tint: rgba(229, 118, 111, 0.1);
    --caution: #D3A44E;
    --caution-tint: rgba(211, 164, 78, 0.12);
    --good: #5FB98A;
    --senior: #B79AE0;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

html, body { height: 100%; }
body {
    background: var(--paper);
    color: var(--text);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    transition: background-color 0.2s ease, color 0.2s ease;
}

.theme-toggle {
    position: fixed; top: 1.1rem; right: 1.1rem;
    width: 34px; height: 34px; border-radius: 8px;
    border: 1px solid var(--line); background: var(--card);
    color: var(--text-muted); font-size: 15px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; z-index: 10;
}
.theme-toggle:hover { color: var(--text); border-color: var(--line-strong); }

@media (prefers-reduced-motion: reduce) {
    .alert-banner { animation: none !important; }
}

/* ---------- split layout: fixed-dark brand rail + paper sign-in panel ---------- */
.split { display: grid; grid-template-columns: 38% 62%; min-height: 100vh; }

.brand-panel {
    background: #12283D; color: #E7ECF1;
    padding: 2.6rem 2.6rem;
    display: flex; flex-direction: column;
    position: relative; overflow: hidden;
}
.brand-panel::before {
    content: ""; position: absolute; inset: 0;
    background-image: repeating-linear-gradient(135deg, rgba(255,255,255,0.035) 0px, rgba(255,255,255,0.035) 1px, transparent 1px, transparent 26px);
    pointer-events: none;
}
.brand-mark { display: flex; align-items: center; gap: 0.55rem; position: relative; }
.brand-icon { width: 28px; height: 28px; border-radius: 7px; background: #6E9FCB; color: #0E1B27; display: flex; align-items: center; justify-content: center; font-size: 12px; flex-shrink: 0; }
.brand-word { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; }
.brand-tagline { font-size: 26px; font-weight: 700; line-height: 1.32; letter-spacing: -0.015em; margin: auto 0; max-width: 21ch; position: relative; }
.brand-tagline span { color: #8EB4D6; }
.brand-types { position: relative; display: flex; flex-wrap: wrap; gap: 0.5rem 0.9rem; font-size: 10.5px; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase; color: #7C93AA; }

/* ---------- sign-in panel ---------- */
.form-panel { background: var(--paper); padding: 2.6rem 3rem; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.form-inner { width: 100%; max-width: 440px; display: flex; flex-direction: column; }

.alert-banner {
    display: flex; align-items: center; gap: 8px;
    padding: 0.7rem 0.9rem;
    border-radius: 9px;
    font-size: 12.5px; font-weight: 600;
    margin-bottom: 1.2rem;
    animation: fadeIn 0.3s ease;
}
.alert-banner.error { background: var(--critical-tint); color: var(--critical); border: 1px solid color-mix(in srgb, var(--critical) 35%, transparent); }
.alert-banner.warn { background: var(--caution-tint); color: var(--caution); border: 1px solid color-mix(in srgb, var(--caution) 35%, transparent); }
.alert-banner i { font-size: 14px; flex-shrink: 0; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-4px); } to { opacity: 1; transform: translateY(0); } }

.form-eyebrow { font-size: 10.5px; font-weight: 700; letter-spacing: 0.09em; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.4rem; }
.form-inner h1 { font-size: 22px; font-weight: 700; letter-spacing: -0.015em; margin: 0 0 0.3rem; }
.form-inner > p.form-sub { font-size: 12.5px; color: var(--text-muted); margin: 0 0 1.6rem; }

.account-rows { display: flex; flex-direction: column; }
.account-row {
    display: flex; align-items: center; gap: 0.85rem; padding: 0.9rem 0.15rem;
    border-bottom: 1px solid var(--line); cursor: pointer;
    background: none; border-left: none; border-right: none; border-top: none;
    text-align: left; width: 100%; font-family: inherit;
    transition: padding-left 0.12s ease, background-color 0.12s ease;
}
.account-row:hover, .account-row:focus-visible {
    padding-left: 0.6rem; background: color-mix(in srgb, var(--ink) 4%, transparent); outline: none;
}
.account-row:focus-visible { box-shadow: inset 2px 0 0 var(--ink); }

.account-avatar {
    width: 34px; height: 34px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: var(--card);
    flex-shrink: 0;
}
.account-info { flex: 1; min-width: 0; }
.account-name { font-size: 13px; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.account-email { font-size: 11px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.account-role { font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; color: var(--text-muted); flex-shrink: 0; }

.account-empty {
    text-align: center; padding: 1.75rem 1rem;
    color: var(--text-muted); font-size: 12.5px;
    border-top: 1px solid var(--line); border-bottom: 1px solid var(--line);
}
.account-empty i { font-size: 20px; display: block; margin-bottom: 0.5rem; opacity: 0.6; }

.form-footnote { margin-top: auto; padding-top: 1.6rem; font-size: 11px; color: var(--text-muted); }

@media (max-width: 720px) {
    .split { grid-template-columns: 1fr; min-height: 0; }
    .brand-panel { padding: 1.9rem 1.6rem; }
    .brand-tagline { font-size: 20px; margin: 1.6rem 0; }
    .form-panel { padding: 2rem 1.6rem 2.6rem; }
    .form-footnote { margin-top: 1.6rem; padding-top: 0; }
}

/* ---------- PIN modal ---------- */
.modal-overlay {
    position: fixed; inset: 0;
    background: rgba(10, 15, 22, 0.5);
    display: none; align-items: center; justify-content: center;
    padding: 1.5rem;
    z-index: 1000;
}
.modal-overlay.active { display: flex; }

.modal-box {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 1.75rem;
    width: 100%;
    max-width: 380px;
    box-shadow: 0 24px 64px rgba(0, 0, 0, 0.28);
    position: relative;
}

.modal-close {
    position: absolute; top: 0.9rem; right: 0.9rem;
    width: 28px; height: 28px;
    border: none; background: transparent;
    color: var(--text-muted); font-size: 18px;
    cursor: pointer; line-height: 1; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
}
.modal-close:hover { color: var(--text); background: var(--paper); }

.modal-header {
    display: flex; align-items: center; gap: 0.7rem;
    margin-bottom: 1.3rem;
    padding-bottom: 1.1rem;
    border-bottom: 1px solid var(--line);
}
.modal-header-icon {
    width: 38px; height: 38px;
    background: color-mix(in srgb, var(--ink) 12%, transparent);
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    color: var(--ink);
    font-size: 16px;
    flex-shrink: 0;
}
.modal-header h5 { font-size: 14.5px; font-weight: 700; color: var(--text); }
.modal-subtext { font-size: 11px; color: var(--text-muted); margin-top: 1px; }

/* PIN dot entry — replaces bullet-masked text for sign-in. The real <input>
   stays focused/typable but visually hidden; JS toggles .filled on these
   dots as digits are entered.
   The input is layered directly on top of the dots (not tucked off-screen)
   so it's a real tap target — on mobile, an off-screen/pointer-events:none
   input can never be focused by touch, and JS-triggered .focus() alone
   isn't enough to raise the on-screen keypad on most mobile browsers unless
   it happens synchronously inside a genuine tap. Tapping the dots directly
   focuses this input and reliably opens the numeric keypad. */
.pin-entry { position: relative; display: flex; justify-content: center; margin: 1.5rem 0 0.6rem; }
.pin-hidden-input {
    position: absolute; inset: 0; width: 100%; height: 100%;
    opacity: 0; border: 0; background: transparent; margin: 0; padding: 0;
    font-size: 16px; /* 16px+ stops iOS Safari auto-zooming in on focus */
    cursor: pointer; z-index: 1;
}
.pin-dots { display: flex; gap: 0.85rem; justify-content: center; }
.pin-dot {
    width: 14px; height: 14px; border-radius: 50%; border: 2px solid var(--line-strong);
    transition: all 0.15s;
}
.pin-dot.filled { background: var(--ink); border-color: var(--ink); transform: scale(1.1); }
.pin-hint { text-align: center; font-size: 11px; color: var(--text-muted); }
</style>
</head>
<body>
<script>
// Applied synchronously, as the very first thing in <body>, so the saved
// theme is set before anything paints — avoids a light-then-dark flash for
// anyone who left dark mode on while last logged in.
try {
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
} catch (e) {}
</script>

<button class="theme-toggle" id="themeToggle" title="Dark mode" aria-label="Toggle dark mode">
    <i class="bi bi-moon"></i>
</button>

<div class="split">
    <!-- Brand rail — intentionally always dark, independent of the light/
         dark toggle to the right: a fixed panel, like paper stock that
         doesn't change color when the office lights do. -->
    <div class="brand-panel">
        <div class="brand-mark">
            <div class="brand-icon"><i class="bi bi-bar-chart-fill"></i></div>
            <div class="brand-word">Engagement Tracker</div>
        </div>
        <div class="brand-tagline">Every engagement, <span>accounted for.</span></div>
        <div class="brand-types">SOC 1 &middot; SOC 2 &middot; HIPAA &middot; HITRUST &middot; FISMA</div>
    </div>

    <div class="form-panel">
        <div class="form-inner">
            <?php if ($loginError): ?>
                <div class="alert-banner error"><i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($loginError) ?></div>
            <?php elseif (isset($_GET['timeout'])): ?>
                <div class="alert-banner warn"><i class="bi bi-clock-history"></i> You were logged out due to inactivity &mdash; please sign in again.</div>
            <?php endif; ?>

            <div class="form-eyebrow">Sign in</div>
            <h1>Select your account</h1>
            <p class="form-sub">Choose your name, then enter your PIN.</p>

            <?php if (!empty($accounts)): ?>
                <div class="account-rows">
                    <?php foreach ($accounts as $account): ?>
                        <?php if ($account['role'] === 'super_admin') continue; ?>
                        <?php
                            $accountInitials = '';
                            foreach (explode(' ', trim($account['name'])) as $part) {
                                if ($part !== '') $accountInitials .= strtoupper($part[0]);
                            }
                            $avatarColor = $roleColorVar[$account['role']] ?? 'var(--ink)';
                            $roleText = $roleLabel[$account['role']] ?? ucfirst($account['role']);
                        ?>
                        <button type="button" class="account-row"
                             data-user-id="<?= $account['user_id'] ?>"
                             data-account-name="<?= htmlspecialchars($account['name']) ?>"
                             onclick="openPinModal(this)">
                            <div class="account-avatar" style="background:<?= $avatarColor ?>;"><?= htmlspecialchars($accountInitials) ?></div>
                            <div class="account-info">
                                <div class="account-name"><?= htmlspecialchars($account['name']) ?></div>
                                <div class="account-email"><?= htmlspecialchars($account['email']) ?></div>
                            </div>
                            <div class="account-role"><?= htmlspecialchars($roleText) ?></div>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="account-empty">
                    <i class="bi bi-person-x"></i>
                    No accounts available
                </div>
            <?php endif; ?>

            <p class="form-footnote">Locked out or need a PIN reset? Contact your admin.</p>
        </div>
    </div>
</div>

<!-- PIN Entry Modal -->
<div class="modal-overlay" id="pinModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closePinModal()" aria-label="Close">&times;</button>
        <div class="modal-header">
            <div class="modal-header-icon">
                <i class="bi bi-lock-fill"></i>
            </div>
            <div>
                <h5 id="modalAccountName">Enter PIN</h5>
                <div class="modal-subtext">4-digit PIN</div>
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

    // If we landed back here right after a failed PIN attempt (the error
    // banner above is present), give it a beat of visible motion instead of
    // just sitting there — a plain static banner is easy to miss right
    // after being bounced back from a modal.
    const errorBanner = document.querySelector('.alert-banner.error');
    if (errorBanner) {
        errorBanner.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
});

// Close modal when clicking outside, or on Escape
window.addEventListener('click', function(e){
    if (e.target === document.getElementById('pinModal')) closePinModal();
});
window.addEventListener('keydown', function(e){
    if (e.key === 'Escape' && document.getElementById('pinModal').classList.contains('active')) {
        closePinModal();
    }
});

// Dark mode toggle — mirrors the same localStorage key + icon-swap
// convention used on dashboard.php, so a choice made here carries over
// once logged in (and vice versa). Only the sign-in panel + modal respond
// to this; the brand rail on the left stays dark on purpose either way.
const themeToggle = document.getElementById('themeToggle');
function updateThemeIcon(isDark) {
    const icon = themeToggle.querySelector('i');
    icon.classList.toggle('bi-moon', !isDark);
    icon.classList.toggle('bi-sun', isDark);
}
updateThemeIcon(document.body.classList.contains('dark-mode'));
themeToggle.addEventListener('click', () => {
    const isDark = document.body.classList.toggle('dark-mode');
    try { localStorage.setItem('darkMode', isDark); } catch (e) {}
    updateThemeIcon(isDark);
});
</script>

</body>
</html>
