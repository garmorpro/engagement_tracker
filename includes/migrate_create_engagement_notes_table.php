<?php
/**
 * One-time migration: create engagement_notes — a running, timestamped log
 * replacing the old single engagements.eng_notes free-text field (which
 * silently overwrote itself and had no author/timestamp/type at all).
 *
 * Every entry is one of four types, matching the four buttons in the
 * drawer's "Notes & Meetings" section: 'planning' (Planning Meeting),
 * 'client' (Client Planning Meeting), 'weekly' (Weekly Status Call), or
 * 'general' (a plain note). A 'planning' entry additionally carries
 * independence_snapshot — a JSON object of {emp_name: 'Y'|'N'|null} taken
 * at the moment the meeting was logged, for the audit record of what was
 * confirmed *then* (separate from engagement_team.emp_independent, which
 * always reflects the current/latest answer — see api/add-engagement-note.php,
 * which writes both).
 *
 * eng_notes itself is left in place, untouched — this migration only adds
 * the new table alongside it. Nothing currently in eng_notes is migrated
 * into the new log automatically (there's no reliable author/timestamp to
 * attribute it to); if there's real content in there worth keeping, copy
 * it forward as one manual 'general' entry.
 *
 * Safe to run more than once — uses CREATE TABLE IF NOT EXISTS. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_create_engagement_notes_table.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$sql = "
    CREATE TABLE IF NOT EXISTS engagement_notes (
        note_id INT AUTO_INCREMENT PRIMARY KEY,
        engagement_idno VARCHAR(64) NOT NULL,
        note_type VARCHAR(20) NOT NULL,
        note_text TEXT NOT NULL,
        independence_snapshot TEXT NULL,
        author_user_id INT NULL,
        author_name VARCHAR(255) NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_engagement_created (engagement_idno, created_at)
    )
";

if (!$conn->query($sql)) {
    fwrite(STDERR, "Failed to create engagement_notes table: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "engagement_notes table ready.\n";
