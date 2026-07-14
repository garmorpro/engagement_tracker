<?php
require_once '../../auth/session_check.php';
require_once '../../path.php';
require_once '../../includes/functions.php';

$allEngagements = getAllEngagements($conn);
$activeEngagements = array_filter($allEngagements, fn($e) => $e['eng_status'] !== 'archived');
usort($activeEngagements, fn($a, $b) => strcasecmp($a['eng_name'], $b['eng_name']));

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
    <title>DOL Generator - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/styles/main.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --ink: #1B3A5C; --paper: #F4F6F8; --card: #FFFFFF;
            --line: #DCE1E7; --line-strong: #C2CAD3;
            --text: #16202B; --text-muted: #5B6B7C;
            --critical: #B3261E; --caution: #A66A00; --good: #1F7A54; --senior: #7A4FB0;
            --manager: #1B3A5C; --staff: #1F7A54; --intern: #A66A00;
        }
        body.dark-mode {
            --ink: #6E9FCB; --paper: #10161D; --card: #171F28;
            --line: #2A343E; --line-strong: #3C4854;
            --text: #E7ECF1; --text-muted: #93A1AF;
            --critical: #E5766F; --caution: #D3A44E; --good: #5FB98A; --senior: #B79AE0;
            --manager: #6E9FCB; --staff: #5FB98A; --intern: #D3A44E;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-variant-numeric: tabular-nums; }

        .top-header { background: var(--card); border-bottom: 1px solid var(--line); padding: 0 1.75rem; position: sticky; top: 0; z-index: 100; }
        .header-inner { max-width: 900px; margin: 0 auto; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 2.5rem; }
        .brand { display: flex; align-items: center; gap: 0.6rem; text-decoration: none; }
        .brand-icon { width: 26px; height: 26px; border-radius: 7px; background: var(--ink); color: var(--card); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .brand-mark { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; color: var(--text); }
        .main-nav { display: flex; gap: 1.6rem; }
        .main-nav a { font-size: 13px; font-weight: 600; color: var(--text-muted); text-decoration: none; padding: 4px 0; border-bottom: 2px solid transparent; }
        .main-nav a.active { color: var(--text); border-bottom-color: var(--ink); }
        .main-nav a:hover { color: var(--text); }
        .header-right { display: flex; align-items: center; gap: 0.65rem; margin-left: auto; }
        .icon-btn { width: 32px; height: 32px; border-radius: 6px; border: none; background: transparent; color: var(--text-muted); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 15px; }
        .icon-btn:hover { background: color-mix(in srgb, var(--ink) 8%, var(--paper)); color: var(--text); }
        .profile-section { position: relative; margin-left: 0.4rem; padding-left: 0.75rem; border-left: 1px solid var(--line); }
        .profile-wrapper { display: flex; align-items: center; gap: 6px; cursor: pointer; }
        .profile-btn { width: 30px; height: 30px; border-radius: 50%; background: var(--ink); color: var(--card); border: none; font-weight: 700; font-size: 11.5px; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .profile-dropdown-toggle { border: none; background: transparent; color: var(--text-muted); cursor: pointer; padding: 2px; }
        .profile-dropdown {
            position: absolute; top: calc(100% + 8px); right: 0; width: 200px;
            background: var(--card); border: 1px solid var(--line); border-radius: 10px; box-shadow: 0 12px 32px rgba(0,0,0,0.14);
            display: none; z-index: 60;
        }
        .profile-dropdown.active { display: block; }
        .profile-dropdown-menu { padding: 0.4rem; }
        .profile-dropdown-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 12.5px; color: var(--text); text-decoration: none; }
        .profile-dropdown-item:hover { background: var(--paper); }
        .profile-dropdown-item.logout { color: var(--critical); }

        .main-container { max-width: 900px; margin: 0 auto; padding: 2.25rem 1.75rem 4rem; }
        .page-head h1 { font-size: 24px; margin: 0; font-weight: 700; letter-spacing: -0.015em; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin: 4px 0 0; }
        hr.rule { border: none; border-top: 1px solid var(--line); margin: 1.1rem 0 1.75rem; }
        .back-link { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 12.5px; font-weight: 600; color: var(--text-muted); text-decoration: none; margin-bottom: 1rem; cursor: pointer; border: none; background: none; padding: 0; }
        .back-link:hover { color: var(--ink); }

        .gen-card { background: var(--card); border: 1px solid var(--line); border-radius: 13px; padding: 1.5rem; margin-bottom: 1rem; }
        .gen-card h2 { font-size: 14px; font-weight: 700; margin: 0 0 1.1rem; display: flex; align-items: center; gap: 0.5rem; }
        .step-num { width: 20px; height: 20px; border-radius: 50%; background: var(--ink); color: var(--card); font-size: 10.5px; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }

        .field-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.5rem; display: block; }
        .select-input, .text-input, .textarea-input {
            width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--line); border-radius: 8px;
            background: var(--paper); color: var(--text); font-size: 13.5px; font-family: inherit;
        }
        .select-input:focus, .text-input:focus, .textarea-input:focus { outline: none; border-color: var(--ink); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 10%, transparent); }
        .textarea-input { min-height: 76px; resize: vertical; }
        .field-hint { font-size: 11px; color: var(--text-muted); margin-top: 0.4rem; }
        .field-error { font-size: 11.5px; color: var(--critical); margin-top: 0.5rem; font-weight: 600; }

        .audit-type-pills { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .audit-pill { padding: 0.5rem 0.9rem; border-radius: 8px; border: 1.5px solid var(--line); background: var(--paper); color: var(--text-muted); font-size: 12.5px; font-weight: 700; cursor: pointer; }
        .audit-pill.active { border-color: var(--ink); background: color-mix(in srgb, var(--ink) 10%, transparent); color: var(--ink); }

        .member-row { display: flex; align-items: center; gap: 0.85rem; padding: 0.75rem 0; border-bottom: 1px solid var(--line); }
        .member-row:last-child { border-bottom: none; }
        .member-avatar { width: 34px; height: 34px; border-radius: 8px; color: #fff; font-weight: 700; font-size: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .member-info { flex: 1; min-width: 0; }
        .member-name { font-size: 13px; font-weight: 700; }
        .member-role { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: var(--text-muted); }
        .hours-input-wrap { display: flex; align-items: center; gap: 0.4rem; }
        .hours-input { width: 64px; padding: 0.45rem 0.5rem; border: 1px solid var(--line); border-radius: 7px; background: var(--paper); color: var(--text); font-size: 13px; text-align: center; }
        .hours-input:focus { outline: none; border-color: var(--ink); }
        .hours-suffix { font-size: 11.5px; color: var(--text-muted); font-weight: 600; }
        .empty-note { text-align: center; padding: 1.5rem 0.5rem; color: var(--text-muted); font-size: 12.5px; }

        .generate-btn { width: 100%; padding: 0.85rem; border-radius: 9px; border: none; background: var(--ink); color: var(--card); font-weight: 700; font-size: 13.5px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem; }
        .generate-btn:hover { background: color-mix(in srgb, var(--ink) 85%, black); }
        .generate-btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .result-member { background: var(--paper); border: 1px solid var(--line); border-radius: 11px; padding: 1rem 1.1rem; margin-bottom: 0.7rem; }
        .result-member-head { display: flex; align-items: center; gap: 0.7rem; margin-bottom: 0.7rem; }
        .result-share { margin-left: auto; font-size: 11px; font-weight: 700; color: var(--text-muted); background: var(--card); border: 1px solid var(--line); padding: 3px 9px; border-radius: 20px; white-space: nowrap; }
        .result-chips { display: flex; flex-wrap: wrap; gap: 0.4rem; }
        .result-chip { font-size: 11px; font-weight: 700; padding: 4px 9px; border-radius: 6px; background: color-mix(in srgb, var(--ink) 10%, transparent); color: var(--ink); }

        .save-warning { display: flex; gap: 0.7rem; align-items: flex-start; padding: 0.85rem 1rem; background: color-mix(in srgb, var(--caution) 10%, transparent); border: 1px solid color-mix(in srgb, var(--caution) 30%, var(--line)); border-radius: 10px; margin-bottom: 1rem; font-size: 12px; color: var(--text); line-height: 1.5; }
        .save-warning i { color: var(--caution); font-size: 15px; flex-shrink: 0; margin-top: 1px; }
        .action-row { display: flex; gap: 0.6rem; }
        .btn-outline { flex: 1; padding: 0.75rem; border-radius: 9px; border: 1px solid var(--line); background: var(--card); color: var(--text-muted); font-weight: 700; font-size: 13px; cursor: pointer; }
        .btn-outline:hover { border-color: var(--line-strong); color: var(--text); }
        .btn-save { flex: 2; padding: 0.75rem; border-radius: 9px; border: none; background: var(--good); color: #fff; font-weight: 700; font-size: 13px; cursor: pointer; }
        .btn-save:hover { background: color-mix(in srgb, var(--good) 85%, black); }
        .btn-save:disabled { opacity: 0.6; cursor: not-allowed; }

        .hidden { display: none !important; }

        /* SweetAlert2 theming, matching dashboard.php */
        .swal2-popup { background: var(--card) !important; border: 1px solid var(--line) !important; }
        .swal2-title { color: var(--text) !important; }
        .swal2-html-container { color: var(--text-muted) !important; }
        .swal2-confirm { background: var(--ink) !important; }
        .swal2-cancel { background: var(--paper) !important; border: 1px solid var(--line) !important; color: var(--text-muted) !important; }
    </style>
</head>
<body>

<div class="top-header">
    <div class="header-inner">
        <a href="../dashboard.php" class="brand">
            <span class="brand-icon">ET</span>
            <span class="brand-mark">Engagement Tracker</span>
        </a>
        <nav class="main-nav">
            <a href="../dashboard.php">Engagements</a>
            <a class="active" href="../tools.php">Tools</a>
        </nav>
        <div class="header-right">
            <button class="icon-btn" id="darkModeBtn" title="Dark mode">
                <i class="bi bi-moon"></i>
            </button>
            <div class="profile-section">
                <div class="profile-wrapper" id="profileToggle">
                    <button class="profile-btn" title="Profile"><?php echo $initials; ?></button>
                    <button class="profile-dropdown-toggle"><i class="bi bi-chevron-down"></i></button>
                </div>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-menu">
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
    <a class="back-link" href="../tools.php"><i class="bi bi-chevron-left"></i> Tools</a>
    <div class="page-head">
        <h1>DOL Generator</h1>
        <p class="page-sub">Split criteria across your team by hours</p>
    </div>
    <hr class="rule">

    <!-- Step 1: Engagement -->
    <div class="gen-card">
        <h2><span class="step-num">1</span>Engagement</h2>
        <label class="field-label">Select Engagement</label>
        <select class="select-input" id="engagementSelect">
            <option value="">Select an engagement&hellip;</option>
            <?php foreach ($activeEngagements as $eng): ?>
                <option value="<?php echo htmlspecialchars($eng['eng_idno']); ?>"><?php echo htmlspecialchars($eng['eng_name']); ?> &mdash; <?php echo htmlspecialchars($eng['eng_idno']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div id="setupSections" class="hidden">
        <!-- Step 2: Audit Type -->
        <div class="gen-card">
            <h2><span class="step-num">2</span>Audit Type</h2>
            <label class="field-label">Which DOL does this split apply to?</label>
            <div class="audit-type-pills" id="auditTypePills"></div>
            <div class="field-hint">Only audit types assigned to this engagement show up here.</div>
        </div>

        <!-- Step 3: Team Hours -->
        <div class="gen-card">
            <h2><span class="step-num">3</span>Team Hours</h2>
            <div id="teamHoursList"></div>
            <div class="field-hint">Pulled live from this engagement's team (Senior/Staff/Intern &mdash; Manager excluded). Hours aren't saved anywhere, just used to compute this split.</div>
        </div>

        <!-- Step 4: Criteria -->
        <div class="gen-card">
            <h2><span class="step-num">4</span>Criteria to Split</h2>
            <label class="field-label">Criteria (comma or line separated)</label>
            <textarea class="textarea-input" id="criteriaInput"></textarea>
            <div class="field-hint" id="criteriaHint"></div>
        </div>

        <div id="genErrorBox" class="field-error hidden" style="margin-bottom: 0.75rem;"></div>
        <button class="generate-btn" id="generateBtn"><i class="bi bi-magic"></i> Generate Split</button>
    </div>

    <!-- Results -->
    <div id="resultSection" class="hidden">
        <hr class="rule">
        <div id="resultSummary" class="page-sub" style="margin-bottom: 1.25rem;"></div>
        <div id="resultMembers"></div>
        <div class="save-warning">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div id="saveWarningText"></div>
        </div>
        <div class="action-row">
            <button class="btn-outline" id="backToEditBtn">Back &amp; Adjust</button>
            <button class="btn-save" id="saveBtn"><i class="bi bi-check-lg"></i> Save to Engagement</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
<script>
    const isDarkMode = localStorage.getItem('darkMode') === 'true';
    if (isDarkMode) document.body.classList.add('dark-mode');
    const darkModeBtn = document.getElementById('darkModeBtn');
    function updateDarkModeIcon(isDark) {
        const icon = darkModeBtn?.querySelector('i');
        if (icon) { icon.classList.toggle('bi-moon', !isDark); icon.classList.toggle('bi-sun', isDark); }
    }
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

    // ===================================================================
    // DOL GENERATOR
    // ===================================================================
    const DOL_AUDIT_TYPES = {
        'SOC 1':   'emp_soc1_dol',
        'SOC 2':   'emp_soc2_dol',
        'HIPAA':   'emp_hipaa_dol',
        'HITRUST': 'emp_hitrust_dol',
        'FISMA':   'emp_fisma_dol'
    };
    const ROLE_COLOR_VAR = { senior: 'var(--senior)', staff: 'var(--staff)', intern: 'var(--intern)' };
    const ROLE_LABELS = { senior: 'Senior', staff: 'Staff', intern: 'Intern' };

    let engagementData = null; // { engagement, team } from the API
    let eligibleMembers = [];  // team members with role senior/staff/intern
    let selectedAuditType = null;
    let lastResult = null;     // computed split, kept for the Save step

    function initials(name) {
        return (name || '').split(' ').filter(Boolean).map(p => p[0].toUpperCase()).join('');
    }
    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Derives the SOC 2 criteria list from the engagement's free-text TSC field.
    // Security is always the 9 Common Criteria; the other four categories are
    // each represented by their own name, matching how this firm actually
    // divides SOC 2 work (not the more granular sub-criteria some firms use).
    function deriveSoc2Criteria(engTsc) {
        const tsc = (engTsc || '').toLowerCase();
        const criteria = [];
        if (tsc.includes('security')) criteria.push('CC1', 'CC2', 'CC3', 'CC4', 'CC5', 'CC6', 'CC7', 'CC8', 'CC9');
        if (tsc.includes('availability')) criteria.push('Availability');
        if (tsc.includes('confidentiality')) criteria.push('Confidentiality');
        if (tsc.includes('processing integrity')) criteria.push('Processing Integrity');
        if (tsc.includes('privacy')) criteria.push('Privacy');
        return criteria;
    }

    function parseCriteriaInput(text) {
        return text.split(/[,\n]/).map(c => c.trim()).filter(Boolean);
    }

    // Largest-remainder (Hamilton) apportionment: converts each member's exact
    // fractional share of the criteria count into a whole number, guaranteed
    // to sum to exactly the total criteria count. Criteria are then assigned
    // as sequential chunks in the order they were entered, so it's obvious
    // why any given person got what they got.
    function computeSplit(members, criteria) {
        const totalHours = members.reduce((sum, m) => sum + m.hours, 0);
        const n = criteria.length;

        const withShares = members.map(m => {
            const exact = totalHours > 0 ? (m.hours / totalHours) * n : 0;
            return { ...m, exact, count: Math.floor(exact), remainder: exact - Math.floor(exact) };
        });

        let assigned = withShares.reduce((sum, m) => sum + m.count, 0);
        let remaining = n - assigned;

        const byRemainder = [...withShares].sort((a, b) => b.remainder - a.remainder);
        for (let i = 0; i < remaining; i++) byRemainder[i].count += 1;

        let idx = 0;
        withShares.forEach(m => {
            m.assigned = criteria.slice(idx, idx + m.count);
            idx += m.count;
        });

        return withShares;
    }

    // ---------- Engagement selection ----------
    document.getElementById('engagementSelect').addEventListener('change', async (ev) => {
        const engId = ev.target.value;
        resetResult();
        if (!engId) {
            document.getElementById('setupSections').classList.add('hidden');
            return;
        }
        try {
            const res = await fetch('../../api/get-engagement-details.php?id=' + encodeURIComponent(engId));
            const data = await res.json();
            if (!data.success) {
                Swal.fire('Error', data.message || 'Failed to load engagement', 'error');
                return;
            }
            engagementData = data;
            eligibleMembers = (data.team || []).filter(m => ['senior', 'staff', 'intern'].includes((m.role || '').toLowerCase()));
            renderAuditTypePills();
            renderTeamHours();
            document.getElementById('setupSections').classList.remove('hidden');
        } catch (err) {
            console.error('Error:', err);
            Swal.fire('Error', 'Failed to load engagement', 'error');
        }
    });

    function renderAuditTypePills() {
        const container = document.getElementById('auditTypePills');
        const auditTypes = (engagementData.engagement.eng_audit_type || '').split(',').map(t => t.trim()).filter(Boolean);
        const relevant = auditTypes.filter(t => DOL_AUDIT_TYPES.hasOwnProperty(t));

        if (!relevant.length) {
            container.innerHTML = '<div class="empty-note">This engagement has no audit types with DOL support assigned.</div>';
            selectedAuditType = null;
            return;
        }

        selectedAuditType = relevant[0];
        container.innerHTML = relevant.map(t => `<button type="button" class="audit-pill ${t === selectedAuditType ? 'active' : ''}" data-type="${t}">${escapeHtml(t)}</button>`).join('');
        container.querySelectorAll('.audit-pill').forEach(btn => {
            btn.addEventListener('click', () => {
                container.querySelectorAll('.audit-pill').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                selectedAuditType = btn.dataset.type;
                updateCriteriaForAuditType();
            });
        });
        updateCriteriaForAuditType();
    }

    function updateCriteriaForAuditType() {
        const textarea = document.getElementById('criteriaInput');
        const hint = document.getElementById('criteriaHint');
        if (selectedAuditType === 'SOC 2') {
            const derived = deriveSoc2Criteria(engagementData.engagement.eng_tsc);
            textarea.value = derived.join(', ');
            hint.textContent = derived.length
                ? `Derived from this engagement's TSC ("${engagementData.engagement.eng_tsc || ''}"). Edit if needed.`
                : `This engagement's TSC field doesn't mention any known SOC 2 category — enter criteria manually.`;
        } else {
            textarea.value = '';
            hint.textContent = 'Paste or type the criteria to split for this audit type.';
        }
    }

    function renderTeamHours() {
        const container = document.getElementById('teamHoursList');
        if (!eligibleMembers.length) {
            container.innerHTML = '<div class="empty-note">No Senior, Staff, or Intern team members on this engagement yet. Add them from the Engagements list first.</div>';
            return;
        }
        container.innerHTML = eligibleMembers.map(m => {
            const roleKey = (m.role || '').toLowerCase();
            return `
                <div class="member-row" data-emp-id="${m.emp_id}">
                    <div class="member-avatar" style="background:${ROLE_COLOR_VAR[roleKey] || 'var(--ink)'}">${initials(m.emp_name)}</div>
                    <div class="member-info">
                        <div class="member-name">${escapeHtml(m.emp_name)}</div>
                        <div class="member-role">${ROLE_LABELS[roleKey] || m.role}</div>
                    </div>
                    <div class="hours-input-wrap">
                        <input type="number" class="hours-input member-hours-input" min="0" step="0.5" value="0">
                        <span class="hours-suffix">hrs</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    // ---------- Generate ----------
    document.getElementById('generateBtn').addEventListener('click', () => {
        const errorBox = document.getElementById('genErrorBox');
        errorBox.classList.add('hidden');

        if (!eligibleMembers.length) {
            errorBox.textContent = 'This engagement has no eligible team members.';
            errorBox.classList.remove('hidden');
            return;
        }
        if (!selectedAuditType) {
            errorBox.textContent = 'This engagement has no audit type with DOL support assigned.';
            errorBox.classList.remove('hidden');
            return;
        }

        const memberRows = document.querySelectorAll('#teamHoursList .member-row');
        const members = Array.from(memberRows).map(row => {
            const empId = row.dataset.empId;
            const member = eligibleMembers.find(m => String(m.emp_id) === String(empId));
            const hours = parseFloat(row.querySelector('.member-hours-input').value) || 0;
            return { ...member, hours };
        });

        const totalHours = members.reduce((sum, m) => sum + m.hours, 0);
        if (totalHours <= 0) {
            errorBox.textContent = 'Enter hours for at least one team member.';
            errorBox.classList.remove('hidden');
            return;
        }

        const criteria = parseCriteriaInput(document.getElementById('criteriaInput').value);
        if (!criteria.length) {
            errorBox.textContent = 'Enter at least one criterion to split.';
            errorBox.classList.remove('hidden');
            return;
        }

        lastResult = computeSplit(members, criteria);
        renderResult(totalHours, criteria.length);
    });

    function renderResult(totalHours, criteriaCount) {
        document.getElementById('setupSections').classList.add('hidden');
        document.getElementById('resultSection').classList.remove('hidden');

        const engName = escapeHtml(engagementData.engagement.eng_name);
        const hoursBreakdown = lastResult.map(m => m.hours).join(' / ');
        document.getElementById('resultSummary').innerHTML =
            `${engName} &middot; ${escapeHtml(selectedAuditType)} &middot; ${criteriaCount} criteria across ${lastResult.length} people, split by hours (${hoursBreakdown} = ${totalHours} total)`;

        document.getElementById('resultMembers').innerHTML = lastResult.map(m => {
            const roleKey = (m.role || '').toLowerCase();
            const pct = totalHours > 0 ? Math.round((m.hours / totalHours) * 100) : 0;
            const chips = m.assigned.length
                ? m.assigned.map(c => `<span class="result-chip">${escapeHtml(c)}</span>`).join('')
                : '<span class="field-hint">No criteria assigned</span>';
            return `
                <div class="result-member">
                    <div class="result-member-head">
                        <div class="member-avatar" style="background:${ROLE_COLOR_VAR[roleKey] || 'var(--ink)'}">${initials(m.emp_name)}</div>
                        <div class="member-info">
                            <div class="member-name">${escapeHtml(m.emp_name)}</div>
                            <div class="member-role">${ROLE_LABELS[roleKey] || m.role}</div>
                        </div>
                        <span class="result-share">${m.hours} hrs &middot; ${pct}% &middot; ${m.assigned.length} criteria</span>
                    </div>
                    <div class="result-chips">${chips}</div>
                </div>
            `;
        }).join('');

        document.getElementById('saveWarningText').innerHTML =
            `Saving will replace this engagement's <strong>${escapeHtml(selectedAuditType)}</strong> DOL for these ${lastResult.length} people. Their DOL for any other audit type on this engagement is untouched.`;
    }

    function resetResult() {
        lastResult = null;
        document.getElementById('resultSection').classList.add('hidden');
        document.getElementById('genErrorBox').classList.add('hidden');
    }

    document.getElementById('backToEditBtn').addEventListener('click', () => {
        document.getElementById('resultSection').classList.add('hidden');
        document.getElementById('setupSections').classList.remove('hidden');
    });

    // ---------- Save ----------
    document.getElementById('saveBtn').addEventListener('click', async () => {
        if (!lastResult || !selectedAuditType) return;
        const saveBtn = document.getElementById('saveBtn');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';

        const dolField = DOL_AUDIT_TYPES[selectedAuditType];
        const engagementIdno = engagementData.engagement.eng_idno;
        let failures = [];

        for (const m of lastResult) {
            const current = eligibleMembers.find(em => String(em.emp_id) === String(m.emp_id)) || {};
            const payload = {
                engagement_idno: engagementIdno,
                emp_id: m.emp_id,
                emp_name: m.emp_name,
                role: m.role,
                emp_soc1_dol: current.emp_soc1_dol || '',
                emp_soc2_dol: current.emp_soc2_dol || '',
                emp_hipaa_dol: current.emp_hipaa_dol || '',
                emp_hitrust_dol: current.emp_hitrust_dol || '',
                emp_fisma_dol: current.emp_fisma_dol || ''
            };
            payload[dolField] = m.assigned.join(', ');

            try {
                const res = await fetch('../../api/update-team-member.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (!data.success) failures.push(m.emp_name);
            } catch (err) {
                failures.push(m.emp_name);
            }
        }

        saveBtn.disabled = false;
        saveBtn.innerHTML = '<i class="bi bi-check-lg"></i> Save to Engagement';

        if (failures.length) {
            Swal.fire('Partial Failure', `Failed to save for: ${failures.join(', ')}. The rest saved successfully.`, 'warning');
            return;
        }

        sessionStorage.setItem('reopenDrawerFor', engagementIdno);
        sessionStorage.setItem('showEngagementUpdatedToast', 'true');
        window.location.href = '../dashboard.php';
    });
</script>
</body>
</html>
