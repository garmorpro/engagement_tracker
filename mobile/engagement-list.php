<?php
require_once '../path.php';
require_once '../includes/functions.php';

// Fetch all engagements
$query = "SELECT * FROM engagements WHERE eng_status != 'archived' ORDER BY eng_created DESC";
$result = $conn->query($query);
$engagements = $result->fetch_all(MYSQLI_ASSOC);

// Count by status
$totalCount = count($engagements);
$inProgressCount = count(array_filter($engagements, fn($e) => $e['eng_status'] === 'in-progress'));
$planningCount = count(array_filter($engagements, fn($e) => $e['eng_status'] === 'planning'));
$reviewCount = count(array_filter($engagements, fn($e) => $e['eng_status'] === 'in-review'));
$completeCount = count(array_filter($engagements, fn($e) => $e['eng_status'] === 'complete'));

// Dark mode
$isDarkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engagement Tracker - Mobile</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-blue: #4487FC;
            --success-green: #4FC65F;
            --warning-orange: #F17313;
            --info-purple: #A04DFD;
            --teal: #4DBFB8;
            --danger-red: #C90012;

            --bg-primary: #0F1419;
            --bg-secondary: #1A2332;
            --bg-tertiary: #2A3447;
            --border-color: #3A4557;
            --text-primary: #E8EAED;
            --text-secondary: #9CA3AF;
        }

        body.light-mode {
            --bg-primary: #FFFFFF;
            --bg-secondary: #F5F7FA;
            --bg-tertiary: #E8ECEF;
            --border-color: #D1D5DB;
            --text-primary: #1A1A1A;
            --text-secondary: #6B7280;
        }

        body {
            background: var(--bg-primary);
            color: var(--text-primary);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            font-size: 16px;
        }

        /* ========== HEADER ========== */
        .mobile-header {
            background: var(--bg-secondary);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .mobile-header h1 {
            font-size: 18px;
            font-weight: 700;
        }

        .mobile-header-actions {
            display: flex;
            gap: 0.75rem;
        }

        .mobile-header-btn {
            width: 36px;
            height: 36px;
            background: var(--bg-tertiary);
            border: none;
            border-radius: 8px;
            color: var(--text-primary);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.2s;
        }

        .mobile-header-btn:active {
            background: var(--primary-blue);
            color: white;
        }

        /* ========== STATUS FILTER TABS ========== */
        .status-tabs {
            display: flex;
            gap: 0.5rem;
            padding: 0.75rem;
            background: var(--bg-secondary);
            overflow-x: auto;
            border-bottom: 1px solid var(--border-color);
        }

        .status-tab {
            padding: 0.5rem 0.75rem;
            background: var(--bg-tertiary);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            color: var(--text-secondary);
            cursor: pointer;
            white-space: nowrap;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .status-tab.active {
            background: var(--primary-blue);
            border-color: var(--primary-blue);
            color: white;
        }

        /* ========== ENGAGEMENT LIST ========== */
        .engagement-list {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .engagement-item {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 1rem;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .engagement-item:active {
            background: var(--bg-tertiary);
            border-color: var(--primary-blue);
        }

        .engagement-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .engagement-item-title {
            font-weight: 600;
            font-size: 15px;
            flex: 1;
        }

        .engagement-item-id {
            font-size: 12px;
            color: var(--text-secondary);
            margin-top: 0.25rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.planning {
            background: rgba(241, 115, 19, 0.2);
            color: var(--warning-orange);
        }

        .status-badge.in-progress {
            background: rgba(79, 198, 95, 0.2);
            color: var(--success-green);
        }

        .status-badge.in-review {
            background: rgba(160, 77, 253, 0.2);
            color: var(--info-purple);
        }

        .status-badge.complete {
            background: rgba(77, 191, 184, 0.2);
            color: var(--teal);
        }

        .engagement-item-meta {
            display: flex;
            gap: 0.75rem;
            font-size: 13px;
            color: var(--text-secondary);
        }

        .engagement-item-meta-item {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .engagement-item-meta-item i {
            font-size: 14px;
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
            text-align: center;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 48px;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* ========== DARK MODE TOGGLE ========== */
        .dark-mode-toggle {
            position: fixed;
            bottom: 1.5rem;
            right: 1rem;
            width: 50px;
            height: 50px;
            background: var(--primary-blue);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 22px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s;
            z-index: 50;
        }

        .dark-mode-toggle:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body <?php echo $isDarkMode ? '' : 'class="light-mode"'; ?>>

    <!-- HEADER -->
    <div class="mobile-header">
        <h1>Engagements</h1>
        <div class="mobile-header-actions">
            <button class="mobile-header-btn" onclick="window.location.href='../pages/dashboard.php'" title="Back to Dashboard">
                <i class="bi bi-house"></i>
            </button>
        </div>
    </div>

    <!-- STATUS TABS -->
    <div class="status-tabs">
        <div class="status-tab active" data-filter="all" onclick="filterEngagements(this)">
            All (<?php echo $totalCount; ?>)
        </div>
        <div class="status-tab" data-filter="planning" onclick="filterEngagements(this)">
            Planning (<?php echo $planningCount; ?>)
        </div>
        <div class="status-tab" data-filter="in-progress" onclick="filterEngagements(this)">
            In Progress (<?php echo $inProgressCount; ?>)
        </div>
        <div class="status-tab" data-filter="in-review" onclick="filterEngagements(this)">
            Review (<?php echo $reviewCount; ?>)
        </div>
        <div class="status-tab" data-filter="complete" onclick="filterEngagements(this)">
            Complete (<?php echo $completeCount; ?>)
        </div>
    </div>

    <!-- ENGAGEMENT LIST -->
    <div class="engagement-list">
        <?php if (!empty($engagements)): ?>
            <?php foreach ($engagements as $eng): ?>
                <a href="mobile-engagement-details.php?id=<?php echo htmlspecialchars($eng['eng_idno']); ?>" class="engagement-item" data-status="<?php echo htmlspecialchars($eng['eng_status']); ?>">
                    <div class="engagement-item-header">
                        <div>
                            <div class="engagement-item-title"><?php echo htmlspecialchars($eng['eng_name']); ?></div>
                            <div class="engagement-item-id"><?php echo htmlspecialchars($eng['eng_idno']); ?></div>
                        </div>
                        <div class="status-badge <?php echo htmlspecialchars($eng['eng_status']); ?>">
                            <?php echo ucfirst(str_replace('-', ' ', $eng['eng_status'])); ?>
                        </div>
                    </div>
                    <div class="engagement-item-meta">
                        <?php if (!empty($eng['eng_location'])): ?>
                            <div class="engagement-item-meta-item">
                                <i class="bi bi-geo-alt"></i>
                                <span><?php echo htmlspecialchars($eng['eng_location']); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($eng['eng_manager'])): ?>
                            <div class="engagement-item-meta-item">
                                <i class="bi bi-person"></i>
                                <span><?php echo htmlspecialchars($eng['eng_manager']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <p>No engagements found</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- DARK MODE TOGGLE -->
    <button class="dark-mode-toggle" onclick="toggleDarkMode()" title="Toggle Dark Mode">
        <i class="bi" id="darkModeIcon"></i>
    </button>

    <script>
        // Dark mode functionality
        const isDarkMode = localStorage.getItem('darkMode') === 'true';
        updateDarkMode(isDarkMode);

        function updateDarkMode(isDark) {
            if (isDark) {
                document.body.classList.remove('light-mode');
                document.getElementById('darkModeIcon').className = 'bi bi-sun-fill';
            } else {
                document.body.classList.add('light-mode');
                document.getElementById('darkModeIcon').className = 'bi bi-moon-fill';
            }
        }

        function toggleDarkMode() {
            const isDark = localStorage.getItem('darkMode') === 'true';
            localStorage.setItem('darkMode', !isDark ? 'true' : 'false');
            updateDarkMode(!isDark);
            document.cookie = `darkMode=${!isDark ? 'true' : 'false'}; path=/`;
        }

        // Filter functionality
        function filterEngagements(tab) {
            // Remove active from all tabs
            document.querySelectorAll('.status-tab').forEach(t => t.classList.remove('active'));
            
            // Add active to clicked tab
            tab.classList.add('active');
            
            // Filter items
            const filter = tab.getAttribute('data-filter');
            const items = document.querySelectorAll('.engagement-item');
            
            items.forEach(item => {
                if (filter === 'all') {
                    item.style.display = 'block';
                } else {
                    item.style.display = item.getAttribute('data-status') === filter ? 'block' : 'none';
                }
            });
        }
    </script>

</body>
</html>