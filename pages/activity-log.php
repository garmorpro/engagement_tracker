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

$entries = [];
$tableCheck = $conn->query("SHOW TABLES LIKE 'activity_log'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $result = $conn->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 200");
    $entries = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$eventMeta = [
    'engagement_status_change' => ['label' => 'Status Change', 'icon' => 'bi-arrow-left-right', 'color' => '--ink'],
    'dol_updated'              => ['label' => 'DOL Updated',   'icon' => 'bi-people',            'color' => '--ink'],
    'account_created'          => ['label' => 'Account Created', 'icon' => 'bi-person-plus',      'color' => '--good'],
    'account_updated'          => ['label' => 'Account Updated', 'icon' => 'bi-pencil-square',    'color' => '--caution'],
    'account_deleted'          => ['label' => 'Account Deleted', 'icon' => 'bi-person-dash',      'color' => '--critical'],
];

function timeAgoLabel(string $datetime): string {
    $now = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);
    if ($diff->days > 0) return $diff->days . ' day' . ($diff->days > 1 ? 's' : '') . ' ago';
    if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
    if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
    return 'Just now';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --ink: #1B3A5C; --ink-soft: #4A6483; --paper: #F4F6F8; --card: #FFFFFF;
            --line: #DCE1E7; --line-strong: #C2CAD3; --text: #16202B; --text-muted: #5B6B7C;
            --critical: #B3261E; --caution: #A66A00; --good: #1F7A54;
        }
        body.dark-mode {
            --ink: #6E9FCB; --ink-soft: #7C93AA; --paper: #10161D; --card: #171F28;
            --line: #2A343E; --line-strong: #3C4854; --text: #E7ECF1; --text-muted: #93A1AF;
            --critical: #E5766F; --caution: #D3A44E; --good: #5FB98A;
        }
        * { box-sizing: border-box; }
        body { background: var(--paper); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; margin: 0; transition: background-color 0.2s ease, color 0.2s ease; }

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

        .main-container { max-width: 860px; margin: 0 auto; padding: 2.25rem 1.75rem 4rem; }
        .page-head h1 { font-size: 24px; margin: 0; font-weight: 700; letter-spacing: -0.015em; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin: 4px 0 0; }
        hr.rule { border: none; border-top: 1px solid var(--line); margin: 1.1rem 0 1.75rem; }

        .btn-secondary { background: var(--paper); border: 1px solid var(--line); color: var(--text-muted); font-weight: 700; border-radius: 8px; padding: 0.6rem 1.1rem; transition: all 0.15s; display: inline-flex; align-items: center; gap: 6px; font-size: 13px; }
        .btn-secondary:hover { background: var(--line); color: var(--text); }

        .log-list { display: flex; flex-direction: column; }
        .log-item { display: flex; gap: 0.85rem; padding: 0.9rem 0; border-bottom: 1px solid var(--line); }
        .log-item:last-child { border-bottom: none; }
        .log-icon { width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 14px; }
        .log-body { min-width: 0; flex: 1; }
        .log-top { display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap; }
        .log-event { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; }
        .log-time { font-size: 11.5px; color: var(--text-muted); margin-left: auto; white-space: nowrap; }
        .log-desc { font-size: 13px; color: var(--text); margin-top: 3px; }
        .log-actor { font-size: 11.5px; color: var(--text-muted); margin-top: 2px; }
        .log-empty { padding: 3rem 1rem; text-align: center; color: var(--text-muted); font-size: 13px; }

        @media (max-width: 480px) {
            .top-header { padding: 0 1rem; }
            .header-inner { gap: 0.6rem; }
            .brand-mark { display: none; }
            .main-container { padding: 1.5rem 1rem 3rem; }
            .log-time { margin-left: 0; }
            .log-top { flex-direction: column; align-items: flex-start; gap: 2px; }
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
                        <a href="settings.php" class="profile-dropdown-item">
                            <i class="bi bi-gear"></i> Settings
                        </a>
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
            <h1>Activity Log</h1>
            <p class="page-sub">Engagement status changes, DOL edits, and account changes — most recent 200.</p>
        </div>
        <a href="settings.php" class="btn-secondary" style="text-decoration: none;">
            <i class="bi bi-arrow-left"></i> Settings
        </a>
    </div>
    <hr class="rule">

    <div class="log-list">
        <?php if (empty($entries)): ?>
            <div class="log-empty">
                <?php if (!$tableCheck || $tableCheck->num_rows === 0): ?>
                    Activity logging isn't set up yet — run <code>php includes/migrate_create_activity_log_table.php</code> on the server.
                <?php else: ?>
                    Nothing logged yet. Actions will start appearing here as they happen.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($entries as $entry): ?>
                <?php
                    $meta = $eventMeta[$entry['event_type']] ?? ['label' => $entry['event_type'], 'icon' => 'bi-dot', 'color' => '--text-muted'];
                ?>
                <div class="log-item">
                    <div class="log-icon" style="background: color-mix(in srgb, var(<?= $meta['color'] ?>) 14%, transparent); color: var(<?= $meta['color'] ?>);">
                        <i class="bi <?= htmlspecialchars($meta['icon']) ?>"></i>
                    </div>
                    <div class="log-body">
                        <div class="log-top">
                            <span class="log-event" style="color: var(<?= $meta['color'] ?>);"><?= htmlspecialchars($meta['label']) ?></span>
                            <span class="log-time"><?= htmlspecialchars(timeAgoLabel($entry['created_at'])) ?></span>
                        </div>
                        <div class="log-desc"><?= htmlspecialchars($entry['description']) ?></div>
                        <div class="log-actor">by <?= htmlspecialchars($entry['actor_name']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
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
</script>

</body>
</html>
