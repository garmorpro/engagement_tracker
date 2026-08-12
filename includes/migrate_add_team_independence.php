<?php
/**
 * One-time migration: add emp_independent to engagement_team — whether
 * this person has confirmed they're independent from the client on this
 * specific engagement (an audit independence attestation: someone could be
 * independent on one engagement and not on another, e.g. a prior
 * employment or personal relationship with a particular client, so this
 * lives per engagement assignment, not on the employees roster).
 *
 * NULL = not yet answered, 'Y' = independent, 'N' = not independent.
 * For now this is set by whoever manages the team (there's no separate
 * employee login yet) — see the drawer's Team card. The longer-term plan
 * is for each employee to self-attest once they have their own account,
 * with managers/VPs reviewing the roll-up per engagement; this column is
 * the same field either way, just who sets it changes later.
 *
 * Safe to run more than once — skips if the column already exists. CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_add_team_independence.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$check = $conn->query("SHOW COLUMNS FROM `engagement_team` LIKE 'emp_independent'");
if ($check && $check->num_rows > 0) {
    echo "Column emp_independent already exists, skipping.\n";
    exit(0);
}

if (!$conn->query("ALTER TABLE `engagement_team` ADD COLUMN `emp_independent` ENUM('Y','N') NULL")) {
    fwrite(STDERR, "Failed to add column emp_independent: " . $conn->error . PHP_EOL);
    exit(1);
}

echo "Added column emp_independent.\n";
echo "Done.\n";
