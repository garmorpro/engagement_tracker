<?php
require_once 'db.php';
session_start();

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