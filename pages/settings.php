<?php
require_once '../auth/session_check.php';
require_once '../path.php';
require_once '../includes/functions.php';

// Settings is reached from the profile dropdown, not a separate PIN gate —
// access is purely the logged-in account's own role. Non-admins get bounced
// straight back to the dashboard.
if (!in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'], true)) {
    header('Location: dashboard.php');
    exit;
}

$initials = '';
if (!empty($_SESSION['name'])) {
    foreach (explode(' ', trim($_SESSION['name'])) as $part) {
        if (!empty($part)) $initials .= strtoupper($part[0]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
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
            --good: #1F7A54;
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
            --good: #5FB98A;
        }

        * { box-sizing: border-box; }
        body {
            background: var(--paper);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-variant-numeric: tabular-nums;
            transition: background-color 0.2s ease, color 0.2s ease;
            margin: 0;
        }

        /* ---------- header (ported from dashboard.php) ---------- */
        .top-header { background: var(--card); border-bottom: 1px solid var(--line); padding: 0 1.75rem; position: sticky; top: 0; z-index: 100; }
        .header-inner { max-width: 1080px; margin: 0 auto; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 2.5rem; }
        .brand { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; text-decoration: none; }
        .brand-icon { width: 26px; height: 26px; border-radius: 7px; background: var(--ink); color: var(--card); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .brand-mark { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; color: var(--text); }
        .main-nav { display: flex; gap: 1.6rem; }
        .main-nav a { font-size: 13px; font-weight: 600; color: var(--text-muted); text-decoration: none; padding: 4px 0; border-bottom: 2px solid transparent; }
        .main-nav a:hover { color: var(--text); }
        .header-right { display: flex; align-items: center; gap: 0.65rem; margin-left: auto; }
        .icon-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; position: relative; font-size: 15px; }
        .icon-btn:hover { background: color-mix(in srgb, var(--ink) 8%, var(--paper)); color: var(--text); }

        .profile-section { position: relative; margin-left: 0.4rem; padding-left: 0.75rem; border-left: 1px solid var(--line); }
        .profile-wrapper { display: flex; align-items: center; gap: 6px; cursor: pointer; }
        .profile-btn { width: 30px; height: 30px; border-radius: 50%; background: var(--ink); color: var(--card); border: none; font-weight: 700; font-size: 11.5px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .profile-dropdown-toggle { border: none; background: transparent; color: var(--text-muted); cursor: pointer; padding: 2px; }
        .profile-dropdown {
            position: absolute; top: calc(100% + 8px); right: 0; width: 240px;
            background: var(--card); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,0.14);
            display: none; z-index: 60;
        }
        .profile-dropdown.active { display: block; }
        .profile-dropdown-header { display: flex; gap: 10px; align-items: center; padding: 0.9rem 1rem; border-bottom: 1px solid var(--line); }
        .profile-dropdown-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: var(--card); font-weight: 700; font-size: 12.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .profile-dropdown-name { font-size: 13px; font-weight: 700; }
        .profile-dropdown-email { font-size: 11.5px; color: var(--text-muted); }
        .profile-dropdown-menu { padding: 0.4rem; }
        .profile-dropdown-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 12.5px; color: var(--text); text-decoration: none; background: none; border: none; width: 100%; text-align: left; cursor: pointer; }
        .profile-dropdown-item:hover { background: var(--paper); }
        .profile-dropdown-item.logout { color: var(--critical); }

        /* ---------- page body ---------- */
        .main-container { max-width: 720px; margin: 0 auto; padding: 2.25rem 1.75rem 4rem; }
        .page-head h1 { font-size: 24px; margin: 0; font-weight: 700; letter-spacing: -0.015em; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin: 4px 0 0; }
        hr.rule { border: none; border-top: 1px solid var(--line); margin: 1.1rem 0 1.75rem; }

        .settings-card { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 1.4rem 1.5rem; margin-bottom: 1.25rem; }
        .settings-card-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.1rem; }
        .settings-card-header h2 { font-size: 15px; font-weight: 700; margin: 0; }
        .settings-card-desc { font-size: 12px; color: var(--text-muted); margin: -0.7rem 0 1.1rem; }

        .channel-header { display: flex; align-items: center; justify-content: space-between; margin: 0 0 0.6rem; }
        .channel-toggle { position: relative; display: inline-block; width: 36px; height: 20px; flex-shrink: 0; }
        .channel-toggle input { opacity: 0; width: 0; height: 0; }
        .channel-toggle-track { position: absolute; cursor: pointer; inset: 0; background: var(--line-strong); border-radius: 20px; transition: background 0.15s ease; }
        .channel-toggle-track::before { content: ""; position: absolute; height: 14px; width: 14px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: transform 0.15s ease; }
        .channel-toggle input:checked + .channel-toggle-track { background: var(--good); }
        .channel-toggle input:checked + .channel-toggle-track::before { transform: translateX(16px); }

        .form-label { font-size: 11.5px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 700; display: block; }
        .form-control { background: var(--paper); border: 1px solid var(--line); color: var(--text); border-radius: 8px; padding: 0.75rem 1rem; font-size: 14px; width: 100%; }
        .form-control::placeholder { color: var(--text-muted); }
        .form-control:focus { background: var(--paper); border-color: var(--ink); color: var(--text); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 10%, transparent); outline: none; }
        select.form-control { appearance: auto; }
        .pin-field { letter-spacing: 0.2em; font-family: 'Courier New', monospace; font-size: 20px !important; font-weight: 600; text-align: center; }
        .mb-3 { margin-bottom: 1rem; }

        .btn-primary { background: var(--ink); border: none; color: var(--card); font-weight: 700; border-radius: 8px; padding: 0.7rem 1.3rem; transition: all 0.15s; }
        .btn-primary:hover { background: color-mix(in srgb, var(--ink) 85%, black); box-shadow: 0 4px 16px color-mix(in srgb, var(--ink) 30%, transparent); }
        .btn-secondary { background: var(--paper); border: 1px solid var(--line); color: var(--text-muted); font-weight: 700; border-radius: 8px; padding: 0.7rem 1.3rem; transition: all 0.15s; }
        .btn-secondary:hover { background: var(--line); color: var(--text); }
        .button-group { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .button-group button { flex: 1; }

        .account-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
        .account-icon.user { background: var(--ink); color: var(--card); }
        .account-icon.admin { background: var(--critical); color: var(--card); }
        .dashboard-user-item { background: transparent; border: 1px solid var(--line); border-radius: 10px; padding: 0.7rem 0.8rem; display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; transition: all 0.15s ease; }
        .dashboard-user-item:hover { border-color: var(--ink); background: color-mix(in srgb, var(--ink) 5%, transparent); }
        .dashboard-user-info { flex: 1; min-width: 0; }
        .dashboard-user-name { font-size: 12.5px; font-weight: 700; color: var(--text); margin-bottom: 0.15rem; display: flex; align-items: center; gap: 6px; }
        .dashboard-user-email { font-size: 11px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .role-badge { font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; color: var(--critical); background: var(--critical-tint); padding: 1px 6px; border-radius: 5px; }
        .user-actions { display: flex; gap: 4px; }
        .user-actions button { background: var(--paper); border: 1px solid var(--line); color: var(--text-muted); cursor: pointer; font-size: 12px; width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; transition: all 0.15s; }
        .user-actions button:hover { border-color: var(--ink); color: var(--ink); }
        .user-actions button.delete:hover { border-color: var(--critical); color: var(--critical); }
        .accounts-empty { font-size: 12px; color: var(--text-muted); padding: 0.5rem 0; }

        /* ---------- modals (Add/Edit User) ---------- */
        .modal-overlay { position: fixed; inset: 0; background: rgba(10, 15, 22, 0.55); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 1.5rem; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 1.9rem; width: 100%; max-width: 400px; box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5); position: relative; }
        .modal-close { position: absolute; top: 1rem; right: 1rem; width: 30px; height: 30px; border: none; background: transparent; color: var(--text-muted); font-size: 20px; cursor: pointer; padding: 0; line-height: 1; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .modal-close:hover { color: var(--text); background: var(--paper); }
        .modal-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.4rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--line); }
        .modal-header-icon { width: 42px; height: 42px; background: color-mix(in srgb, var(--ink) 14%, transparent); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--ink); font-size: 19px; flex-shrink: 0; }
        .modal-header h5 { font-size: 15.5px; font-weight: 700; color: var(--text); margin: 0; flex: 1; }

        /* ========== SWEETALERT2 (same hardcoded-hex approach as dashboard.php —
           var() indirection doesn't reliably resolve inside Swal's dynamically
           inserted DOM) ========== */
        .swal2-container { z-index: 2000; }
        .swal2-popup { background: #FFFFFF !important; border: 1px solid #DCE1E7; border-radius: 16px; padding: 1.5rem; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); max-width: 550px; width: calc(100vw - 2rem); overflow-x: hidden; }
        .swal2-title { color: #16202B !important; font-size: 22px; font-weight: 700; margin-bottom: 1.5rem; line-height: 1.3; padding: 0; }
        .swal2-html-container, .swal2-html-container * { color: #16202B !important; }
        .swal2-actions { gap: 0.75rem; margin-top: 1.5rem; display: flex; justify-content: center; padding: 0; margin-left: 0; margin-right: 0; margin-bottom: 0; }
        .swal2-confirm, .swal2-cancel { flex: 1; max-width: 200px; margin: 0 !important; padding: 0.7rem 1.5rem !important; border-radius: 8px; font-weight: 600; font-size: 13px; transition: all 0.2s; min-width: 0; height: auto; }
        .swal2-confirm { background: #1B3A5C !important; color: #fff !important; border: none; }
        .swal2-confirm:hover { background: #14283F !important; box-shadow: 0 4px 12px rgba(27, 58, 92, 0.3); }
        .swal2-cancel { background: #DCE1E7 !important; color: #16202B !important; border: 1px solid #DCE1E7; }
        .swal2-cancel:hover { background: rgba(27, 58, 92, 0.05) !important; border-color: #1B3A5C; color: #1B3A5C !important; }
        body.dark-mode .swal2-popup { background: #171F28 !important; border-color: #2A343E; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4); }
        body.dark-mode .swal2-title { color: #E7ECF1 !important; }
        body.dark-mode .swal2-html-container, body.dark-mode .swal2-html-container * { color: #E7ECF1 !important; }
        body.dark-mode .swal2-confirm { background: #6E9FCB !important; color: #10161D !important; }
        body.dark-mode .swal2-confirm:hover { background: #8CB4D8 !important; box-shadow: 0 4px 12px rgba(110, 159, 203, 0.3); }
        body.dark-mode .swal2-cancel { background: #2A343E !important; color: #E7ECF1 !important; border-color: #2A343E; }
        body.dark-mode .swal2-cancel:hover { background: rgba(110, 159, 203, 0.12) !important; border-color: #6E9FCB; color: #6E9FCB !important; }

        @media (max-width: 480px) {
            .top-header { padding: 0 1rem; }
            .header-inner { gap: 0.6rem; }
            .brand-mark { display: none; }
            .main-container { padding: 1.5rem 1rem 3rem; }
        }
    </style>
</head>
<body>

<div class="top-header">
    <div class="header-inner">
        <a href="dashboard.php" class="brand" title="Engagement Tracker">
            <span class="brand-icon">ET</span>
            <span class="brand-mark">Engagement Tracker</span>
        </a>
        <nav class="main-nav">
            <a href="dashboard.php">Engagements</a>
            <a href="tools.php">Tools</a>
        </nav>
        <div class="header-right">
            <button class="icon-btn" title="Dark mode">
                <i class="bi bi-moon"></i>
            </button>
            <div class="profile-section">
                <div class="profile-wrapper" id="profileToggle">
                    <button class="profile-btn" title="Profile"><?php echo $initials; ?></button>
                    <button class="profile-dropdown-toggle"><i class="bi bi-chevron-down"></i></button>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                        <div class="profile-dropdown-avatar"><?php echo $initials; ?></div>
                        <div>
                            <div class="profile-dropdown-name"><?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></div>
                            <div class="profile-dropdown-email"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></div>
                        </div>
                    </div>
                    <div class="profile-dropdown-menu">
                        <a href="dashboard.php" class="profile-dropdown-item">
                            <i class="bi bi-grid"></i> Dashboard
                        </a>
                        <a href="<?php echo BASE_URL . '/auth/logout.php'; ?>" class="profile-dropdown-item logout">
                            <i class="bi bi-box-arrow-right"></i> Log Out
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="main-container">
    <div class="page-head" style="display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
        <div>
            <h1>Settings</h1>
            <p class="page-sub">Account management and app-wide notification settings. Only admins can see this page.</p>
        </div>
        <a href="activity-log.php" class="btn btn-secondary" style="text-decoration: none; white-space: nowrap;">
            <i class="bi bi-clock-history"></i> Activity Log
        </a>
    </div>
    <hr class="rule">

    <div class="settings-card">
        <div class="settings-card-header">
            <h2>Accounts</h2>
            <button type="button" class="btn btn-primary" onclick="openAddUserModal()">
                <i class="bi bi-person-fill-add"></i> Add New User
            </button>
        </div>
        <div id="accountsList"></div>
    </div>

    <div class="settings-card">
        <div class="channel-header">
            <h2 style="font-size: 15px; font-weight: 700; margin: 0;">Slack Notifications</h2>
            <label class="channel-toggle">
                <input type="checkbox" id="slackEnabledToggle">
                <span class="channel-toggle-track"></span>
            </label>
        </div>
        <label class="form-label">Incoming Webhook URL</label>
        <input type="text" class="form-control" id="slackWebhookInput" placeholder="https://hooks.slack.com/services/...">
        <p class="settings-card-desc" style="margin-top: 0.5rem;">Use the toggle above to pause Slack without losing the saved webhook. Applies to upcoming key dates, upcoming milestones, and ready-to-archive alerts.</p>
        <div class="button-group">
            <button type="button" class="btn btn-secondary" id="slackTestBtn">Send Test</button>
            <button type="button" class="btn btn-primary" id="slackSaveBtn">Save</button>
        </div>
        <p id="slackStatusMsg" style="font-size: 11.5px; margin: 0.6rem 0 0; display: none;"></p>
    </div>

    <div class="settings-card">
        <div class="channel-header">
            <h2 style="font-size: 15px; font-weight: 700; margin: 0;">Push Notifications (ntfy)</h2>
            <label class="channel-toggle">
                <input type="checkbox" id="ntfyEnabledToggle">
                <span class="channel-toggle-track"></span>
            </label>
        </div>
        <label class="form-label">Topic URL</label>
        <input type="text" class="form-control" id="ntfyTopicInput" placeholder="https://ntfy.sh/your-private-topic-name">
        <p class="settings-card-desc" style="margin-top: 0.5rem;">Install the free ntfy app on your phone and subscribe to this same topic name to get push notifications. Pick something private/hard-to-guess. Use the toggle above to pause push without losing the saved topic.</p>
        <div class="button-group">
            <button type="button" class="btn btn-secondary" id="ntfyTestBtn">Send Test</button>
            <button type="button" class="btn btn-primary" id="ntfySaveBtn">Save</button>
        </div>
        <p id="ntfyStatusMsg" style="font-size: 11.5px; margin: 0.6rem 0 0; display: none;"></p>
    </div>

    <div class="settings-card">
        <h2 style="font-size: 15px; font-weight: 700; margin: 0 0 0.6rem;">"What's Due" Popup</h2>
        <label class="form-label">Days Ahead</label>
        <input type="number" class="form-control" id="duePopupDaysInput" min="1" max="90" placeholder="7" style="max-width: 140px;">
        <p class="settings-card-desc" style="margin-top: 0.5rem;">Everyone sees this same window when they log in: everything overdue, plus anything due within this many days.</p>
        <div class="button-group">
            <button type="button" class="btn btn-primary" id="duePopupSaveBtn">Save</button>
        </div>
        <p id="duePopupStatusMsg" style="font-size: 11.5px; margin: 0.6rem 0 0; display: none;"></p>
    </div>
</div>

<!-- Add New User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeAddUserModal()">&times;</button>
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
                <input type="text" class="form-control pin-field" name="passcode" maxlength="4" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-control" name="role">
                    <option value="standard">Standard</option>
                    <option value="admin">Admin (can access Settings)</option>
                </select>
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
    <div class="modal-box">
        <button class="modal-close" onclick="closeEditUserModal()">&times;</button>
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
                <input type="text" class="form-control pin-field" id="editPasscode" name="passcode" maxlength="4" placeholder="&bull;&bull;&bull;&bull;">
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-control" id="editRole" name="role">
                    <option value="standard">Standard</option>
                    <option value="admin">Admin (can access Settings)</option>
                </select>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="closeEditUserModal()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script>
// ---------- dark mode (same pattern as dashboard.php) ----------
const darkModeBtn = document.querySelector('.icon-btn[title="Dark mode"]');
function updateDarkModeIcon(isDark) {
    const icon = darkModeBtn?.querySelector('i');
    if (icon) {
        icon.classList.toggle('bi-moon', !isDark);
        icon.classList.toggle('bi-sun', isDark);
    }
}
const isDarkMode = localStorage.getItem('darkMode') === 'true';
if (isDarkMode) document.body.classList.add('dark-mode');
updateDarkModeIcon(isDarkMode);
darkModeBtn?.addEventListener('click', () => {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', isDark);
    updateDarkModeIcon(isDark);
});

// ---------- profile dropdown ----------
const profileToggle = document.getElementById('profileToggle');
const profileDropdown = document.getElementById('profileDropdown');
profileToggle?.addEventListener('click', (e) => {
    e.stopPropagation();
    profileDropdown?.classList.toggle('active');
});
document.addEventListener('click', (e) => {
    if (!profileToggle?.contains(e.target) && !profileDropdown?.contains(e.target)) {
        profileDropdown?.classList.remove('active');
    }
});

// ---------- account management ----------
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
    fetch('../auth/get_accounts.php')
    .then(res => res.json())
    .then(data => {
        if (!data.success) return;
        const accountsList = document.getElementById('accountsList');
        accountsList.innerHTML = '';

        if (data.accounts.length === 0) {
            accountsList.innerHTML = '<p class="accounts-empty">No users found</p>';
            return;
        }

        data.accounts.forEach(account => {
            const initials = (account.name || '').split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('');
            const isAdmin = account.role === 'admin';
            const accountEl = document.createElement('div');
            accountEl.className = 'dashboard-user-item';
            accountEl.innerHTML = `
                <div class="account-icon ${isAdmin ? 'admin' : 'user'}">${initials}</div>
                <div class="dashboard-user-info">
                    <div class="dashboard-user-name">${account.name}${isAdmin ? '<span class="role-badge">Admin</span>' : ''}</div>
                    <div class="dashboard-user-email">${account.email}</div>
                </div>
                <div class="user-actions">
                    <button type="button" title="Edit" onclick="editAccount(${account.user_id})">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <button type="button" class="delete" title="Delete" onclick="deleteAccount(${account.user_id}, '${account.name}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `;
            accountsList.appendChild(accountEl);
        });
    })
    .catch(() => {
        document.getElementById('accountsList').innerHTML = '<p class="accounts-empty">Error loading accounts.</p>';
    });
}

function editAccount(userId) {
    fetch('../auth/get_account_details.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: userId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.account) {
            const account = data.account;
            document.getElementById('editUserId').value = account.user_id;
            document.getElementById('editAccountName').value = account.name;
            document.getElementById('editEmail').value = account.email;
            document.getElementById('editPasscode').value = '';
            document.getElementById('editRole').value = account.role || 'standard';
            document.getElementById('editUserModal').classList.add('active');
            document.getElementById('editAccountName').focus();
        } else {
            Swal.fire('Error', data.message || 'Failed to load user details', 'error');
        }
    })
    .catch(error => Swal.fire('Error', 'Error loading user details: ' + error.message, 'error'));
}

function submitEditForm(event) {
    event.preventDefault();

    const userId = document.getElementById('editUserId').value;
    const name = document.getElementById('editAccountName').value;
    const email = document.getElementById('editEmail').value;
    const passcode = document.getElementById('editPasscode').value;
    const role = document.getElementById('editRole').value;

    if (!userId || !name || !email) {
        Swal.fire('Missing Fields', 'Please fill in all fields', 'error');
        return;
    }
    if (passcode && !/^\d{4}$/.test(passcode)) {
        Swal.fire('Invalid PIN', 'PIN must be exactly 4 digits', 'error');
        return;
    }

    fetch('../auth/update_account.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ user_id: parseInt(userId), name, email, passcode, role })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire('Updated!', 'Account updated successfully', 'success').then(() => {
                closeEditUserModal();
                loadAccountsList();
            });
        } else {
            Swal.fire('Error', data.message || 'Failed to update account', 'error');
        }
    })
    .catch(error => Swal.fire('Error', 'Error updating account: ' + error.message, 'error'));
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
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) return;
        fetch('../auth/delete_account.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Deleted!', 'Account deleted successfully', 'success').then(() => loadAccountsList());
            } else {
                Swal.fire('Error', data.message || 'Error deleting account', 'error');
            }
        })
        .catch(() => Swal.fire('Error', 'Error deleting account', 'error'));
    });
}

// ---------- Slack ----------
function showSlackStatus(message, isError) {
    const el = document.getElementById('slackStatusMsg');
    el.textContent = message;
    el.style.color = isError ? 'var(--critical)' : 'var(--good)';
    el.style.display = 'block';
}
function loadSlackSettings() {
    fetch('../auth/get_slack_webhook.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('slackWebhookInput').value = data.webhook_url || '';
                document.getElementById('slackEnabledToggle').checked = !!data.enabled;
            }
        })
        .catch(() => {});
}
document.getElementById('slackSaveBtn').addEventListener('click', () => {
    const webhookUrl = document.getElementById('slackWebhookInput').value.trim();
    const enabled = document.getElementById('slackEnabledToggle').checked;
    fetch('../auth/update_slack_webhook.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ webhook_url: webhookUrl, enabled })
    })
        .then(res => res.json())
        .then(data => showSlackStatus(data.message, !data.success))
        .catch(() => showSlackStatus('Failed to save Slack settings', true));
});
document.getElementById('slackTestBtn').addEventListener('click', () => {
    const webhookUrl = document.getElementById('slackWebhookInput').value.trim();
    showSlackStatus('Sending…', false);
    fetch('../auth/test_slack_webhook.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ webhook_url: webhookUrl })
    })
        .then(res => res.json())
        .then(data => showSlackStatus(data.message, !data.success))
        .catch(() => showSlackStatus('Failed to send test message', true));
});
document.getElementById('slackEnabledToggle').addEventListener('change', (ev) => {
    const enabled = ev.target.checked;
    fetch('../auth/get_slack_webhook.php')
        .then(res => res.json())
        .then(data => fetch('../auth/update_slack_webhook.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ webhook_url: data.webhook_url || '', enabled })
        }))
        .then(res => res.json())
        .then(data => showSlackStatus(enabled ? 'Slack notifications on' : 'Slack notifications off', !data.success))
        .catch(() => showSlackStatus('Failed to update Slack toggle', true));
});

// ---------- ntfy ----------
function showNtfyStatus(message, isError) {
    const el = document.getElementById('ntfyStatusMsg');
    el.textContent = message;
    el.style.color = isError ? 'var(--critical)' : 'var(--good)';
    el.style.display = 'block';
}
function loadNtfySettings() {
    fetch('../auth/get_ntfy_settings.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById('ntfyTopicInput').value = data.topic_url || '';
                document.getElementById('ntfyEnabledToggle').checked = !!data.enabled;
            }
        })
        .catch(() => {});
}
document.getElementById('ntfySaveBtn').addEventListener('click', () => {
    const topicUrl = document.getElementById('ntfyTopicInput').value.trim();
    const enabled = document.getElementById('ntfyEnabledToggle').checked;
    fetch('../auth/update_ntfy_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ topic_url: topicUrl, enabled })
    })
        .then(res => res.json())
        .then(data => showNtfyStatus(data.message, !data.success))
        .catch(() => showNtfyStatus('Failed to save push notification settings', true));
});
document.getElementById('ntfyTestBtn').addEventListener('click', () => {
    const topicUrl = document.getElementById('ntfyTopicInput').value.trim();
    showNtfyStatus('Sending…', false);
    fetch('../auth/test_ntfy_notification.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ topic_url: topicUrl })
    })
        .then(res => res.json())
        .then(data => showNtfyStatus(data.message, !data.success))
        .catch(() => showNtfyStatus('Failed to send test push', true));
});
document.getElementById('ntfyEnabledToggle').addEventListener('change', (ev) => {
    const enabled = ev.target.checked;
    fetch('../auth/get_ntfy_settings.php')
        .then(res => res.json())
        .then(data => fetch('../auth/update_ntfy_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ topic_url: data.topic_url || '', enabled })
        }))
        .then(res => res.json())
        .then(data => showNtfyStatus(enabled ? 'Push notifications on' : 'Push notifications off', !data.success))
        .catch(() => showNtfyStatus('Failed to update push toggle', true));
});

// ---------- "What's Due" popup settings ----------
function showDuePopupStatus(message, isError) {
    const el = document.getElementById('duePopupStatusMsg');
    el.textContent = message;
    el.style.color = isError ? 'var(--critical)' : 'var(--good)';
    el.style.display = 'block';
}
function loadDuePopupSettings() {
    fetch('../auth/get_due_popup_settings.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) document.getElementById('duePopupDaysInput').value = data.days_ahead;
        })
        .catch(() => {});
}
document.getElementById('duePopupSaveBtn').addEventListener('click', () => {
    const daysAhead = document.getElementById('duePopupDaysInput').value.trim();
    fetch('../auth/update_due_popup_settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ days_ahead: daysAhead })
    })
        .then(res => res.json())
        .then(data => showDuePopupStatus(data.message, !data.success))
        .catch(() => showDuePopupStatus('Failed to save due items window', true));
});

// ---------- init ----------
loadAccountsList();
loadSlackSettings();
loadNtfySettings();
loadDuePopupSettings();

window.addEventListener('click', function (e) {
    if (e.target === document.getElementById('addUserModal')) closeAddUserModal();
    if (e.target === document.getElementById('editUserModal')) closeEditUserModal();
});
</script>

</body>
</html>
