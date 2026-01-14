<?php
include 'db.php'; // Make sure this file exists and $conn is valid

$eng_id = isset($eng) && isset($eng['eng_id']) ? (int)$eng['eng_id'] : 0;

$milestonesData = [];

if ($eng_id > 0 && isset($conn)) {
    $stmt = $conn->prepare("SELECT ms_id, milestone_type, due_date, is_completed FROM engagement_milestones WHERE eng_id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $eng_id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $milestonesData[$row['ms_id']] = $row;
        }
        $stmt->close();
    } else {
        echo "<p>Error preparing milestone query: " . htmlspecialchars($conn->error) . "</p>";
    }
}
function formatMilestoneName($type) {
    return ucwords(str_replace('_', ' ', (string)$type));
}
?>
