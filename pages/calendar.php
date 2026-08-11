<?php
require_once '../auth/session_check.php';
require_once '../path.php';
require_once '../includes/functions.php';

$initials = '';
if (!empty($_SESSION['name'])) {
    foreach (explode(' ', trim($_SESSION['name'])) as $part) {
        if (!empty($part)) $initials .= strtoupper($part[0]);
    }
}
$isAdmin = in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --ink: #1B3A5C; --ink-soft: #4A6483; --paper: #F4F6F8; --card: #FFFFFF;
            --line: #DCE1E7; --line-strong: #C2CAD3; --text: #16202B; --text-muted: #5B6B7C;
            --critical: #B3261E; --critical-tint: rgba(179, 38, 30, 0.07); --caution: #A66A00; --good: #1F7A54;
        }
        body.dark-mode {
            --ink: #6E9FCB; --ink-soft: #7C93AA; --paper: #10161D; --card: #171F28;
            --line: #2A343E; --line-strong: #3C4854; --text: #E7ECF1; --text-muted: #93A1AF;
            --critical: #E5766F; --critical-tint: rgba(229, 118, 111, 0.1); --caution: #D3A44E; --good: #5FB98A;
        }
        * { box-sizing: border-box; }
        body { background: var(--paper); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; font-variant-numeric: tabular-nums; transition: background-color 0.2s ease, color 0.2s ease; }

        .top-header { background: var(--card); border-bottom: 1px solid var(--line); padding: 0 1.75rem; position: sticky; top: 0; z-index: 100; }
        .header-inner { max-width: 1200px; margin: 0 auto; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 2.5rem; }
        .brand { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; text-decoration: none; }
        .brand-icon { width: 26px; height: 26px; border-radius: 7px; background: var(--ink); color: var(--card); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .brand-mark { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; color: var(--text); }
        .main-nav { display: flex; gap: 1.6rem; }
        .main-nav a { font-size: 13px; font-weight: 600; color: var(--text-muted); text-decoration: none; padding: 4px 0; border-bottom: 2px solid transparent; }
        .main-nav a.active { color: var(--text); border-bottom-color: var(--ink); }
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

        .main-container { max-width: 1200px; margin: 0 auto; padding: 2.25rem 1.75rem 4rem; }
        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1.5rem; margin-bottom: 0.35rem; flex-wrap: wrap; }
        .page-head h1 { font-size: 24px; margin: 0; font-weight: 700; letter-spacing: -0.015em; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin: 4px 0 0; }
        hr.rule { border: none; border-top: 1px solid var(--line); margin: 1.1rem 0 1.5rem; }

        .cal-toolbar { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.25rem; flex-wrap: wrap; }
        .cal-nav-btn { width: 30px; height: 30px; border-radius: 6px; border: 1px solid var(--line); background: var(--card); color: var(--text); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 13px; }
        .cal-nav-btn:hover { border-color: var(--ink); color: var(--ink); }
        .cal-month-label { font-size: 16px; font-weight: 700; min-width: 150px; }
        .cal-today-btn { border: 1px solid var(--line); background: var(--card); color: var(--text-muted); border-radius: 6px; padding: 5px 12px; font-size: 12px; font-weight: 600; cursor: pointer; }
        .cal-today-btn:hover { border-color: var(--ink); color: var(--ink); }
        .cal-legend { display: flex; gap: 1.1rem; margin-left: auto; flex-wrap: wrap; }
        .cal-legend-item { display: flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--text-muted); }
        .cal-legend-item .dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

        .cal-grid { border: 1px solid var(--line); border-radius: 12px; overflow: hidden; background: var(--card); }
        .cal-weekdays { display: grid; grid-template-columns: repeat(7, 1fr); border-bottom: 1px solid var(--line); }
        .cal-weekday { padding: 8px 10px; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); text-align: center; }
        .cal-weeks { display: flex; flex-direction: column; }
        .cal-week { display: grid; grid-template-columns: repeat(7, 1fr); }
        .cal-day { min-height: 108px; padding: 6px; border-right: 1px solid var(--line); border-bottom: 1px solid var(--line); display: flex; flex-direction: column; gap: 3px; }
        .cal-day:nth-child(7n) { border-right: none; }
        .cal-week:last-child .cal-day { border-bottom: none; }
        .cal-day.outside { background: color-mix(in srgb, var(--paper) 55%, var(--card)); }
        .cal-day.outside .cal-day-num { color: var(--text-muted); opacity: 0.5; }
        .cal-day.today { background: color-mix(in srgb, var(--ink) 6%, var(--card)); }
        .cal-day-num { font-size: 12px; font-weight: 700; color: var(--text); }
        .cal-day.today .cal-day-num { color: var(--ink); }
        .cal-chip { display: flex; align-items: center; gap: 4px; font-size: 10.5px; padding: 2px 5px; border-radius: 4px; cursor: pointer; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; background: color-mix(in srgb, var(--ink) 8%, transparent); }
        .cal-chip:hover { background: color-mix(in srgb, var(--ink) 16%, transparent); }
        .cal-chip .dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
        .cal-chip.overdue { background: var(--critical-tint); }
        .cal-chip.completed { opacity: 0.55; }
        .cal-chip-label { overflow: hidden; text-overflow: ellipsis; }
        .cal-more { font-size: 10.5px; color: var(--text-muted); font-weight: 600; cursor: pointer; padding: 2px 5px; }
        .cal-more:hover { color: var(--ink); }

        .day-popover-scrim { position: fixed; inset: 0; background: rgba(10,14,20,0.5); z-index: 300; display: flex; align-items: center; justify-content: center; padding: 1.5rem; opacity: 0; pointer-events: none; transition: opacity 0.15s ease; }
        .day-popover-scrim.open { opacity: 1; pointer-events: auto; }
        .day-popover { background: var(--card); border: 1px solid var(--line); border-radius: 14px; width: 100%; max-width: 420px; max-height: 80vh; display: flex; flex-direction: column; box-shadow: 0 24px 64px rgba(0,0,0,0.28); }
        .day-popover-header { display: flex; align-items: center; justify-content: space-between; padding: 1.1rem 1.3rem; border-bottom: 1px solid var(--line); }
        .day-popover-header h3 { font-size: 14.5px; font-weight: 700; margin: 0; }
        .day-popover-close { border: none; background: transparent; color: var(--text-muted); cursor: pointer; font-size: 18px; padding: 2px; line-height: 1; }
        .day-popover-body { overflow-y: auto; padding: 0.4rem 0; }
        .day-popover-item { display: flex; align-items: center; gap: 10px; padding: 0.6rem 1.3rem; cursor: pointer; border-bottom: 1px solid var(--line); }
        .day-popover-item:last-child { border-bottom: none; }
        .day-popover-item:hover { background: var(--paper); }
        .day-popover-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
        .day-popover-info { min-width: 0; flex: 1; }
        .day-popover-name { font-size: 13px; font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .day-popover-title { font-size: 11.5px; color: var(--text-muted); }

        @media (max-width: 720px) {
            .main-nav { display: none; }
            .cal-day { min-height: 74px; }
            .cal-chip-label { display: none; }
            .cal-legend { display: none; }
        }
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
            <a class="active" href="calendar.php">Calendar</a>
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
                        <?php if ($isAdmin): ?>
                        <a href="settings.php" class="profile-dropdown-item">
                            <i class="bi bi-gear"></i> Settings
                        </a>
                        <?php endif; ?>
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
    <div class="page-head">
        <div>
            <h1>Calendar</h1>
            <p class="page-sub">Every key date and milestone across your active engagements, one month at a time.</p>
        </div>
    </div>
    <hr class="rule">

    <div class="cal-toolbar">
        <button class="cal-nav-btn" id="calPrevBtn"><i class="bi bi-chevron-left"></i></button>
        <button class="cal-nav-btn" id="calNextBtn"><i class="bi bi-chevron-right"></i></button>
        <div class="cal-month-label" id="calMonthLabel"></div>
        <button class="cal-today-btn" id="calTodayBtn">Today</button>
        <div class="cal-legend">
            <div class="cal-legend-item"><span class="dot" style="background:var(--critical)"></span> Overdue</div>
            <div class="cal-legend-item"><span class="dot" style="background:var(--caution)"></span> This week</div>
            <div class="cal-legend-item"><span class="dot" style="background:var(--ink)"></span> Upcoming</div>
            <div class="cal-legend-item"><span class="dot" style="background:var(--good)"></span> Completed</div>
        </div>
    </div>

    <div class="cal-grid">
        <div class="cal-weekdays">
            <div class="cal-weekday">Sun</div><div class="cal-weekday">Mon</div><div class="cal-weekday">Tue</div>
            <div class="cal-weekday">Wed</div><div class="cal-weekday">Thu</div><div class="cal-weekday">Fri</div>
            <div class="cal-weekday">Sat</div>
        </div>
        <div class="cal-weeks" id="calWeeks">
            <div style="padding: 3rem; text-align: center; color: var(--text-muted); font-size: 13px;">Loading&hellip;</div>
        </div>
    </div>
</div>

<div class="day-popover-scrim" id="dayPopoverScrim">
    <div class="day-popover">
        <div class="day-popover-header">
            <h3 id="dayPopoverTitle"></h3>
            <button class="day-popover-close" onclick="closeDayPopover()">&times;</button>
        </div>
        <div class="day-popover-body" id="dayPopoverBody"></div>
    </div>
</div>

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

// ---------- calendar ----------
const today = new Date();
today.setHours(0, 0, 0, 0);
const todayIso = today.toISOString().slice(0, 10);
let viewYear = today.getFullYear();
let viewMonth = today.getMonth() + 1; // 1-12

const MONTH_NAMES = ['January','February','March','April','May','June','July','August','September','October','November','December'];

function escapeHtml(str) {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
function escAttr(str) {
    return String(str).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function itemStatusClass(item) {
    if (item.completed) return 'completed';
    if (item.date < todayIso) return 'overdue';
    const daysUntil = Math.round((new Date(item.date + 'T00:00:00') - today) / 86400000);
    if (daysUntil <= 7) return 'soon';
    return 'upcoming';
}
function statusColorVar(status) {
    return { overdue: 'var(--critical)', soon: 'var(--caution)', upcoming: 'var(--ink)', completed: 'var(--good)' }[status];
}

function goToEngagement(id) {
    sessionStorage.setItem('reopenDrawerFor', id);
    window.location.href = 'dashboard.php';
}

function openDayPopover(dateIso, items) {
    const d = new Date(dateIso + 'T00:00:00');
    document.getElementById('dayPopoverTitle').textContent = d.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric' });
    const body = document.getElementById('dayPopoverBody');
    body.innerHTML = items.map(item => {
        const status = itemStatusClass(item);
        return `
            <div class="day-popover-item" onclick="goToEngagement('${escAttr(item.engagement_idno)}')">
                <span class="day-popover-dot" style="background:${statusColorVar(status)}"></span>
                <div class="day-popover-info">
                    <div class="day-popover-name">${escapeHtml(item.eng_name)}</div>
                    <div class="day-popover-title">${escapeHtml(item.title)}</div>
                </div>
            </div>
        `;
    }).join('');
    document.getElementById('dayPopoverScrim').classList.add('open');
}
function closeDayPopover() {
    document.getElementById('dayPopoverScrim').classList.remove('open');
}
document.getElementById('dayPopoverScrim').addEventListener('click', (ev) => {
    if (ev.target.id === 'dayPopoverScrim') closeDayPopover();
});

async function loadCalendar() {
    document.getElementById('calMonthLabel').textContent = `${MONTH_NAMES[viewMonth - 1]} ${viewYear}`;
    const weeksEl = document.getElementById('calWeeks');
    weeksEl.innerHTML = '<div style="padding: 3rem; text-align: center; color: var(--text-muted); font-size: 13px;">Loading&hellip;</div>';

    let items = [];
    try {
        const res = await fetch(`../api/get-calendar-items.php?year=${viewYear}&month=${viewMonth}`);
        const data = await res.json();
        if (data.success) items = data.items;
    } catch (error) {
        console.error('Error:', error);
    }

    const byDate = {};
    items.forEach(item => {
        (byDate[item.date] = byDate[item.date] || []).push(item);
    });

    const firstOfMonth = new Date(viewYear, viewMonth - 1, 1);
    const startDow = firstOfMonth.getDay(); // 0 = Sun
    const daysInMonth = new Date(viewYear, viewMonth, 0).getDate();
    const totalCells = Math.ceil((startDow + daysInMonth) / 7) * 7;

    let html = '';
    for (let w = 0; w < totalCells / 7; w++) {
        html += '<div class="cal-week">';
        for (let d = 0; d < 7; d++) {
            const cellDate = new Date(viewYear, viewMonth - 1, 1);
            cellDate.setDate(cellDate.getDate() - startDow + (w * 7 + d));
            const iso = cellDate.toISOString().slice(0, 10);
            const dayItems = (byDate[iso] || []).slice().sort((a, b) => a.completed - b.completed);
            const isToday = iso === todayIso;
            const isOutside = cellDate.getMonth() !== (viewMonth - 1);

            let chipsHtml = '';
            dayItems.slice(0, 3).forEach(item => {
                const status = itemStatusClass(item);
                chipsHtml += `
                    <div class="cal-chip ${status}" data-date="${iso}" title="${escAttr(item.eng_name + ' — ' + item.title)}">
                        <span class="dot" style="background:${statusColorVar(status)}"></span>
                        <span class="cal-chip-label">${escapeHtml(item.eng_name)}</span>
                    </div>
                `;
            });
            if (dayItems.length > 3) {
                chipsHtml += `<div class="cal-more" data-date="${iso}">+${dayItems.length - 3} more</div>`;
            }

            html += `
                <div class="cal-day ${isOutside ? 'outside' : ''} ${isToday ? 'today' : ''}">
                    <div class="cal-day-num">${cellDate.getDate()}</div>
                    ${chipsHtml}
                </div>
            `;
        }
        html += '</div>';
    }
    weeksEl.innerHTML = html;

    // Event delegation off data-date rather than inline onclick — avoids
    // serializing each day's items into an HTML attribute (fragile with
    // quotes/special characters, and wasteful re-serializing the same
    // array once per chip). byDate is already in scope here.
    weeksEl.querySelectorAll('[data-date]').forEach(el => {
        el.addEventListener('click', () => openDayPopover(el.dataset.date, byDate[el.dataset.date] || []));
    });
}

document.getElementById('calPrevBtn').addEventListener('click', () => {
    viewMonth--;
    if (viewMonth < 1) { viewMonth = 12; viewYear--; }
    loadCalendar();
});
document.getElementById('calNextBtn').addEventListener('click', () => {
    viewMonth++;
    if (viewMonth > 12) { viewMonth = 1; viewYear++; }
    loadCalendar();
});
document.getElementById('calTodayBtn').addEventListener('click', () => {
    viewYear = today.getFullYear();
    viewMonth = today.getMonth() + 1;
    loadCalendar();
});

loadCalendar();
</script>

</body>
</html>
