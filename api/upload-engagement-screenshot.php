<?php
require_once '../path.php';
require_once '../includes/functions.php';
requireApiAuth();

header('Content-Type: application/json');

$engagementId = trim($_POST['engagement_id'] ?? '');

if ($engagementId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing engagement id']);
    exit;
}

if (!isset($_FILES['screenshot'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['screenshot'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $message = match ($file['error']) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File is too large for this server\'s upload limit.',
        default => 'Upload failed (error code ' . $file['error'] . ').',
    };
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

// 8 MB app-level cap, on top of whatever the server's own php.ini enforces.
$maxBytes = 8 * 1024 * 1024;
if ($file['size'] > $maxBytes) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File is larger than 8 MB.']);
    exit;
}

// Validate it's actually an image (not just a spoofed extension/MIME).
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'That file is not a valid image.']);
    exit;
}

$allowedTypes = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_GIF  => 'gif',
    IMAGETYPE_WEBP => 'webp',
];
$ext = $allowedTypes[$imageInfo[2]] ?? null;
if ($ext === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, or WEBP images are supported.']);
    exit;
}

try {
    $uploadDir = dirname(__DIR__) . '/uploads/engagement-screenshots';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new Exception('Could not create upload directory');
        }
    }

    // Look up the engagement and its current screenshot (if any), so we can
    // delete the old file when replacing rather than leaving it orphaned.
    $stmt = $conn->prepare("SELECT eng_planning_doc FROM engagements WHERE eng_idno = ?");
    $stmt->bind_param('s', $engagementId);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Engagement not found']);
        exit;
    }

    $safeIdno = preg_replace('/[^A-Za-z0-9_-]/', '', $engagementId);
    $filename = $safeIdno . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destPath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        throw new Exception('Failed to save uploaded file');
    }

    $stmt = $conn->prepare("UPDATE engagements SET eng_planning_doc = ? WHERE eng_idno = ?");
    $stmt->bind_param('ss', $filename, $engagementId);
    if (!$stmt->execute()) {
        unlink($destPath);
        throw new Exception($stmt->error);
    }
    $stmt->close();

    // Clean up the previous file now that the new one is safely saved and recorded.
    if (!empty($existing['eng_planning_doc'])) {
        $oldPath = $uploadDir . '/' . basename($existing['eng_planning_doc']);
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Screenshot uploaded successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
