<?php
/**
 * One-time migration: create inbox_notes — notes sent in by another app
 * (api/receive-note.php) and shown on the Notes page (pages/notes.php).
 *
 * A note may or may not be tied to an engagement:
 *   - engagement_hint  what the sender said (an ENG id or a name), kept as received
 *   - engagement_idno  the engagement it is actually attached to (NULL = just a quick note)
 * Attached notes also appear in that engagement's Notes & Meetings log (getEngagementNotes()).
 *
 * source + external_id: if the sending app gives each note an id, a retry of the same note
 * is ignored instead of creating a duplicate (unique on the pair; NULL external_id never collides).
 *
 * Safe to run more than once — CREATE TABLE IF NOT EXISTS. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_create_inbox_notes_table.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$sql = "
    CREATE TABLE IF NOT EXISTS inbox_notes (
        note_id INT AUTO_INCREMENT PRIMARY KEY,
        note_title VARCHAR(255) NULL,
        note_text TEXT NOT NULL,
        source VARCHAR(100) NOT NULL DEFAULT '',
        external_id VARCHAR(100) NULL,
        engagement_hint VARCHAR(255) NULL,
        engagement_idno VARCHAR(64) NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uniq_source_external (source, external_id),
        INDEX idx_read_created (is_read, created_at),
        INDEX idx_engagement (engagement_idno)
    ) DEFAULT CHARSET=utf8mb4
";

if (!$conn->query($sql)) {
    fwrite(STDERR, "Failed to create inbox_notes table: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "inbox_notes table ready.\n";
