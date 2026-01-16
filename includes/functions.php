<?php
require_once 'db.php';
session_start();

// LOGOUT

function logoutUser($conn)
{
    if (isset($_GET['logout']) && $_GET['logout'] == 1) {

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

        session_destroy();
        header("Location: /");
        exit;
    }
}


// LOGIN

function loginUser($conn)
{
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return $error;
    }

    $accountName = trim($_POST['account_name'] ?? '');
    $password    = $_POST['password'] ?? '';

    if ($accountName === '' || $password === '') {
        return 'Please enter both account name and password.';
    }

    // Fetch account
    $stmt = $conn->prepare(
        "SELECT account_name, password 
         FROM service_accounts 
         WHERE account_name = ?
         LIMIT 1"
    );

    if (!$stmt) {
        return 'System error. Please try again later.';
    }

    $stmt->bind_param("s", $accountName);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    $stmt->close();

    if (!$account || !password_verify($password, $account['password'])) {
        return 'Invalid account name or password.';
    }

    // ✅ Login success
    session_regenerate_id(true);
    $_SESSION['account_name'] = $account['account_name'];

    // Update login status
    $stmt = $conn->prepare(
        "UPDATE service_accounts 
         SET last_active = NOW(),
             logged_in = 1
         WHERE account_name = ?"
    );

    if ($stmt) {
        $stmt->bind_param("s", $_SESSION['account_name']);
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
