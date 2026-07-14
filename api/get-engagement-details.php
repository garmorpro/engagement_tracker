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

    echo json_encode([
        'success' => true,
        'engagement' => $engagement,
        'timeline' => $timeline,
        'team' => $team,
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
