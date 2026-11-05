<?php
require_once '../auth/session_check.php';
require_once '../path.php';
require_once '../includes/functions.php';

// Notes sent in by another app (api/receive-note.php), newest first. A missing table just means
// the migration hasn't been run yet. Engagement names are looked up separately rather than JOINed:
// inbox_notes and engagements can end up with different text collations, which MariaDB refuses to compare.
$tableMissing = false;
$notes = [];
try {
    $res = $conn->query("SELECT * FROM inbox_notes ORDER BY created_at DESC, note_id DESC LIMIT 500");
    $notes = $res->fetch_all(MYSQLI_ASSOC);
} catch (Throwable $e) {
    error_log('notes.php: ' . $e->getMessage());
    $tableMissing = true;
}

$engagementOptions = [];
$engagementNames = [];
try {
    $res = $conn->query("SELECT eng_idno, eng_name, eng_audit_type, eng_status FROM engagements ORDER BY eng_name");
    foreach ($res->fetch_all(MYSQLI_ASSOC) as $e) {
        $engagementNames[$e['eng_idno']] = $e['eng_name'];
        if ($e['eng_status'] !== 'archived') {
            $engagementOptions[] = ['idno' => $e['eng_idno'], 'label' => $e['eng_idno'] . ' · ' . $e['eng_name'] . ($e['eng_audit_type'] ? ' (' . $e['eng_audit_type'] . ')' : '')];
        }
    }
} catch (Throwable $e) {
}
foreach ($notes as &$n) {
    $n['eng_name'] = $engagementNames[$n['engagement_idno'] ?? ''] ?? null;
}
unset($n);

$total = count($notes);
$unread = count(array_filter($notes, fn($n) => !(int) $n['is_read']));
$unattached = count(array_filter($notes, fn($n) => empty($n['engagement_idno'])));
$keyReady = strlen((string) ($_ENV['NOTES_API_KEY'] ?? getenv('NOTES_API_KEY') ?: '')) >= 16;

$roleLabels = ['super_admin' => 'Super admin', 'admin' => 'Admin'];
$roleLabel = $roleLabels[$_SESSION['role'] ?? ''] ?? ucfirst(str_replace('_', ' ', $_SESSION['role'] ?? 'user'));
$initials = '';
if (!empty($_SESSION['name'])) {
    foreach (explode(' ', trim($_SESSION['name'])) as $part) {
        if (!empty($part)) $initials .= strtoupper($part[0]);
    }
}

function noteWhen(string $raw): string
{
    $t = strtotime($raw);
    return $t ? date('M j, Y', $t) . ' · ' . date('g:i A', $t) : $raw;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes - Engagement Tracker</title>
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
            --critical-tint-strong: rgba(179, 38, 30, 0.13);
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
            --critical-tint-strong: rgba(229, 118, 111, 0.18);
            --caution: #D3A44E;
            --good: #5FB98A;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--paper); color: var(--text); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; transition: background-color 0.2s ease, color 0.2s ease; }
        a { color: inherit; }
        .sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

        /* ---------- header (same bar as the dashboard) ---------- */
        .top-header { background: var(--card); border-bottom: 1px solid var(--line); position: sticky; top: 0; z-index: 100; width: 100%; display: grid; grid-template-columns: minmax(1.75rem, 1fr) minmax(0, 1200px) minmax(1.75rem, 1fr); }
        .header-inner { grid-column: 2; min-width: 0; height: 68px; display: flex; align-items: center; justify-content: flex-start; gap: 2rem; }
        .brand { display: flex; align-items: center; gap: 0.6rem; flex-shrink: 0; text-decoration: none; }
        .brand-icon { width: 26px; height: 26px; border-radius: 7px; background: var(--ink); color: var(--card); font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
        .brand-mark { font-size: 15px; font-weight: 700; letter-spacing: -0.01em; }
        .header-right { display: flex; align-items: center; gap: 0.9rem; flex-shrink: 0; }
        .hdr-due { display: flex; align-items: center; gap: 8px; height: 40px; padding: 0 14px; border-radius: 19px; border: 1px solid var(--line); background: var(--card); color: var(--text); font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; text-decoration: none; }
        .hdr-due:hover { border-color: var(--line-strong); background: var(--paper); }
        .hdr-due.active { border-color: var(--ink); color: var(--ink); background: var(--paper); }
        .hdr-due i { font-size: 15px; }
        .hdr-badge { min-width: 20px; height: 20px; border-radius: 10px; font-size: 11.5px; font-weight: 800; display: flex; align-items: center; justify-content: center; padding: 0 6px; margin-right: -4px; background: color-mix(in srgb, var(--caution) 16%, transparent); color: var(--caution); }
        .hdr-badge[hidden] { display: none; }
        .hdr-search { display: flex; align-items: center; gap: 10px; flex: 1 1 260px; min-width: 0; max-width: 420px; margin: 0 auto; height: 40px; padding: 0 12px; border: 1px solid var(--line); border-radius: 10px; background: var(--paper); color: var(--text-muted); }
        .hdr-search:focus-within { border-color: var(--ink); background: var(--card); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ink) 14%, transparent); }
        .hdr-search i { font-size: 14px; flex-shrink: 0; }
        .hdr-search input { flex: 1; min-width: 0; border: none; outline: none; background: transparent; font: inherit; font-size: 13px; color: var(--text); }
        .hdr-search input::placeholder { color: var(--text-muted); }
        .hdr-search kbd { font-family: inherit; font-size: 11px; font-weight: 600; color: var(--text-muted); border: 1px solid var(--line); border-radius: 5px; padding: 1px 6px; background: var(--card); }
        .hdr-search:focus-within kbd { display: none; }
        .btn-new-engagement { background: var(--ink); color: var(--card); border: none; height: 40px; padding: 0 18px; border-radius: 10px; font-size: 13px; font-weight: 600; font-family: inherit; display: flex; align-items: center; gap: 8px; flex-shrink: 0; cursor: pointer; text-decoration: none; }
        .btn-new-engagement:hover { opacity: 0.92; }
        .hdr-sep { width: 1px; height: 26px; background: var(--line); margin: 0 0.25rem; }
        .profile-section { position: relative; }
        .profile-wrapper { display: flex; align-items: center; gap: 8px; height: 40px; padding: 0 12px 0 5px; border-radius: 19px; border: 1px solid var(--line); background: var(--paper); color: var(--text-muted); cursor: pointer; font-family: inherit; }
        .profile-wrapper:hover { border-color: var(--line-strong); }
        .profile-wrapper i { font-size: 12px; }
        .profile-btn { width: 30px; height: 30px; border-radius: 50%; background: var(--ink); color: var(--card); font-weight: 700; font-size: 11.5px; display: flex; align-items: center; justify-content: center; }
        .profile-dropdown { display: none; position: absolute; right: 0; top: calc(100% + 8px); width: 248px; background: var(--card); border: 1px solid var(--line); border-radius: 12px; box-shadow: 0 14px 36px rgba(10, 20, 32, 0.18); z-index: 200; overflow: hidden; }
        .profile-dropdown.active { display: block; }
        .profile-dropdown-header { display: flex; gap: 10px; align-items: center; padding: 0.9rem 1rem; border-bottom: 1px solid var(--line); }
        .profile-dropdown-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--ink); color: var(--card); font-weight: 700; font-size: 12.5px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .profile-dropdown-name { font-size: 13px; font-weight: 700; }
        .profile-dropdown-email { font-size: 11.5px; color: var(--text-muted); }
        .profile-dropdown-menu { padding: 0.4rem; }
        .profile-dropdown-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 6px; font-size: 12.5px; color: var(--text); text-decoration: none; }
        .profile-dropdown-item:hover { background: var(--paper); }
        button.profile-dropdown-item { width: 100%; border: none; background: none; text-align: left; font-family: inherit; cursor: pointer; }
        .profile-dropdown-state { margin-left: auto; font-size: 11.5px; color: var(--text-muted); }
        .profile-dropdown-sep { height: 1px; background: var(--line); margin: 0.35rem 0.25rem; }
        .profile-dropdown-item.logout { color: var(--critical); }

        /* ---------- page ---------- */
        .page { max-width: 860px; margin: 0 auto; padding: 2rem 1.25rem 4rem; }
        .page-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 1rem; flex-wrap: wrap; }
        .page-head h1 { margin: 0; font-size: 28px; font-weight: 700; letter-spacing: -0.02em; }
        .page-sub { margin: 6px 0 0; font-size: 13px; color: var(--text-muted); }
        .btn { height: 36px; padding: 0 14px; border-radius: 9px; border: 1px solid var(--line); background: var(--card); color: var(--text); font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn:hover { border-color: var(--line-strong); background: var(--paper); }
        .btn:disabled { opacity: 0.5; cursor: default; }
        .btn-primary { background: var(--ink); border-color: var(--ink); color: var(--card); }
        .btn-primary:hover { background: var(--ink); opacity: 0.92; }
        .btn-sm { height: 30px; padding: 0 10px; font-size: 12px; border-radius: 8px; }
        .btn-danger { border-color: var(--critical); color: var(--critical); }
        .btn-danger:hover { background: var(--critical-tint-strong); border-color: var(--critical); }

        .toolbar { display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap; margin: 1.25rem 0 1rem; }
        .chips { display: flex; gap: 6px; flex-wrap: wrap; }
        .chip { height: 32px; padding: 0 12px; border-radius: 16px; border: 1px solid var(--line); background: var(--card); color: var(--text-muted); font: inherit; font-size: 12.5px; font-weight: 600; cursor: pointer; }
        .chip:hover { border-color: var(--line-strong); }
        .chip.on { background: var(--ink); border-color: var(--ink); color: var(--card); }
        .chip .n { margin-left: 4px; font-weight: 700; opacity: 0.75; }

        .note { background: var(--card); border: 1px solid var(--line); border-radius: 12px; padding: 1rem 1.1rem; margin-bottom: 0.75rem; position: relative; transition: opacity 0.2s ease, transform 0.2s ease; }
        .note.unread { border-color: color-mix(in srgb, var(--ink) 45%, var(--line)); }
        .note.unread::before { content: ''; position: absolute; left: -5px; top: 18px; width: 9px; height: 9px; border-radius: 50%; background: var(--ink); box-shadow: 0 0 0 3px var(--paper); }
        .note.leaving { opacity: 0; transform: translateY(-6px); }
        .note.hidden { display: none; }
        .note-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; font-size: 12px; color: var(--text-muted); }
        .tag { display: inline-flex; align-items: center; gap: 5px; height: 22px; padding: 0 8px; border-radius: 11px; font-size: 11.5px; font-weight: 600; border: 1px solid var(--line); color: var(--text-muted); text-decoration: none; max-width: 100%; }
        .tag.eng { border-color: color-mix(in srgb, var(--ink) 40%, var(--line)); color: var(--ink); }
        a.tag.eng:hover { background: var(--paper); }
        .tag.none { border-style: dashed; }
        .note-when { margin-left: auto; font-variant-numeric: tabular-nums; }
        .note-title { margin: 0.6rem 0 0; font-size: 14.5px; font-weight: 700; }
        .note-text { margin: 0.45rem 0 0; font-size: 13.5px; line-height: 1.55; white-space: pre-wrap; overflow-wrap: anywhere; }
        .note-actions { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 0.85rem; }
        .note-actions .spacer { flex: 1; }
        .attach-row { display: none; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid var(--line); }
        .attach-row.open { display: flex; }
        .attach-row select { flex: 1 1 240px; min-width: 0; height: 32px; border-radius: 8px; border: 1px solid var(--line); background: var(--paper); color: var(--text); font: inherit; font-size: 12.5px; padding: 0 8px; }
        .confirm-row { display: none; align-items: center; gap: 8px; }
        .note.confirming .confirm-row { display: inline-flex; }
        .note.confirming .del-btn { display: none; }
        .confirm-row span { font-size: 12px; color: var(--critical); font-weight: 600; }

        .empty { text-align: center; color: var(--text-muted); font-size: 13.5px; padding: 3rem 1rem; border: 1px dashed var(--line-strong); border-radius: 12px; }
        .empty i { font-size: 28px; display: block; margin-bottom: 0.5rem; }
        .notice { display: flex; gap: 10px; align-items: flex-start; font-size: 12.5px; line-height: 1.5; padding: 0.75rem 1rem; border-radius: 10px; margin-top: 1rem; border: 1px solid color-mix(in srgb, var(--caution) 40%, var(--line)); background: color-mix(in srgb, var(--caution) 8%, transparent); }
        .notice code, .howto code { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px; }
        .howto { margin-top: 2rem; font-size: 12.5px; color: var(--text-muted); }
        .howto summary { cursor: pointer; font-weight: 600; color: var(--text); }
        .howto pre { background: var(--card); border: 1px solid var(--line); border-radius: 10px; padding: 0.85rem 1rem; overflow-x: auto; font-size: 12px; line-height: 1.55; color: var(--text); }
        .howto ul { padding-left: 1.2rem; line-height: 1.6; }
        .toast { position: fixed; bottom: 24px; left: 50%; transform: translate(-50%, 20px); background: var(--text); color: var(--paper); padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600; opacity: 0; pointer-events: none; transition: opacity 0.2s ease, transform 0.2s ease; z-index: 500; }
        .toast.show { opacity: 1; transform: translate(-50%, 0); }

        @media (max-width: 900px) { .header-inner { gap: 1rem; } .hdr-label { display: none; } .hdr-due { padding: 0 10px; } .btn-new-engagement { padding: 0 12px; } }
        @media (max-width: 480px) {
            .top-header { grid-template-columns: 1rem minmax(0, 1fr) 1rem; }
            .header-inner { gap: 0.6rem; height: 64px; }
            .brand-mark, .hdr-sep { display: none; }
            .hdr-search kbd { display: none; }
            .hdr-search { padding: 0 10px; gap: 8px; }
            .profile-wrapper { padding: 0 8px 0 4px; gap: 5px; }
            .header-right { gap: 0.35rem; }
            .page { padding-top: 1.25rem; }
            .note-when { margin-left: 0; width: 100%; }
        }
        @media (prefers-reduced-motion: reduce) { .note, .toast { transition: none; } }
    </style>
</head>
<body>

<div class="top-header">
    <div class="header-inner">
        <a href="dashboard.php" class="brand" title="Engagement Tracker">
            <span class="brand-icon">ET</span>
            <span class="brand-mark">Engagement Tracker</span>
        </a>

        <label class="hdr-search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <span class="sr-only">Search notes</span>
            <input type="search" id="noteSearch" placeholder="Search notes" autocomplete="off"<?php echo $tableMissing ? ' disabled' : ''; ?>>
            <kbd title="Press / to search">/</kbd>
        </label>

        <div class="header-right">
            <a href="dashboard.php" class="hdr-due">
                <i class="bi bi-list-ul" aria-hidden="true"></i>
                <span class="hdr-label">Engagements</span>
            </a>
            <a href="notes.php" class="hdr-due active" aria-current="page">
                <i class="bi bi-inbox" aria-hidden="true"></i><span class="hdr-label">Notes</span>
                <span class="hdr-badge" id="navBadge" <?php echo $unread ? '' : 'hidden'; ?>><?php echo $unread; ?></span>
            </a>
            <a href="time.php" class="hdr-due">
                <i class="bi bi-clock" aria-hidden="true"></i><span class="hdr-label">Time</span>
            </a>
            <a href="dashboard.php?onboard=1" class="btn-new-engagement">
                <i class="bi bi-plus-lg" aria-hidden="true"></i><span class="hdr-label">Onboard Client</span>
            </a>
            <span class="hdr-sep" aria-hidden="true"></span>

            <div class="profile-section">
                <button type="button" class="profile-wrapper" id="profileToggle" aria-haspopup="true" aria-expanded="false" aria-label="Account menu, <?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?>">
                    <span class="profile-btn"><?php echo $initials; ?></span>
                    <i class="bi bi-chevron-down" aria-hidden="true"></i>
                </button>
                <div class="profile-dropdown" id="profileDropdown">
                    <div class="profile-dropdown-header">
                        <div class="profile-dropdown-avatar"><?php echo $initials; ?></div>
                        <div>
                            <div class="profile-dropdown-name"><?php echo htmlspecialchars($_SESSION['name'] ?? ''); ?></div>
                            <div class="profile-dropdown-email"><?php echo htmlspecialchars($roleLabel); ?></div>
                        </div>
                    </div>
                    <div class="profile-dropdown-menu">
                        <?php if (in_array($_SESSION['role'] ?? '', ['admin', 'super_admin'], true)): ?>
                        <a href="settings.php" class="profile-dropdown-item"><i class="bi bi-gear"></i> Settings</a>
                        <?php endif; ?>
                        <button type="button" class="profile-dropdown-item" id="darkModeItem">
                            <i class="bi bi-moon"></i> Dark mode
                            <span class="profile-dropdown-state" id="darkModeState">Off</span>
                        </button>
                        <div class="profile-dropdown-sep" aria-hidden="true"></div>
                        <a href="<?php echo BASE_URL . '/auth/logout.php'; ?>" class="profile-dropdown-item logout"><i class="bi bi-box-arrow-right"></i> Log Out</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<main class="page">
    <div class="page-head">
        <div>
            <h1>Notes</h1>
            <p class="page-sub" id="pageSub"></p>
        </div>
        <button type="button" class="btn" id="markAllBtn"><i class="bi bi-check2-all" aria-hidden="true"></i> Mark all read</button>
    </div>

<?php if ($tableMissing): ?>
    <div class="notice"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <div>The Notes inbox isn't set up on the server yet. Run <code>php includes/migrate_create_inbox_notes_table.php</code> once, then reload.</div>
    </div>
<?php else: ?>
    <?php if (!$keyReady): ?>
    <div class="notice"><i class="bi bi-key" aria-hidden="true"></i>
        <div>No API key is set on the server yet, so the other app can't send notes. Add a line <code>NOTES_API_KEY=</code> followed by a long random value (16+ characters) to the server's <code>.env</code>.</div>
    </div>
    <?php endif; ?>

    <div class="toolbar">
        <div class="chips" role="group" aria-label="Filter notes">
            <button type="button" class="chip on" data-filter="all">All <span class="n" id="cAll"><?php echo $total; ?></span></button>
            <button type="button" class="chip" data-filter="unread">Unread <span class="n" id="cUnread"><?php echo $unread; ?></span></button>
            <button type="button" class="chip" data-filter="loose">No engagement <span class="n" id="cLoose"><?php echo $unattached; ?></span></button>
        </div>
    </div>

    <div id="noteList">
    <?php foreach ($notes as $n):
        $isRead = (int) $n['is_read'] === 1;
        $idno = (string) ($n['engagement_idno'] ?? '');
        $search = strtolower(implode(' ', [$n['note_title'], $n['note_text'], $n['source'], $idno, $n['eng_name'] ?? '', $n['engagement_hint'] ?? '']));
    ?>
        <article class="note <?php echo $isRead ? '' : 'unread'; ?>" data-id="<?php echo (int) $n['note_id']; ?>" data-read="<?php echo $isRead ? 1 : 0; ?>" data-eng="<?php echo htmlspecialchars($idno); ?>" data-search="<?php echo htmlspecialchars($search); ?>">
            <div class="note-head">
                <?php if (trim((string) $n['source']) !== ''): ?>
                    <span class="tag"><i class="bi bi-send" aria-hidden="true"></i><?php echo htmlspecialchars($n['source']); ?></span>
                <?php endif; ?>
                <span class="eng-slot">
                <?php if ($idno !== ''): ?>
                    <a class="tag eng" href="dashboard.php?open=<?php echo urlencode($idno); ?>" title="Open this engagement"><i class="bi bi-folder2-open" aria-hidden="true"></i><?php echo htmlspecialchars($idno . ' · ' . ($n['eng_name'] ?? '')); ?></a>
                <?php else: ?>
                    <span class="tag none"><?php echo trim((string) $n['engagement_hint']) !== '' ? 'Sent for “' . htmlspecialchars($n['engagement_hint']) . '”, not matched' : 'No engagement'; ?></span>
                <?php endif; ?>
                </span>
                <span class="note-when"><?php echo htmlspecialchars(noteWhen($n['created_at'])); ?></span>
            </div>
            <?php if (trim((string) $n['note_title']) !== ''): ?>
                <h2 class="note-title"><?php echo htmlspecialchars($n['note_title']); ?></h2>
            <?php endif; ?>
            <p class="note-text"><?php echo htmlspecialchars($n['note_text']); ?></p>

            <div class="note-actions">
                <button type="button" class="btn btn-sm read-btn"></button>
                <button type="button" class="btn btn-sm attach-btn"></button>
                <span class="spacer"></span>
                <button type="button" class="btn btn-sm btn-danger del-btn"><i class="bi bi-trash" aria-hidden="true"></i> Delete</button>
                <span class="confirm-row"><span>Delete this note?</span>
                    <button type="button" class="btn btn-sm btn-danger confirm-del">Delete</button>
                    <button type="button" class="btn btn-sm cancel-del">Keep</button>
                </span>
            </div>
            <div class="attach-row">
                <select aria-label="Engagement for this note"></select>
                <button type="button" class="btn btn-sm btn-primary attach-save">Save</button>
                <button type="button" class="btn btn-sm attach-cancel">Cancel</button>
            </div>
        </article>
    <?php endforeach; ?>
    </div>

    <div class="empty" id="emptyState" <?php echo $total ? 'hidden' : ''; ?>>
        <i class="bi bi-inbox" aria-hidden="true"></i>
        <span id="emptyText">No notes yet. Notes your other app sends will show up here.</span>
    </div>

    <details class="howto">
        <summary>How the other app sends notes</summary>
        <p>POST to <code><?php echo htmlspecialchars(BASE_URL); ?>/api/receive-note.php</code> with the key in the <code>X-API-Key</code> header (or <code>Authorization: Bearer</code>).</p>
        <pre>curl -X POST <?php echo htmlspecialchars(BASE_URL); ?>/api/receive-note.php \
  -H "X-API-Key: YOUR_KEY" -H "Content-Type: application/json" \
  -d '{"text": "Call Todd about the scope", "source": "Phone", "engagement": "ENG-2026-015"}'</pre>
        <ul>
            <li><code>text</code> is the only required field.</li>
            <li><code>title</code>, <code>source</code> (which app sent it) and <code>external_id</code> are optional. Sending the same <code>source</code> and <code>external_id</code> again is ignored, so retries don't create copies.</li>
            <li><code>engagement</code> is an ENG id or a name. It attaches only when it matches exactly one engagement; otherwise the note waits here for you to attach it.</li>
        </ul>
    </details>
<?php endif; ?>
</main>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script>
(function () {
    const ENGAGEMENTS = <?php echo json_encode($engagementOptions, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const API = '../api/update-inbox-note.php';
    const list = document.getElementById('noteList');
    const toastEl = document.getElementById('toast');
    let filter = 'all';
    let toastTimer = null;

    function toast(msg) {
        toastEl.textContent = msg;
        toastEl.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toastEl.classList.remove('show'), 2200);
    }
    function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    async function call(payload) {
        const res = await fetch(API, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) throw new Error(data.message || 'Something went wrong');
        return data;
    }

    // ----- counts, filters, empty state -----
    function cards() { return list ? Array.from(list.querySelectorAll('.note')) : []; }
    function refresh() {
        const all = cards();
        const unread = all.filter(c => c.dataset.read !== '1').length;
        const loose = all.filter(c => !c.dataset.eng).length;
        const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
        set('cAll', all.length); set('cUnread', unread); set('cLoose', loose);
        const sub = document.getElementById('pageSub');
        if (sub) sub.textContent = all.length ? `${all.length} note${all.length === 1 ? '' : 's'} · ${unread} unread` : 'Nothing here yet';
        const badge = document.getElementById('navBadge');
        if (badge) { badge.textContent = unread; badge.hidden = unread === 0; }
        const markAll = document.getElementById('markAllBtn');
        if (markAll) markAll.disabled = unread === 0;

        const q = (document.getElementById('noteSearch')?.value || '').trim().toLowerCase();
        let shown = 0;
        all.forEach(c => {
            const ok = (filter === 'all' || (filter === 'unread' && c.dataset.read !== '1') || (filter === 'loose' && !c.dataset.eng))
                && (!q || c.dataset.search.includes(q));
            c.classList.toggle('hidden', !ok);
            if (ok) shown++;
        });
        const empty = document.getElementById('emptyState');
        if (empty) {
            empty.hidden = shown > 0;
            document.getElementById('emptyText').textContent = all.length === 0
                ? "No notes yet. Notes your other app sends will show up here."
                : 'No notes match.';
        }
    }

    function paintCard(card) {
        const read = card.dataset.read === '1';
        card.classList.toggle('unread', !read);
        card.querySelector('.read-btn').innerHTML = read
            ? '<i class="bi bi-circle" aria-hidden="true"></i> Mark unread'
            : '<i class="bi bi-check2" aria-hidden="true"></i> Mark read';
        card.querySelector('.attach-btn').innerHTML = card.dataset.eng
            ? '<i class="bi bi-arrow-left-right" aria-hidden="true"></i> Change engagement'
            : '<i class="bi bi-paperclip" aria-hidden="true"></i> Attach to engagement';
    }

    // ----- one delegated handler for every card -----
    list?.addEventListener('click', async (ev) => {
        const btn = ev.target.closest('button');
        const card = ev.target.closest('.note');
        if (!btn || !card) return;
        const id = +card.dataset.id;
        try {
            if (btn.classList.contains('read-btn')) {
                const makeRead = card.dataset.read !== '1';
                await call({ action: 'read', id, read: makeRead });
                card.dataset.read = makeRead ? '1' : '0';
                paintCard(card); refresh();
            } else if (btn.classList.contains('attach-btn')) {
                const row = card.querySelector('.attach-row');
                const sel = row.querySelector('select');
                sel.innerHTML = '<option value="">No engagement</option>' + ENGAGEMENTS.map(e =>
                    `<option value="${esc(e.idno)}"${e.idno === card.dataset.eng ? ' selected' : ''}>${esc(e.label)}</option>`).join('');
                row.classList.add('open');
                sel.focus();
            } else if (btn.classList.contains('attach-cancel')) {
                card.querySelector('.attach-row').classList.remove('open');
            } else if (btn.classList.contains('attach-save')) {
                const sel = card.querySelector('.attach-row select');
                const idno = sel.value;
                await call({ action: 'attach', id, engagement_idno: idno });
                card.dataset.eng = idno;
                const opt = ENGAGEMENTS.find(e => e.idno === idno);
                card.querySelector('.eng-slot').innerHTML = idno
                    ? `<a class="tag eng" href="dashboard.php?open=${encodeURIComponent(idno)}" title="Open this engagement"><i class="bi bi-folder2-open" aria-hidden="true"></i>${esc(opt ? opt.label.split(' (')[0] : idno)}</a>`
                    : '<span class="tag none">No engagement</span>';
                if (idno) card.dataset.read = '1';
                card.dataset.search = card.dataset.search + ' ' + (opt ? opt.label.toLowerCase() : idno.toLowerCase());
                card.querySelector('.attach-row').classList.remove('open');
                paintCard(card); refresh();
                toast(idno ? 'Attached. It now shows in that engagement\'s notes.' : 'Detached from the engagement.');
            } else if (btn.classList.contains('del-btn')) {
                card.classList.add('confirming');
            } else if (btn.classList.contains('cancel-del')) {
                card.classList.remove('confirming');
            } else if (btn.classList.contains('confirm-del')) {
                await call({ action: 'delete', id });
                card.classList.add('leaving');
                setTimeout(() => { card.remove(); refresh(); }, 200);
                toast('Note deleted.');
            }
        } catch (err) {
            toast(err.message || 'Something went wrong');
        }
    });

    document.getElementById('markAllBtn')?.addEventListener('click', async () => {
        try {
            await call({ action: 'read_all' });
            cards().forEach(c => { c.dataset.read = '1'; paintCard(c); });
            refresh();
            toast('All notes marked read.');
        } catch (err) { toast(err.message); }
    });

    document.querySelectorAll('.chip').forEach(chip => chip.addEventListener('click', () => {
        filter = chip.dataset.filter;
        document.querySelectorAll('.chip').forEach(c => c.classList.toggle('on', c === chip));
        refresh();
    }));
    document.getElementById('noteSearch')?.addEventListener('input', refresh);

    document.addEventListener('keydown', (e) => {
        if (e.key !== '/' || e.ctrlKey || e.metaKey || e.altKey) return;
        const t = e.target;
        if (t && (t.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName))) return;
        e.preventDefault();
        document.getElementById('noteSearch')?.focus();
    });

    cards().forEach(paintCard);
    refresh();

    // ----- dark mode + profile menu, same as the dashboard -----
    const darkBtn = document.getElementById('darkModeItem');
    function paintDark(isDark) {
        darkBtn?.querySelector('i')?.classList.toggle('bi-moon', !isDark);
        darkBtn?.querySelector('i')?.classList.toggle('bi-sun', isDark);
        const state = document.getElementById('darkModeState');
        if (state) state.textContent = isDark ? 'On' : 'Off';
    }
    let dark = false;
    try { dark = localStorage.getItem('darkMode') === 'true'; } catch (e) {}
    if (dark) document.body.classList.add('dark-mode');
    paintDark(dark);
    darkBtn?.addEventListener('click', () => {
        const isDark = document.body.classList.toggle('dark-mode');
        try { localStorage.setItem('darkMode', isDark); } catch (e) {}
        paintDark(isDark);
    });
    const profileToggle = document.getElementById('profileToggle');
    const profileDropdown = document.getElementById('profileDropdown');
    profileToggle?.addEventListener('click', (e) => {
        e.stopPropagation();
        const open = profileDropdown.classList.toggle('active');
        profileToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', (e) => {
        if (!profileToggle?.contains(e.target) && !profileDropdown?.contains(e.target)) {
            profileDropdown?.classList.remove('active');
            profileToggle?.setAttribute('aria-expanded', 'false');
        }
    });
})();
</script>
</body>
</html>
