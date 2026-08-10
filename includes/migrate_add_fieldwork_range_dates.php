<?php
/**
 * One-time migration: give "Fieldwork - Client Calls" and
 * "Fieldwork - Documentation" a start date in addition to the single date
 * they had before, so the dashboard drawer's Timeline & Key Dates section
 * can show a range ("Jul 6, 2026 - Jul 10, 2026") instead of one date.
 *
 * Adds 4 new columns to engagement_timeline:
 *   - fieldwork_client_calls_start_date  DATE NULL
 *   - fieldwork_client_calls_end_date    DATE NULL
 *   - fieldwork_documentation_start_date DATE NULL
 *   - fieldwork_documentation_end_date   DATE NULL
 *
 * The existing fieldwork_client_calls_date / fieldwork_documentation_date
 * columns are deliberately left in place (not touched, not dropped) —
 * consistent with how this table has been extended before — but any
 * existing value in them is copied into the new *_end_date column as a
 * best-effort backfill, since the old single date was always understood as
 * "when this phase is due," which is what the new end date represents. The
 * start date is left blank; there's no way to infer it retroactively.
 *
 * Safe to run more than once — skips columns that already exist, and the
 * backfill only touches rows where the new end date is still NULL. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_fieldwork_range_dates.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$columns = [
    'fieldwork_client_calls_start_date'  => 'DATE NULL',
    'fieldwork_client_calls_end_date'    => 'DATE NULL',
    'fieldwork_documentation_start_date' => 'DATE NULL',
    'fieldwork_documentation_end_date'   => 'DATE NULL',
];

foreach ($columns as $name => $definition) {
    $check = $conn->query("SHOW COLUMNS FROM `engagement_timeline` LIKE '{$name}'");
    if ($check && $check->num_rows > 0) {
        echo "Column {$name} already exists, skipping.\n";
        continue;
    }
    if (!$conn->query("ALTER TABLE `engagement_timeline` ADD COLUMN `{$name}` {$definition}")) {
        fwrite(STDERR, "Failed to add column {$name}: " . $conn->error . PHP_EOL);
        exit(1);
    }
    echo "Added column {$name}.\n";
}

$backfills = [
    'fieldwork_client_calls_date'  => 'fieldwork_client_calls_end_date',
    'fieldwork_documentation_date' => 'fieldwork_documentation_end_date',
];

foreach ($backfills as $oldCol => $newCol) {
    $sql = "UPDATE `engagement_timeline`
            SET `{$newCol}` = `{$oldCol}`
            WHERE `{$oldCol}` IS NOT NULL AND `{$newCol}` IS NULL";
    if (!$conn->query($sql)) {
        fwrite(STDERR, "Failed to backfill {$newCol} from {$oldCol}: " . $conn->error . PHP_EOL);
        exit(1);
    }
    echo "Backfilled {$newCol} from {$oldCol} ({$conn->affected_rows} row(s)).\n";
}

echo "Done.\n";
