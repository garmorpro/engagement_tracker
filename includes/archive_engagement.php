<?php
include '/db.php'; // your DB connection

if (!empty($_GET['eng_id'])) {
    $engId = $_GET['eng_id'];

    // Update engagement status to 'archived'
    $stmt = $conn->prepare("UPDATE engagements SET eng_status = 'archived' WHERE eng_idno = ?");
    $stmt->bind_param("s", $engId);

    if ($stmt->execute()) {
        // Success — redirect back to board or archive page
        header("Location: /pages/dashboard.php?msg=archived");
        exit;
    } else {
        echo "Failed to archive engagement.";
    }
}
?>
