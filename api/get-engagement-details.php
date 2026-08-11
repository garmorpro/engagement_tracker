<?php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$engagementId = trim($_GET['id'] ?? '');

if ($engagementId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing engagement id']);
    exit;
}

try {
    $engagement = null;
    foreach (getAllEngagements($conn) as $eng) {
        if ($eng['eng_idno'] === $engagementId) {
            $engagement = $eng;
            break;
        }
    }

    if (!$engagement) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Engagement not found']);
        exit;
    }

    $timeline = null;
    foreach (getAllTimelineData($conn) as $row) {
        if ($row['engagement_idno'] == $engagementId) {
            $timeline = $row;
            break;
        }
    }

    $team = [];
    foreach (getAllTeamData($conn) as $row) {
        if ($row['engagement_idno'] == $engagementId) {
            $team[] = $row;
        }
    }

    // Other engagements sharing this one's weekly status call, if any —
    // lets the drawer show "Linked with: X, Y" and offer to unlink.
    $linkedCalls = [];
    if (!empty($timeline['weekly_status_call_group'])) {
        $stmt = $conn->prepare("SELECT t.engagement_idno, e.eng_name
                                 FROM engagement_timeline t
                                 JOIN engagements e ON e.eng_idno = t.engagement_idno
                                 WHERE t.weekly_status_call_group = ? AND t.engagement_idno != ?");
        $stmt->bind_param('ss', $timeline['weekly_status_call_group'], $engagementId);
        $stmt->execute();
        $linkedCalls = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'engagement' => $engagement,
        'timeline' => $timeline,
        'team' => $team,
        'linked_calls' => $linkedCalls,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
