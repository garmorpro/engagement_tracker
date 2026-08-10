<?php
/**
 * One-time / on-demand CLI helper: promote a service_accounts row to
 * role = 'admin' by email, so that account can reach the in-app Settings
 * page (pages/settings.php).
 *
 * Needed because there was previously no way to grant the 'admin' role
 * through the UI at all — the old pre-login "Admin Dashboard" was gated by
 * a completely separate 6-digit super-admin PIN tied to one hidden special
 * account, unrelated to any regular account's own role. Everyone's regular
 * login account is very likely still role = 'standard' as a result, this
 * script is how you bootstrap the first real admin.
 *
 * Safe to run more than once — no-ops if the account is already admin (or
 * super_admin). CLI only.
 *
 * Run from the project root:
 *   php includes/migrate_promote_admin.php someone@example.com
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Forbidden');
}

$basePath = dirname(__DIR__);
require_once $basePath . '/path.php';
require_once $basePath . '/includes/functions.php';

$email = trim($argv[1] ?? '');
if ($email === '') {
    fwrite(STDERR, "Usage: php includes/migrate_promote_admin.php someone@example.com\n");
    exit(1);
}

$stmt = $conn->prepare("SELECT user_id, name, role FROM service_accounts WHERE email = ? AND status = 'active'");
$stmt->bind_param('s', $email);
$stmt->execute();
$account = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$account) {
    fwrite(STDERR, "No active account found with email {$email}\n");
    exit(1);
}

if (in_array($account['role'], ['admin', 'super_admin'], true)) {
    echo "{$account['name']} ({$email}) is already role '{$account['role']}' — nothing to do.\n";
    exit(0);
}

$upd = $conn->prepare("UPDATE service_accounts SET role = 'admin' WHERE user_id = ?");
$upd->bind_param('i', $account['user_id']);
$upd->execute();
$upd->close();

echo "Promoted {$account['name']} ({$email}) to role 'admin'.\n";
echo "Role is read from the session at login time, so they'll need to log out and log back in (or just close the tab and sign in again) before Settings shows up in their profile dropdown.\n";
