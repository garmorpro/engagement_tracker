<?php
/**
 * One-time migration: let an engagement hold several planning docs.
 * engagements.eng_planning_doc was VARCHAR(255) holding one filename; it
 * becomes TEXT holding one filename per line (see planningDocList() in
 * includes/functions.php). Existing single filenames stay valid as-is.
 *
 * Safe to run more than once. CLI only.
 *   php includes/migrate_planning_docs_multiple.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$col = $conn->query("SHOW COLUMNS FROM `engagements` LIKE 'eng_planning_doc'")->fetch_assoc();
if (!$col) {
    fwrite(STDERR, "Column eng_planning_doc does not exist; run includes/migrate_add_engagement_screenshot.php first.\n");
    exit(1);
}
if (stripos($col['Type'], 'text') !== false) {
    echo "eng_planning_doc is already {$col['Type']}, skipping.\n";
    exit(0);
}
if (!$conn->query("ALTER TABLE `engagements` MODIFY `eng_planning_doc` TEXT NULL")) {
    fwrite(STDERR, "Failed to widen eng_planning_doc: " . $conn->error . PHP_EOL);
    exit(1);
}
echo "eng_planning_doc is now TEXT (one filename per line).\n";
