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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/styles/main.css?v=<?php echo time(); ?>">
    <style>
        :root {
            --ink: #1B3A5C; --paper: #F4F6F8; --card: #FFFFFF;
            --line: #DCE1E7; --line-strong: #C2CAD3;
            --text: #16202B; --text-muted: #5B6B7C;
        }
        body.dark-mode {
            --ink: #6E9FCB; --paper: #10161D; --card: #171F28;
            --line: #2A343E; --line-strong: #3C4854;
            --text: #E7ECF1; --text-muted: #93A1AF;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }

        .top-header { background: var(--card); border-bottom: 1px solid var(--line); padding: 0 1.75rem; position: sticky; top: 0; z-index: 100; }
        .header-inner { max-width: 1080px; margin: 0 auto; height: 62px; display: flex; align-items: center; justify-content: space-between; gap: 2.5rem; }
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
        .profile-dropdown-item.logout { color: var(--critical, #B3261E); }

        .main-container { max-width: 1080px; margin: 0 auto; padding: 2.25rem 1.75rem 4rem; }
        .page-head h1 { font-size: 24px; margin: 0; font-weight: 700; letter-spacing: -0.015em; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin: 4px 0 0; }
        hr.rule { border: none; border-top: 1px solid var(--line); margin: 1.1rem 0 1.75rem; }

        .tool-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem; }
        .tool-card { background: var(--card); border: 1px solid var(--line); border-radius: 13px; padding: 1.4rem; text-decoration: none; color: inherit; display: block; transition: all 0.15s; }
        .tool-card:hover { border-color: var(--ink); box-shadow: 0 6px 18px rgba(0,0,0,0.08); }
        .tool-card .tool-icon { width: 42px; height: 42px; border-radius: 10px; background: color-mix(in srgb, var(--ink) 12%, transparent); color: var(--ink); display: flex; align-items: center; justify-content: center; font-size: 19px; margin-bottom: 0.9rem; }
        .tool-card h3 { font-size: 14.5px; font-weight: 700; margin: 0 0 0.35rem; }
        .tool-card p { font-size: 12px; color: var(--text-muted); margin: 0; line-height: 1.5; }
    </style>
</head>
<body>

<div class="top-header">
    <div class="header-inner">
        <a href="dashboard.php" class="brand">
            <span class="brand-icon">ET</span>
            <span class="brand-mark">Engagement Tracker</span>
        </a>
        <nav class="main-nav">
            <a href="dashboard.php">Engagements</a>
            <a class="active" href="tools.php">Tools</a>
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
    <div class="page-head">
        <h1>Tools</h1>
        <p class="page-sub">Utilities for planning and managing engagements</p>
    </div>
    <hr class="rule">

    <div class="tool-grid">
        <a class="tool-card" href="tool/dol-generator.php">
            <div class="tool-icon"><i class="bi bi-diagram-3"></i></div>
            <h3>DOL Generator</h3>
            <p>Split audit criteria across your Senior/Staff/Intern team by hours, and save it straight to the engagement.</p>
        </a>
    </div>
</div>

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
