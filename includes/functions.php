<?php

require_once __DIR__ . '/session_config.php';
startSecureSession();
require_once 'db.php';


// API AUTH GUARD
// Call at the top of any api/*.php endpoint, after this file is required.
// Ends the request with a 401 JSON response if there is no logged-in session.
function requireApiAuth()
{
    if (empty($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
}

// ADMIN ACCOUNT-MANAGEMENT GUARD
// Call at the top of any account-management endpoint (get_accounts, register,
// get_account_details, update_account, delete_account). Requires that
// verify_admin_pin.php has been passed within the last 15 minutes.
function requireAdminVerified()
{
    $window = 15 * 60; // seconds
    if (empty($_SESSION['admin_verified']) || (time() - $_SESSION['admin_verified']) > $window) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Admin verification required']);
        exit;
    }
}


// RATE LIMITING
// Backed by the login_attempts table (see includes/migrate_create_login_attempts_table.php).
// $identifier scopes the limit (e.g. an IP address, or "ip:user_id"); $type separates
// independent buckets (e.g. "login_ip", "login_user", "admin_pin").

function getClientIp()
{
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Only trust proxy-supplied IP headers when the direct connection is from
    // a loopback address (i.e. a local reverse proxy). Trusting these headers
    // unconditionally would let any client spoof them to dodge rate limits.
    $trustedProxy = in_array($remoteAddr, ['127.0.0.1', '::1'], true);

    if ($trustedProxy) {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Nearest trusted proxy prepends the original client IP first.
            $forwardedIps = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $clientIp = trim($forwardedIps[0]);
            if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
                return $clientIp;
            }
        }
        if (!empty($_SERVER['HTTP_X_REAL_IP']) && filter_var($_SERVER['HTTP_X_REAL_IP'], FILTER_VALIDATE_IP)) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }
    }

    return $remoteAddr;
}

// Returns true if $identifier has hit $maxAttempts failures for $type within $windowSeconds.
function isRateLimited($conn, string $identifier, string $type, int $maxAttempts, int $windowSeconds): bool
{
    $stmt = $conn->prepare(
        "SELECT COUNT(*) FROM login_attempts
         WHERE identifier = ? AND attempt_type = ? AND attempted_at > (NOW() - INTERVAL ? SECOND)"
    );
    $stmt->bind_param('ssi', $identifier, $type, $windowSeconds);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return $count >= $maxAttempts;
}

function recordFailedAttempt($conn, string $identifier, string $type): void
{
    $stmt = $conn->prepare(
        "INSERT INTO login_attempts (identifier, attempt_type, attempted_at) VALUES (?, ?, NOW())"
    );
    $stmt->bind_param('ss', $identifier, $type);
    $stmt->execute();
    $stmt->close();
}

function clearAttempts($conn, string $identifier, string $type): void
{
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE identifier = ? AND attempt_type = ?");
    $stmt->bind_param('ss', $identifier, $type);
    $stmt->execute();
    $stmt->close();
}


// LOGOUT

function logoutUser($conn)
{
    if (!empty($_SESSION['account_name'])) {
        $accountName = $_SESSION['account_name'];

        $stmt = $conn->prepare(
            "UPDATE service_accounts
             SET last_active = NOW(),
                 logged_in = 0
             WHERE account_name = ?"
        );

        if ($stmt) {
            $stmt->bind_param("s", $accountName);
            $stmt->execute();
            $stmt->close();
        }
    }

    session_unset();
    session_destroy();

    header("Location: /");
    exit;
}



// LOGIN

function loginUser($conn)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return '';
    }

    $accountName = trim($_POST['account_name'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($accountName === '' || $password === '') {
        return 'Please enter both account name and password.';
    }

    $stmt = $conn->prepare(
        "SELECT user_id, name, account_name, password
         FROM service_accounts
         WHERE account_name = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return 'System error. Please try again.';
    }

    $stmt->bind_param("s", $accountName);
    $stmt->execute();
    $result  = $stmt->get_result();
    $account = $result->fetch_assoc();
    $stmt->close();

    if (!$account || !password_verify($password, $account['password'])) {
        return 'Invalid account name or password.';
    }

    // ✅ Login success
    session_regenerate_id(true);

    $_SESSION['user_id']      = $account['user_id'];
    $_SESSION['name']    = $account['name'];
    $_SESSION['account_name'] = $account['account_name'];

    // Update login state
    $stmt = $conn->prepare(
        "UPDATE service_accounts
         SET last_active = NOW(),
             logged_in = 1
         WHERE user_id = ?"
    );

    if ($stmt) {
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: /pages/dashboard.php");
    exit;
}



// get engagement details

// $engagements = getAllEngagements();

// Function to get all engagements with manager + final due
function getAllEngagements(mysqli $conn): array
{
    $sql = "
        SELECT
            e.*,

            -- Engagement Manager (single expected)
            MAX(mgr.emp_name) AS eng_manager,

            -- Final Due Date
            MIN(ms.due_date) AS eng_final_due

        FROM engagements e

        LEFT JOIN engagement_team mgr
            ON mgr.engagement_idno = e.eng_idno
           AND LOWER(mgr.role) = 'manager'

        LEFT JOIN engagement_milestones ms
            ON ms.engagement_idno = e.eng_idno
           AND ms.milestone_type LIKE 'final%'

        GROUP BY e.eng_id
        ORDER BY e.eng_id DESC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}


function getAllTimelineData(mysqli $conn): array
{
    $sql = "
        SELECT * FROM engagement_timeline
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllTeamData(mysqli $conn): array
{
    $sql = "
        SELECT * FROM engagement_team
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllMilestones(mysqli $conn): array
{
    $sql = "
        SELECT * FROM engagement_milestones
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllActiveEngagements(mysqli $conn): array
{
    $sql = "
        SELECT
            e.*,

            -- Engagement Manager (single expected)
            MAX(mgr.emp_name) AS eng_manager,

            -- Final Due Date
            MIN(ms.due_date) AS eng_final_due

        FROM engagements e

        LEFT JOIN engagement_team mgr
            ON mgr.engagement_idno = e.eng_idno
           AND LOWER(mgr.role) = 'manager'

        LEFT JOIN engagement_milestones ms
            ON ms.engagement_idno = e.eng_idno
           AND ms.milestone_type LIKE 'final%'

        WHERE e.eng_status != 'archived'

        GROUP BY e.eng_idno
        ORDER BY e.eng_idno DESC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}




// automate eng_idno

function getNextEngagementId($conn): string
{
    $currentYear = date('Y');

    $sql = "
        SELECT eng_idno
        FROM engagements
        WHERE eng_idno LIKE CONCAT('ENG-', ?, '-%')
        ORDER BY eng_idno DESC
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $currentYear);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);

    $nextNumber = 1;

    if ($row = mysqli_fetch_assoc($result)) {
        $parts = explode('-', $row['eng_idno']);
        $nextNumber = ((int)$parts[2]) + 1;
    }

    mysqli_stmt_close($stmt);

    return sprintf('ENG-%s-%03d', $currentYear, $nextNumber);
}

function getActiveEngagementCount(mysqli $conn): int
{
    $sql = "
        SELECT COUNT(*) AS active_count
        FROM engagements
        WHERE eng_status != 'archived'
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row['active_count'] ?? 0);
}

function getArchivedEngagementCount(mysqli $conn): int
{
    $sql = "
        SELECT COUNT(*) AS archived_count
        FROM engagements
        WHERE eng_status = 'archived'
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return 0;
    }

    $row = $result->fetch_assoc();

    return (int) ($row['archived_count'] ?? 0);
}



function getAllActiveEngagementsMobile(mysqli $conn): array
{
    // Query active engagements (exclude archived)
    $sql = "
        SELECT 
            e.eng_id,
            e.eng_status,
            e.eng_name,
            e.eng_idno,
            e.eng_audit_type,
            -- Get manager name
            (SELECT emp_name FROM engagement_team t 
             WHERE t.engagement_idno = e.eng_idno AND t.role = 'Manager' LIMIT 1) AS manager_name,
            -- Count of team members excluding manager
            (SELECT COUNT(*) FROM engagement_team t 
             WHERE t.engagement_idno = e.eng_idno AND t.role != 'Manager') AS team_count
        FROM engagements e
        WHERE e.eng_status != 'archived'
        ORDER BY e.eng_name ASC
    ";

    $result = $conn->query($sql);

    if (!$result) {
        return [];
    }

    $engagements = [];

    while ($row = $result->fetch_assoc()) {
        $eng_id = $row['eng_id'];

        // Get team members (excluding manager) for initials
        $teamMembers = [];
        $team_sql = "SELECT emp_name FROM engagement_team WHERE eng_id = '$eng_id' AND role != 'Manager'";
        $team_result = $conn->query($team_sql);
        if ($team_result) {
            while ($t = $team_result->fetch_assoc()) {
                $teamMembers[] = $t['emp_name'];
            }
        }

        // Get next milestone (closest due date that is not completed and has a due_date)
        $milestone = null;
        $milestone_sql = "
            SELECT milestone_type, due_date, is_completed
            FROM engagement_milestones
            WHERE engagement_idno = '$eng_id' AND is_completed = 'N' AND due_date IS NOT NULL
            ORDER BY due_date ASC
            LIMIT 1
        ";
        $milestone_result = $conn->query($milestone_sql);
        if ($milestone_result && $milestone_result->num_rows > 0) {
            $milestone = $milestone_result->fetch_assoc();
        } else {
            // If no milestone with due_date exists, show placeholder
            $milestone = [
                'milestone_type' => 'No due dates set',
                'due_date' => null,
                'is_completed' => 0
            ];
        }

        $engagements[] = [
            'eng_id' => $row['eng_id'],
            'eng_status' => $row['eng_status'],
            'eng_name' => $row['eng_name'],
            'eng_idno' => $row['eng_idno'],
            'eng_audit_type' => $row['eng_audit_type'],
            'manager_name' => $row['manager_name'] ?? '',
            'team_count' => (int) $row['team_count'],
            'team_members' => $teamMembers,
            'next_milestone' => $milestone,
        ];
    }

    return $engagements;
}


// APP SETTINGS
// Simple key/value store for app-wide config that should be editable from
// the Admin Dashboard rather than a server file (currently just the Slack
// webhook URL, but built to hold more than one setting).

function getAppSetting(mysqli $conn, string $key): ?string
{
    $stmt = $conn->prepare("SELECT setting_value FROM app_settings WHERE setting_key = ?");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['setting_value'] ?? null;
}

function setAppSetting(mysqli $conn, string $key, string $value): bool
{
    $stmt = $conn->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)
                             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->bind_param('ss', $key, $value);
    return $stmt->execute();
}

// Posts $message to the configured Slack webhook, if one is set. Silently
// no-ops when unconfigured (Slack is optional) and never throws — a Slack
// delivery failure should never break notification creation, which is why
// this is called from inside createNotification() rather than the other
// way around.
function sendSlackNotification(mysqli $conn, string $message): void
{
    // Missing setting = enabled, so installs from before this toggle existed
    // keep working until someone explicitly flips it off from the Admin
    // Dashboard.
    if (getAppSetting($conn, 'slack_enabled') === '0') {
        return;
    }

    $webhookUrl = getAppSetting($conn, 'slack_webhook_url');
    if (empty($webhookUrl)) {
        return;
    }

    $payload = json_encode(['text' => $message]);

    $ch = curl_init($webhookUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('Slack notification failed: ' . curl_error($ch));
    }
    curl_close($ch);
}

// Posts $title/$message as a push notification to the configured ntfy.sh
// topic URL, if one is set. Same optional/never-throws contract as
// sendSlackNotification() above — this runs alongside Slack, not instead of
// it, so a missing or failing ntfy config must never block anything else.
function sendNtfyNotification(mysqli $conn, string $title, string $message): void
{
    // Missing setting = enabled, so installs from before this toggle existed
    // keep working until someone explicitly flips it off from the Admin
    // Dashboard.
    if (getAppSetting($conn, 'ntfy_enabled') === '0') {
        return;
    }

    $topicUrl = getAppSetting($conn, 'ntfy_topic_url');
    if (empty($topicUrl)) {
        return;
    }

    $ch = curl_init($topicUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Title: ' . $title,
        'Priority: default',
        'Content-Type: text/plain; charset=utf-8'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    if (curl_errno($ch)) {
        error_log('ntfy notification failed: ' . curl_error($ch));
    }
    curl_close($ch);
}
