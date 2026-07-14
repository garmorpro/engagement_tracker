<?php
/**
 * One-time migration: add fieldwork_client_calls_date/_completed_at and
 * fieldwork_documentation_date/_completed_at to engagement_timeline, splitting
 * the single "Fieldwork" step into two for the dashboard drawer's Timeline &
 * Key Dates section (fieldwork often spans a distinct client-calls phase and
 * a separate documentation phase).
 *
 * Deliberately does NOT touch or drop the existing fieldwork_date/
 * fieldwork_completed_at columns — those stay in place, since
 * engagement-details.php, archived-engagement-details.php, the mobile view,
 * tools.php's work-balance tool, and the notification system still read them
 * and were not part of this change.
 *
 * Safe to run more than once — skips columns that already exist. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_fieldwork_split_columns.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$columns = [
    'fieldwork_client_calls_date'          => 'DATE NULL',
    'fieldwork_client_calls_completed_at'  => 'DATETIME NULL',
    'fieldwork_documentation_date'         => 'DATE NULL',
    'fieldwork_documentation_completed_at' => 'DATETIME NULL',
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

echo "Done.\n";
