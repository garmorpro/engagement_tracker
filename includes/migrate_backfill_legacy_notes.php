<?php
/**
 * One-time backfill: for every engagement whose old engagements.eng_notes
 * field still has content, insert it into the new engagement_notes log as
 * a regular 'general' entry — so it shows up in the log like everything
 * else instead of sitting in a separate "from before this log existed"
 * callout (which this migration is what lets that callout go away).
 *
 * There's no real author or exact timestamp for this old content, so it's
 * attributed to "Legacy note" (author_user_id left NULL) and dated to
 * eng_updated (falling back to eng_created, then NOW()) as the closest
 * honest guess at when it was last touched — not a claim about who wrote
 * it or exactly when.
 *
 * Idempotent: skips any engagement that already has a general note with
 * this exact text (so running this twice, or after some engagements were
 * already migrated by hand, doesn't create duplicates). Does NOT clear
 * eng_notes itself — that column is left as-is; this only copies forward.
 *
 * Safe to run more than once. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_backfill_legacy_notes.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$tableCheck = $conn->query("SHOW TABLES LIKE 'engagement_notes'");
if (!$tableCheck || $tableCheck->num_rows === 0) {
    fwrite(STDERR, "engagement_notes table doesn't exist yet — run includes/migrate_create_engagement_notes_table.php first.\n");
    exit(1);
}

$result = $conn->query("SELECT eng_idno, eng_notes, eng_created, eng_updated FROM engagements WHERE eng_notes IS NOT NULL AND TRIM(eng_notes) != ''");
if (!$result) {
    fwrite(STDERR, "Failed to query engagements: " . $conn->error . PHP_EOL);
    exit(1);
}

$migrated = 0;
$skipped = 0;

$checkStmt = $conn->prepare("SELECT 1 FROM engagement_notes WHERE engagement_idno = ? AND note_type = 'general' AND note_text = ? LIMIT 1");
$insertStmt = $conn->prepare("INSERT INTO engagement_notes (engagement_idno, note_type, note_text, author_user_id, author_name, created_at) VALUES (?, 'general', ?, NULL, 'Legacy note', ?)");

while ($row = $result->fetch_assoc()) {
    $engagementIdno = $row['eng_idno'];
    $noteText = trim($row['eng_notes']);

    $checkStmt->bind_param('ss', $engagementIdno, $noteText);
    $checkStmt->execute();
    $exists = (bool) $checkStmt->get_result()->fetch_row();
    if ($exists) {
        $skipped++;
        continue;
    }

    $createdAt = $row['eng_updated'] ?: ($row['eng_created'] ?: date('Y-m-d H:i:s'));

    $insertStmt->bind_param('sss', $engagementIdno, $noteText, $createdAt);
    if (!$insertStmt->execute()) {
        fwrite(STDERR, "Failed to insert note for {$engagementIdno}: " . $insertStmt->error . PHP_EOL);
        continue;
    }
    $migrated++;
    echo "Migrated legacy note for {$engagementIdno}.\n";
}

$checkStmt->close();
$insertStmt->close();

echo "Done. Migrated {$migrated}, skipped {$skipped} already-present.\n";
