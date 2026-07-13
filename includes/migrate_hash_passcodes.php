<?php
/**
 * One-time migration: hash any plaintext PINs in service_accounts.passcode.
 *
 * Safe to run more than once — rows already holding a bcrypt hash are skipped.
 * CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_hash_passcodes.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

// Widen the column defensively — bcrypt hashes are ~60 chars, well beyond a
// raw 4-6 digit PIN. Safe to run even if already wide enough.
if (!$conn->query("ALTER TABLE `service_accounts` MODIFY `passcode` VARCHAR(255) NOT NULL")) {
    fwrite(STDERR, "Failed to widen passcode column: " . $conn->error . PHP_EOL);
    exit(1);
}
echo "passcode column widened to VARCHAR(255).\n";

$result = $conn->query("SELECT `user_id`, `passcode` FROM `service_accounts`");
if (!$result) {
    fwrite(STDERR, "Failed to read service_accounts: " . $conn->error . PHP_EOL);
    exit(1);
}

$hashed = 0;
$skipped = 0;

while ($row = $result->fetch_assoc()) {
    $userId = (int) $row['user_id'];
    $passcode = $row['passcode'];

    // Already hashed (bcrypt) — leave it alone.
    if (preg_match('/^\$2[axy]\$\d{2}\$/', $passcode)) {
        $skipped++;
        continue;
    }

    $newHash = password_hash($passcode, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE `service_accounts` SET `passcode` = ? WHERE `user_id` = ?");
    $stmt->bind_param('si', $newHash, $userId);
    if (!$stmt->execute()) {
        fwrite(STDERR, "Failed to update user_id {$userId}: " . $stmt->error . PHP_EOL);
        $stmt->close();
        continue;
    }
    $stmt->close();
    $hashed++;
}

echo "Done. Hashed {$hashed} account(s), skipped {$skipped} already-hashed account(s).\n";
