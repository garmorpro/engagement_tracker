<?php
require_once 'db.php';
session_start();

// LOGOUT

function logoutUser($conn)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

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
        "SELECT user_id, account_name, password
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
            ON mgr.eng_id = e.eng_id
           AND LOWER(mgr.role) = 'manager'

        LEFT JOIN engagement_milestones ms
            ON ms.eng_id = e.eng_id
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
            ON mgr.eng_id = e.eng_id
           AND LOWER(mgr.role) = 'manager'

        LEFT JOIN engagement_milestones ms
            ON ms.eng_id = e.eng_id
           AND ms.milestone_type LIKE 'final%'

        WHERE e.eng_status != 'archived'

        GROUP BY e.eng_id
        ORDER BY e.eng_id DESC
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

function getEngagementMilestones($conn) {
    $sql = "SELECT eng_id, milestone_type, due_date
            FROM engagement_milestones";
    $result = $conn->query($sql);
    $milestones = [];

    while ($row = $result->fetch_assoc()) {
        $engId = (int)$row['eng_id'];
        $type = strtolower($row['milestone_type']); // normalize type

        // Map your DB milestone types to JS-friendly keys
        if (strpos($type, 'planning call') !== false) $type = 'planningCall';
        elseif (strpos($type, 'fieldwork') !== false) $type = 'fieldworkStart';
        elseif (strpos($type, 'draft') !== false) $type = 'draftDue';
        elseif (strpos($type, 'final') !== false) $type = 'finalDue';
        else continue; // skip unknown

        $milestones[$engId][$type] = $row['due_date'];
    }

    return $milestones;
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



// Get final due date

function getOverdueEngagements(mysqli $conn): array
{
    $sql = "
        SELECT
            e.eng_id,
            e.eng_name,
            e.eng_idno,
            e.eng_updated,
            MIN(ms.due_date) AS final_due_date
        FROM engagements e
        JOIN engagement_milestones ms
            ON ms.eng_id = e.eng_id
           AND ms.milestone_type LIKE 'final%'
        WHERE e.eng_status != 'archived'
          AND ms.due_date < CURDATE()
        GROUP BY e.eng_id
        ORDER BY e.eng_updated DESC
        LIMIT 3
    ";

    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get due this week engagements

function getEngagementsDueThisWeek(mysqli $conn): array
{
    $sql = "
        SELECT
            e.eng_id,
            e.eng_name,
            e.eng_idno,
            MIN(ms.due_date) AS final_due_date
        FROM engagements e
        JOIN engagement_milestones ms
            ON ms.eng_id = e.eng_id
           AND ms.milestone_type LIKE 'final%'
        WHERE e.eng_status != 'archived'
          AND ms.due_date BETWEEN CURDATE()
                              AND DATE_ADD(CURDATE(), INTERVAL (7 - WEEKDAY(CURDATE())) DAY)
        GROUP BY e.eng_id
        ORDER BY final_due_date ASC
        LIMIT 3
    ";

    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get new clients engagements

function getNewClientEngagements(mysqli $conn): array
{
    $sql = "
        SELECT
            e.eng_id,
            e.eng_name,
            e.eng_idno,
            MIN(ms.due_date) AS final_due_date
        FROM engagements e
        LEFT JOIN engagement_milestones ms
            ON ms.eng_id = e.eng_id
           AND ms.milestone_type LIKE 'final%'
        WHERE e.eng_status != 'archived'
          AND e.eng_repeat = 'N'
        GROUP BY e.eng_id
        ORDER BY final_due_date ASC
        LIMIT 3
    ";

    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Get recently updated engagements
function getRecentlyUpdatedEngagements(mysqli $conn): array
{
    $sql = "
        SELECT
            e.eng_id,
            e.eng_name,
            e.eng_idno,
            e.eng_updated,
            MIN(ms.due_date) AS final_due_date
        FROM engagements e
        LEFT JOIN engagement_milestones ms
            ON ms.eng_id = e.eng_id
           AND ms.milestone_type LIKE 'final%'
        WHERE e.eng_status != 'archived'
        GROUP BY e.eng_id
        ORDER BY e.eng_updated DESC
        LIMIT 3
    ";

    $result = $conn->query($sql);
    if (!$result) {
        return [];
    }

    return $result->fetch_all(MYSQLI_ASSOC);
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
             WHERE t.eng_id = e.eng_id AND t.role = 'Manager' LIMIT 1) AS manager_name,
            -- Count of team members excluding manager
            (SELECT COUNT(*) FROM engagement_team t 
             WHERE t.eng_id = e.eng_id AND t.role != 'Manager') AS team_count
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
            WHERE eng_id = '$eng_id' AND is_completed = 'N' AND due_date IS NOT NULL
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
