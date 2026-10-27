<?php
/**
 * One-time migration for the PCI checklist (see PCI_CHECKLIST in includes/functions.php):
 *   - creates engagement_checklist, one row per completed step per engagement
 *     (untick = row deleted)
 *   - adds engagements.eng_pci_checklist (0/1): PCI-only engagements always get
 *     the checklist; an engagement with PCI plus other audit types gets it only
 *     once someone presses "Add PCI checklist" (this flag), default 0.
 *   - adds engagements.eng_roc_delivery_date (DATE): the PCI ROC delivery date,
 *     asked for whenever PCI is a selected audit type.
 *
 * Safe to run more than once. CLI only.
 *   php includes/migrate_create_engagement_checklist_table.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$sql = "CREATE TABLE IF NOT EXISTS `engagement_checklist` (
    `checklist_id` INT NOT NULL AUTO_INCREMENT,
    `engagement_idno` VARCHAR(64) NOT NULL,
    `step_key` VARCHAR(40) NOT NULL,
    `completed_at` DATETIME NOT NULL,
    `completed_by` VARCHAR(255) NULL,
    PRIMARY KEY (`checklist_id`),
    UNIQUE KEY `uq_engagement_step` (`engagement_idno`, `step_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$conn->query($sql)) {
    fwrite(STDERR, "Failed to create engagement_checklist: " . $conn->error . PHP_EOL);
    exit(1);
}
echo "engagement_checklist is ready.\n";

foreach ([
    'eng_pci_checklist'     => "ALTER TABLE `engagements` ADD COLUMN `eng_pci_checklist` TINYINT(1) NOT NULL DEFAULT 0",
    'eng_roc_delivery_date' => "ALTER TABLE `engagements` ADD COLUMN `eng_roc_delivery_date` DATE NULL",
] as $column => $alter) {
    $col = $conn->query("SHOW COLUMNS FROM `engagements` LIKE '$column'");
    if ($col && $col->num_rows > 0) {
        echo "engagements.$column already exists, skipping.\n";
        continue;
    }
    if (!$conn->query($alter)) {
        fwrite(STDERR, "Failed to add engagements.$column: " . $conn->error . PHP_EOL);
        exit(1);
    }
    echo "Added engagements.$column.\n";
}
