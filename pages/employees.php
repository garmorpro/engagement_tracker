<?php
require_once '../auth/session_check.php';
require_once '../path.php';
require_once '../includes/functions.php';

// Same gate as settings.php — role alone, no separate PIN.
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
    <title>Employees - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --ink: #1B3A5C; --ink-soft: #4A6483; --paper: #F4F6F8; --card: #FFFFFF;
            --line: #DCE1E7; --line-strong: #C2CAD3; --text: #16202B; --text-muted: #5B6B7C;
            --critical: #B3261E; --critical-tint: rgba(179, 38, 30, 0.07); --caution: #A66A00; --good: #1F7A54;
            --manager: #1B3A5C; --staff: #1F7A54; --intern: #A66A00; --senior: #7A4FB0;
        }
        body.dark-mode {
            --ink: #6E9FCB; --ink-soft: #7C93AA; --paper: #10161D; --card: #171F28;
            --line: #2A343E; --line-strong: #3C4854; --text: #E7ECF1; --text-muted: #93A1AF;
            --critical: #E5766F; --critical-tint: rgba(229, 118, 111, 0.1); --caution: #D3A44E; --good: #5FB98A;
            --manager: #6E9FCB; --staff: #5FB98A; --intern: #D3A44E; --senior: #B79AE0;
        }
        * { box-sizing: border-box; }
        body { background: var(--paper); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; transition: background-color 0.2s ease, color 0.2s ease; }

        .top-header { background: var(--card); border-bottom: 1px solid var(--line); padding: 0 1.75rem; position: sticky; top: 0; z-index: 100; }
        .header-inner { max-width: 1000px; margin: 0 auto; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 2.5rem; }
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
        .profile-dropdown { position: absolute; top: calc(100% + 8px); right: 0; width: 240px; background: var(--card); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,0.14); display: none; z-index: 60; }
        .profile-dropdown.active { display: block; }
        .profile-dropdown-header { display: flex; gap: 10px; align-items: center; padding: 0.9rem 1rem; border-bottom: 1px solid var(--line); }
        .profile-dropdown-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: var(--card); font-weight: 700; font-size: 12.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .profile-dropdown-name { font-size: 13px; font-weight: 700; }
        .profile-dropdown-email { font-size: 11.5px; color: var(--text-muted); }
        .profile-dropdown-menu { padding: 0.4rem; }
        .profile-dropdown-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 12.5px; color: var(--text); text-decoration: none; }
        .profile-dropdown-item:hover { background: var(--paper); }
        .profile-dropdown-item.logout { color: var(--critical); }

        .main-container { max-width: 1000px; margin: 0 auto; padding: 2.25rem 1.75rem 4rem; }
        .page-head h1 { font-size: 24px; margin: 0; font-weight: 700; letter-spacing: -0.015em; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin: 4px 0 0; }
        hr.rule { border: none; border-top: 1px solid var(--line); margin: 1.1rem 0 1.75rem; }

        .btn-primary { background: var(--ink); border: none; color: var(--card); font-weight: 700; border-radius: 8px; padding: 0.65rem 1.2rem; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-primary:hover { background: color-mix(in srgb, var(--ink) 85%, black); }
        .btn-secondary { background: var(--paper); border: 1px solid var(--line); color: var(--text-muted); font-weight: 700; border-radius: 8px; padding: 0.6rem 1.1rem; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-secondary:hover { background: var(--line); color: var(--text); }

        .roster-search { width: 100%; max-width: 280px; padding: 0.55rem 0.8rem; border: 1px solid var(--line); border-radius: 8px; background: var(--card); color: var(--text); font-size: 13px; margin-bottom: 1rem; }
        .roster-search:focus { outline: none; border-color: var(--ink); }

        .roster-item { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 0.8rem 1rem; display: flex; align-items: center; gap: 0.85rem; margin-bottom: 0.55rem; }
        .roster-avatar { width: 36px; height: 36px; border-radius: 9px; color: #fff; font-weight: 700; font-size: 12.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .roster-info { flex: 1; min-width: 0; }
        .roster-name { font-size: 13.5px; font-weight: 700; color: var(--text); }
        .roster-role { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); margin-top: 1px; }
        .roster-restricted { font-size: 11px; font-weight: 600; color: var(--caution); margin-top: 3px; display: flex; align-items: center; gap: 4px; }
        .roster-actions { display: flex; gap: 4px; flex-shrink: 0; }
        .roster-actions button { background: var(--paper); border: 1px solid var(--line); color: var(--text-muted); cursor: pointer; font-size: 12px; width: 30px; height: 30px; border-radius: 7px; display: flex; align-items: center; justify-content: center; }
        .roster-actions button:hover { border-color: var(--ink); color: var(--ink); }
        .roster-actions button.danger:hover { border-color: var(--critical); color: var(--critical); }
        .roster-actions button.restrict:hover { border-color: var(--caution); color: var(--caution); }
        .roster-empty { text-align: center; padding: 2.5rem 1rem; color: var(--text-muted); font-size: 13px; }

        .form-label { font-size: 11.5px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.5rem; font-weight: 700; display: block; }
        .form-control { background: var(--paper); border: 1px solid var(--line); color: var(--text); border-radius: 8px; padding: 0.7rem 0.9rem; font-size: 14px; width: 100%; }
        .form-control:focus { outline: none; border-color: var(--ink); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 10%, transparent); }
        .mb-3 { margin-bottom: 1rem; }
        .modal-overlay { position: fixed; inset: 0; background: rgba(10, 15, 22, 0.55); display: none; align-items: center; justify-content: center; z-index: 1000; padding: 1.5rem; }
        .modal-overlay.active { display: flex; }
        .modal-box { background: var(--card); border: 1px solid var(--line); border-radius: 16px; padding: 1.9rem; width: 100%; max-width: 400px; box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5); position: relative; }
        .modal-close { position: absolute; top: 1rem; right: 1rem; width: 30px; height: 30px; border: none; background: transparent; color: var(--text-muted); font-size: 20px; cursor: pointer; padding: 0; line-height: 1; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .modal-close:hover { color: var(--text); background: var(--paper); }
        .modal-header { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.4rem; padding-bottom: 1.2rem; border-bottom: 1px solid var(--line); }
        .modal-header-icon { width: 42px; height: 42px; background: color-mix(in srgb, var(--ink) 14%, transparent); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--ink); font-size: 19px; flex-shrink: 0; }
        .modal-header h5 { font-size: 15.5px; font-weight: 700; color: var(--text); margin: 0; flex: 1; }
        .modal-note { font-size: 11px; color: var(--text-muted); margin-top: 0.5rem; line-height: 1.5; }
        .button-group { display: flex; gap: 1rem; margin-top: 1.5rem; }
        .button-group button { flex: 1; }

        .swal2-popup { background: var(--card) !important; border: 1px solid var(--line) !important; }
        .swal2-title { color: var(--text) !important; }
        .swal2-html-container { color: var(--text-muted) !important; }
        .swal2-confirm { background: var(--ink) !important; }
        .swal2-cancel { background: var(--paper) !important; border: 1px solid var(--line) !important; color: var(--text-muted) !important; }

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
            <a href="calendar.php">Calendar</a>
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
                        <a href="settings.php" class="profile-dropdown-item">
                            <i class="bi bi-gear"></i> Settings
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
            <h1>Employees</h1>
            <p class="page-sub">The roster used for team autocomplete and DOL training restrictions.</p>
        </div>
        <div style="display: flex; gap: 0.6rem;">
            <a href="settings.php" class="btn-secondary"><i class="bi bi-arrow-left"></i> Settings</a>
            <button type="button" class="btn-primary" id="addEmployeeBtn"><i class="bi bi-person-plus"></i> Add Employee</button>
        </div>
    </div>
    <hr class="rule">

    <input type="text" class="roster-search" id="rosterSearch" placeholder="Search employees&hellip;">
    <div id="rosterList"></div>
</div>

<!-- Add/Edit Employee Modal -->
<div class="modal-overlay" id="employeeModal">
    <div class="modal-box">
        <button class="modal-close" onclick="closeEmployeeModal()">&times;</button>
        <div class="modal-header">
            <div class="modal-header-icon"><i class="bi bi-person-fill-add" id="employeeModalIcon"></i></div>
            <h5 id="employeeModalTitle">Add Employee</h5>
        </div>
        <form id="employeeForm">
            <input type="hidden" id="employeeFormId">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" class="form-control" id="employeeFormName" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-control" id="employeeFormRole">
                    <option value="manager">Manager</option>
                    <option value="senior">Senior</option>
                    <option value="staff" selected>Staff</option>
                    <option value="intern">Intern</option>
                </select>
            </div>
            <div class="modal-note" id="employeeFormNote"></div>
            <div class="button-group">
                <button type="button" class="btn-secondary" onclick="closeEmployeeModal()" style="flex:1;">Cancel</button>
                <button type="submit" class="btn-primary" style="flex:1; justify-content:center;">Save</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script>
const darkModeBtn = document.querySelector('.icon-btn[title="Dark mode"]');
function updateDarkModeIcon(isDark) {
    const icon = darkModeBtn?.querySelector('i');
    if (icon) { icon.classList.toggle('bi-moon', !isDark); icon.classList.toggle('bi-sun', isDark); }
}
const isDarkMode = localStorage.getItem('darkMode') === 'true';
if (isDarkMode) document.body.classList.add('dark-mode');
updateDarkModeIcon(isDarkMode);
darkModeBtn?.addEventListener('click', () => {
    const isDark = document.body.classList.toggle('dark-mode');
    localStorage.setItem('darkMode', isDark);
    updateDarkModeIcon(isDark);
});

const profileToggle = document.getElementById('profileToggle');
const profileDropdown = document.getElementById('profileDropdown');
profileToggle?.addEventListener('click', (e) => { e.stopPropagation(); profileDropdown?.classList.toggle('active'); });
document.addEventListener('click', (e) => {
    if (!profileToggle?.contains(e.target) && !profileDropdown?.contains(e.target)) profileDropdown?.classList.remove('active');
});

const ROLE_COLOR_VAR = { manager: 'var(--manager)', senior: 'var(--senior)', staff: 'var(--staff)', intern: 'var(--intern)' };
const ROLE_LABELS = { manager: 'Manager', senior: 'Senior', staff: 'Staff', intern: 'Intern' };

let allEmployees = [];

function initials(name) {
    return (name || '').split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('');
}
function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
function escAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

const ROLE_SORT_ORDER = { manager: 0, senior: 1, staff: 2, intern: 3 };
function firstName(fullName) {
    return (fullName || '').trim().split(/\s+/)[0] || '';
}
function sortRoster(employees) {
    return [...employees].sort((a, b) => {
        const roleDiff = (ROLE_SORT_ORDER[(a.emp_role || '').toLowerCase()] ?? 99) - (ROLE_SORT_ORDER[(b.emp_role || '').toLowerCase()] ?? 99);
        if (roleDiff !== 0) return roleDiff;
        return firstName(a.emp_name).localeCompare(firstName(b.emp_name), undefined, { sensitivity: 'base' });
    });
}

async function loadEmployees() {
    const list = document.getElementById('rosterList');
    try {
        const res = await fetch('../api/get-all-employees.php');
        const data = await res.json();
        allEmployees = sortRoster(data.success ? (data.employees || []) : []);
        renderRoster(allEmployees);
    } catch (err) {
        console.error('Error:', err);
        list.innerHTML = '<div class="roster-empty">Failed to load employees.</div>';
    }
}

function renderRoster(employees) {
    const list = document.getElementById('rosterList');
    if (!employees.length) {
        list.innerHTML = '<div class="roster-empty">No employees found.</div>';
        return;
    }
    list.innerHTML = employees.map(e => {
        const roleKey = (e.emp_role || '').toLowerCase();
        const restricted = (e.emp_restricted_criteria || '').split(',').map(s => s.trim()).filter(Boolean);
        return `
            <div class="roster-item">
                <div class="roster-avatar" style="background:${ROLE_COLOR_VAR[roleKey] || 'var(--ink)'}">${initials(e.emp_name)}</div>
                <div class="roster-info">
                    <div class="roster-name">${escapeHtml(e.emp_name)}</div>
                    <div class="roster-role">${ROLE_LABELS[roleKey] || e.emp_role}</div>
                    ${restricted.length ? `<div class="roster-restricted"><i class="bi bi-exclamation-triangle-fill"></i> Not trained: ${escapeHtml(restricted.join(', '))}</div>` : ''}
                </div>
                <div class="roster-actions">
                    <button type="button" class="restrict" title="Training restrictions" data-action="restrict" data-id="${e.emp_id}"><i class="bi bi-shield-exclamation"></i></button>
                    <button type="button" title="Edit" data-action="edit" data-id="${e.emp_id}"><i class="bi bi-pencil-square"></i></button>
                    <button type="button" class="danger" title="Remove" data-action="delete" data-id="${e.emp_id}"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        `;
    }).join('');

    list.querySelectorAll('[data-action="edit"]').forEach(btn => btn.addEventListener('click', () => openEmployeeModal(btn.dataset.id)));
    list.querySelectorAll('[data-action="delete"]').forEach(btn => btn.addEventListener('click', () => deleteEmployee(btn.dataset.id)));
    list.querySelectorAll('[data-action="restrict"]').forEach(btn => btn.addEventListener('click', () => openRestrictionsEditor(btn.dataset.id)));
}

document.getElementById('rosterSearch').addEventListener('input', (ev) => {
    const q = ev.target.value.trim().toLowerCase();
    renderRoster(allEmployees.filter(e => e.emp_name.toLowerCase().includes(q)));
});

// ---------- Add / Edit ----------
function openEmployeeModal(empId) {
    const form = document.getElementById('employeeForm');
    form.reset();
    const note = document.getElementById('employeeFormNote');
    if (empId) {
        const emp = allEmployees.find(e => String(e.emp_id) === String(empId));
        document.getElementById('employeeModalTitle').textContent = 'Edit Employee';
        document.getElementById('employeeFormId').value = emp.emp_id;
        document.getElementById('employeeFormName').value = emp.emp_name;
        document.getElementById('employeeFormRole').value = (emp.emp_role || '').toLowerCase();
        note.textContent = "Saving updates this person's name/role on every engagement they're already staffed on too, not just new assignments.";
    } else {
        document.getElementById('employeeModalTitle').textContent = 'Add Employee';
        document.getElementById('employeeFormId').value = '';
        note.textContent = '';
    }
    document.getElementById('employeeModal').classList.add('active');
    document.getElementById('employeeFormName').focus();
}
function closeEmployeeModal() {
    document.getElementById('employeeModal').classList.remove('active');
}
document.getElementById('addEmployeeBtn').addEventListener('click', () => openEmployeeModal(null));
document.getElementById('employeeModal').addEventListener('click', (ev) => {
    if (ev.target.id === 'employeeModal') closeEmployeeModal();
});

document.getElementById('employeeForm').addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const empId = document.getElementById('employeeFormId').value;
    const empName = document.getElementById('employeeFormName').value.trim();
    const empRole = document.getElementById('employeeFormRole').value;
    if (!empName) return;

    const endpoint = empId ? '../api/update-employee.php' : '../api/add-employee.php';
    const payload = empId ? { emp_id: parseInt(empId), emp_name: empName, emp_role: empRole } : { emp_name: empName, emp_role: empRole };

    try {
        const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            closeEmployeeModal();
            loadEmployees();
            if (data.engagements_updated) {
                const n = data.engagements_updated;
                Swal.fire({ icon: 'success', title: 'Updated', text: `Also updated ${n} engagement team assignment${n === 1 ? '' : 's'} for this person.`, timer: 2200, showConfirmButton: false });
            }
        } else {
            Swal.fire('Error', data.message || 'Failed to save employee', 'error');
        }
    } catch (err) {
        console.error('Error:', err);
        Swal.fire('Error', 'Failed to save employee', 'error');
    }
});

// ---------- Delete ----------
async function deleteEmployee(empId) {
    const emp = allEmployees.find(e => String(e.emp_id) === String(empId));
    const result = await Swal.fire({
        title: 'Remove employee?',
        text: `Removes "${emp?.emp_name || ''}" from the roster and autocomplete. Engagements they're already staffed on are unaffected.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: 'var(--critical)',
        confirmButtonText: 'Remove'
    });
    if (!result.isConfirmed) return;

    try {
        const res = await fetch('../api/delete-employee.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ emp_id: parseInt(empId) })
        });
        const data = await res.json();
        if (data.success) loadEmployees();
        else Swal.fire('Error', data.message || 'Failed to remove employee', 'error');
    } catch (err) {
        console.error('Error:', err);
        Swal.fire('Error', 'Failed to remove employee', 'error');
    }
}

// ---------- Restrictions ----------
async function openRestrictionsEditor(empId) {
    const emp = allEmployees.find(e => String(e.emp_id) === String(empId));
    if (!emp) return;
    const current = (emp.emp_restricted_criteria || '').split(',').map(s => s.trim()).filter(Boolean).join(', ');

    const result = await Swal.fire({
        title: 'Training restrictions',
        html: `<div style="text-align:left; font-size:12.5px; color:var(--text-muted); margin-bottom:0.75rem;">
                   Criteria <strong>${escapeHtml(emp.emp_name)}</strong> hasn't completed training on yet — the DOL Generator will never assign these to them, for any engagement, until this is cleared.
               </div>`,
        input: 'text',
        inputValue: current,
        inputPlaceholder: 'e.g. CC6, CC9, Privacy',
        showCancelButton: true,
        confirmButtonText: 'Save'
    });
    if (!result.isConfirmed) return;

    const restricted = result.value.split(',').map(s => s.trim()).filter(Boolean);
    try {
        const res = await fetch('../api/update-employee-restrictions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ emp_name: emp.emp_name, role: emp.emp_role, restricted })
        });
        const data = await res.json();
        if (data.success) loadEmployees();
        else Swal.fire('Error', data.message || 'Failed to save restrictions', 'error');
    } catch (err) {
        console.error('Error:', err);
        Swal.fire('Error', 'Failed to save restrictions', 'error');
    }
}

loadEmployees();
</script>

</body>
</html>
