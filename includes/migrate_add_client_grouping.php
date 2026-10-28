<?php
/**
 * One-time migration for "Client" grouping (several engagements that belong to one
 * client, e.g. PCMI TruComply + PCMI - SCS/SEA + PCMI/PCRS):
 *   - engagements.eng_client (VARCHAR 255, NULL): the shared client name. Engagements
 *     with the same client show each other in the drawer and can share notes.
 *   - engagement_notes.note_scope ('engagement' default | 'client'): a 'client' note
 *     is visible on every engagement of the same client, not just the one it was
 *     logged on.
 * Nothing else about an engagement is shared: timelines, DOL, team and dates stay
 * per engagement.
 *
 * Safe to run more than once. CLI only.
 *   php includes/migrate_add_client_grouping.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

foreach ([
    ['engagements', 'eng_client', "ALTER TABLE `engagements` ADD COLUMN `eng_client` VARCHAR(255) NULL"],
    ['engagement_notes', 'note_scope', "ALTER TABLE `engagement_notes` ADD COLUMN `note_scope` VARCHAR(10) NOT NULL DEFAULT 'engagement'"],
] as [$table, $column, $alter]) {
    $col = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($col && $col->num_rows > 0) {
        echo "$table.$column already exists, skipping.\n";
        continue;
    }
    if (!$conn->query($alter)) {
        fwrite(STDERR, "Failed to add $table.$column: " . $conn->error . PHP_EOL);
        exit(1);
    }
    echo "Added $table.$column.\n";
}
