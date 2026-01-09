<?php
require_once 'db.php';
session_start();

// get engagement details

// Function to get all engagements
function getAllEngagements($conn) {
    $sql = "SELECT `eng_id`, `eng_idno`, `eng_name`, `eng_manager`, `eng_senior`, `eng_staff`, 
                   `eng_senior_dol`, `eng_staff_dol`, `eng_poc`, `eng_location`, `eng_repeat`, 
                   `eng_audit_type`, `eng_scope`, `eng_tsc`, `eng_period`, `eng_as_of_date`, 
                   `eng_irl_due`, `eng_client_planning_call`, `eng_scheduled_client_planning`, 
                   `eng_fieldwork`, `eng_leadsheet_start`, `eng_leadsheet_due`, `eng_draft_due`, 
                   `eng_final_due`, `eng_archive`, `eng_section_3_requested`, `eng_last_communication`, 
                   `eng_notes`, `eng_status`, `eng_created`, `eng_updated` 
            FROM `engagements`";

    $result = $conn->query($sql);

    $engagements = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $engagements[] = $row;
        }
        $result->free();
    }

    return $engagements;
}
