<?php 
// sessions_start();
require_once '../path.php';
require_once '../includes/functions.php';
require_once '../includes/init.php';
logoutUser($conn);

$userId = $_SESSION['user_id'] ?? null;

$showBiometricButton = false;

if ($userId) {
    // Check if user already has registered credentials
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM webauthn_credentials WHERE user_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ((int)$res['cnt'] === 0) {
        $showBiometricButton = true; // no credentials → show button
    }
}






// ============================
// FUNCTION TO DETERMINE MILESTONES
// ============================
function getMilestones($audit_type) {
    $audit_type = strtolower($audit_type); // case-insensitive
    $milestones = [];

    // Both SOC 1 and SOC 2 together
    if (strpos($audit_type, 'soc 1') !== false && strpos($audit_type, 'soc 2') !== false) {
        $milestones = [
            'internal_planning_call',
            'planning_memo_soc_1',
            'planning_memo_soc_2',
            'irl_due_date',
            'client_planning_call',
            'section_3_requested',
            'fieldwork',
            'leadsheet_due_soc_1',
            'leadsheet_due_soc_2',
            'conclusion_memo_soc_1',
            'conclusion_memo_soc_2',
            'draft_report_due_soc_1',
            'draft_report_due_soc_2',
            'final_report_due_soc_1',
            'final_report_due_soc_2',
            'archive_date'
        ];
    }
    // SOC 1 or SOC 2 individually
    else if (strpos($audit_type, 'soc 1') !== false || strpos($audit_type, 'soc 2') !== false) {
        $milestones = [
            'internal_planning_call',
            'planning_memo',
            'irl_due_date',
            'client_planning_call',
            'section_3_requested',
            'fieldwork',
            'leadsheet_due',
            'conclusion_memo',
            'draft_report_due',
            'final_report_due',
            'archive_date'
        ];
    }

    return $milestones;
}

// ============================
// HANDLE ADD ENGAGEMENT
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'add') {

    /* ============================
       TEXT FIELDS
    ============================ */
    $engId       = $_POST['eng_idno'] ?? '';
    $name        = $_POST['eng_name'] ?? '';
    $poc         = $_POST['eng_poc'] ?? '';
    $location    = $_POST['eng_location'] ?? '';
    $audit_type  = $_POST['eng_audit_type'] ?? '';
    $scope       = $_POST['eng_scope'] ?? '';
    $tsc         = $_POST['eng_tsc'] ?? '';
    $notes       = $_POST['eng_notes'] ?? '';
    $status      = $_POST['eng_status'] ?? '';

    /* ============================
       Y / N FIELDS
    ============================ */
    $repeat              = $_POST['eng_repeat'] ?? 'N';

    /* ============================
       DATE FIELDS (EMPTY → NULL)
    ============================ */
    function nullDate($key) {
        return !empty($_POST[$key]) ? $_POST[$key] : null;
    }

    $start_period       = nullDate('eng_start_period');
    $end_period         = nullDate('eng_end_period');
    $as_of_date         = nullDate('eng_as_of_date');
    $archive            = nullDate('eng_archive');
    $last_communication = nullDate('eng_last_communication');

    /* ============================
       INSERT ENGAGEMENT
    ============================ */
    $stmt = $conn->prepare("
        INSERT INTO engagements (
            eng_idno,
            eng_name,
            eng_poc,
            eng_location,
            eng_repeat,
            eng_audit_type,
            eng_scope,
            eng_tsc,
            eng_start_period,
            eng_end_period,
            eng_as_of_date,
            eng_archive,
            eng_last_communication,
            eng_notes,
            eng_status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssssssssssssss",
        $engId,
        $name,
        $poc,
        $location,
        $repeat,
        $audit_type,
        $scope,
        $tsc,
        $start_period,
        $end_period,
        $as_of_date,
        $archive,
        $last_communication,
        $notes,
        $status
    );

    if ($stmt->execute()) {

        // Get the inserted engagement ID
        $engIdInserted = $conn->insert_id;

        // Determine milestones for this engagement
        $milestones = getMilestones($audit_type);

        if (!empty($milestones)) {
            $stmtMilestone = $conn->prepare("
                INSERT INTO engagement_milestones (eng_id, milestone_type) VALUES (?, ?)
            ");

            foreach ($milestones as $ms) {
                $stmtMilestone->bind_param("is", $engIdInserted, $ms);
                $stmtMilestone->execute();
            }
        }

        // Redirect AFTER milestones are inserted
        header("Location: dashboard.php?message=Engagement added successfully");
        exit;

    } else {
        echo "<div class='alert alert-danger'>
            Failed to add engagement: " . htmlspecialchars($stmt->error) . "
        </div>";
    }
}




$nextEngId = getNextEngagementId($conn);

$engagements = getAllEngagements($conn);
$totalEngagements = count($engagements);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kanban - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="../assets/styles/main.css?v=<?php echo time(); ?>">

</head>
<body>

  <!-- Header -->
<div class="header-container">
  <div class="header-inner">
    <div>
      <div class="header-title">Engagement Tracker</div>
      <div class="header-subtitle">Manage your audit engagements</div>
    </div>

    <div class="header-actions d-flex align-items-center">
      <div class="view-options p-1" style="background-color: rgb(241,242,245); border-radius: 10px;" role="tablist">
        <a class="btn btn-sm me-2 tab-btn active" href="dashboard.php">
          <i class="bi bi-grid"></i> Board
        </a>
        <a class="btn btn-sm me-2 tab-btn" href="engagement-list.php">
          <i class="bi bi-list-ul"></i> List
        </a>
        <a class="btn btn-sm me-2 tab-btn" href="engagement-timeline.php">
          <i class="bi bi-calendar2"></i> Timeline
        </a>
        <a class="btn btn-sm tab-btn" href="engagement-analytics.php">
          <i class="bi bi-graph-up"></i> Analytics
        </a>
      </div>

      <a class="btn archive-btn btn-sm ms-3" href="archive.php"><i class="bi bi-archive"></i>&nbsp;&nbsp;Archive</a>
      <a class="btn tools-btn btn-sm ms-3" href="tools.php"><i class="bi bi-tools"></i>&nbsp;&nbsp;Tools</a>

      <?php if ($showBiometricButton): ?>
    <button id="enableBiometricBtn" class="btn btn-success btn-sm ms-3">
      <i class="bi bi-fingerprint"></i>&nbsp;&nbsp;Enable Face ID / Touch ID
    </button>
<?php endif; ?>

      <form method="POST" action="<?php echo BASE_URL . '/auth/logout.php'; ?>" class="d-inline">
          <button type="submit" class="btn logout-btn btn-sm ms-3">
              <i class="bi bi-box-arrow-left"></i>&nbsp;&nbsp;Logout
          </button>
      </form>
    </div>
  </div>
</div>
<!-- Header -->

<script>
// Helper: convert base64url string to ArrayBuffer
function bufferDecode(value) {
    return Uint8Array.from(
        atob(value.replace(/-/g, '+').replace(/_/g, '/')),
        c => c.charCodeAt(0)
    );
}

document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('enableBiometricBtn');
    if (!btn) return; // button doesn't exist → exit

    btn.addEventListener('click', async () => {
        try {
            // 1️⃣ Get registration options from server
            const optionsRes = await fetch('../webauthn/register.php'); // adjust path if needed
            const options = await optionsRes.json();

            if (options.error) {
                alert(options.error);
                return;
            }

            // 2️⃣ Convert challenge and user.id to ArrayBuffer
            options.challenge = bufferDecode(options.challenge);
            options.user.id = bufferDecode(options.user.id);

            if (options.excludeCredentials) {
                options.excludeCredentials = options.excludeCredentials.map(cred => ({
                    ...cred,
                    id: bufferDecode(cred.id)
                }));
            }

            // 3️⃣ Create credential
            const credential = await navigator.credentials.create({ publicKey: options });

            // 4️⃣ Send credential to server
            const res = await fetch('../webauthn/register.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(credential)
            });

            const result = await res.json();

            if (result.success) {
                alert('Biometric login enabled! You can now tap your account to login.');
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-check-circle"></i>&nbsp;&nbsp;Biometric Enabled';
            } else {
                alert('Failed to enable biometrics.');
            }
        } catch (err) {
            console.error(err);
            alert('Biometric registration failed or is not supported on this device.');
        }
    });
});
</script>

  

  <div class="mt-4"></div>

    <?php include_once '../includes/status_cards.php'; ?>
    <?php include_once '../includes/search_bar.php'; ?>

    
  
    <!-- Board sections -->
  
        <!-- Row 1 -->
            <div class="row align-items-center" style="margin-top: 20px; margin-left: 210px; margin-right: 210px;">

                <div class="card" style="border-radius: 15px; border: 2px solid rgb(208,213,219); background-color: rgb(247,248,250) !important;">
                    <div class="card-body">

                        <!-- Header badge -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(105,114,129) !important;">
                            On Hold
                            <?php
                            $engagements = getAllEngagements($conn);
                            $onHoldCount = count(array_filter($engagements, fn($eng) => $eng['eng_status'] === 'on-hold'));
                            ?>
                            <span class="badge rounded-pill ms-2"
                                  style="color: white !important; background-color: rgb(149,156,166) !important;">
                                <?php echo $onHoldCount; ?>
                            </span>
                        </span>

                        <!-- ✅ DROP ZONE -->
                        <div class="kanban-column" data-status="on-hold">

                            <?php
                            $onHoldEngagements = array_filter($engagements, fn($eng) => $eng['eng_status'] === 'on-hold');

                            if (count($onHoldEngagements) > 0):
                                foreach ($onHoldEngagements as $eng):
                                
                                    // Final due date formatting
                                    $finalDueDateRaw = $eng['eng_final_due'] ?? null;
                                    $finalDueDate = $finalDueDateRaw
                                        ? date('M d, Y', strtotime($finalDueDateRaw))
                                        : '';
                                
                                    $today = new DateTime('today');
                                    $dueDate = $finalDueDateRaw ? new DateTime($finalDueDateRaw) : null;
                                
                                    // Red if overdue
                                    $dateColor = ($dueDate && $dueDate < $today)
                                        ? 'rgb(243,36,57)'
                                        : 'rgb(106,115,130)';
                            ?>

                            <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_id']); ?>"
                               class="text-decoration-none text-reset d-block">
                                
                                <!-- ✅ DRAGGABLE CARD -->
                                <div class="card engagement-card-kanban mb-2"
                                     draggable="true"
                                     data-id="<?php echo $eng['eng_id']; ?>"
                                     data-name="<?php echo htmlspecialchars($eng['eng_name'] ?? ''); ?>"
                                     data-engno="<?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?>"
                                     data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                                     data-audit="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? ''); ?>"
                                     data-final-due="<?php echo htmlspecialchars($finalDueDateRaw ?? ''); ?>"
                                     style="border-radius: 15px; border: 1px solid rgb(208,213,219); cursor: move;">
                                
                                    <div class="card-body d-flex align-items-center justify-content-between">
                                
                                        <!-- LEFT -->
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="bi bi-grip-horizontal text-secondary"></i>
                                            <div>
                                                <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">
                                                    <?php echo htmlspecialchars($eng['eng_name']); ?>
                                                </h5>
                                                <span class="text-muted" style="font-size: 14px;">
                                                    <?php echo htmlspecialchars($eng['eng_idno']); ?>
                                                </span>
                                            </div>
                                        </div>
                                
                                        <!-- RIGHT -->
                                        <div class="d-flex align-items-center gap-3 text-secondary">
                                
                                            <!-- ✅ Manager -->
                                            <?php if (!empty($eng['eng_manager'])): ?>
                                                <span style="font-size: 14px;">
                                                    <i class="bi bi-people"></i>&nbsp;
                                                    <?php echo htmlspecialchars($eng['eng_manager']); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- ✅ Final Due Date -->
                                            <?php if (!empty($finalDueDate)): ?>
                                                <span style="font-size: 14px; color: <?php echo $dateColor; ?>;">
                                                    <i class="bi bi-calendar2"></i>&nbsp;
                                                    <?php echo htmlspecialchars($finalDueDate); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Audit Type -->
                                            <?php if (!empty($eng['eng_audit_type'])): ?>
                                                <span class="badge"
                                                      style="background-color: rgba(235,236,237,1); color: rgb(57,69,85); font-weight: 500;">
                                                    <?php
                                                    echo htmlspecialchars(
                                                        preg_replace('/\s*,\s*/', ', ', $eng['eng_audit_type'])
                                                    );
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Overdue badge -->
                                            <?php if ($dueDate && $dueDate < $today): ?>
                                                <span class="badge"
                                                      style="background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight: 500;">
                                                    Overdue
                                                </span>
                                            <?php endif; ?>
                                            
                                        </div>
                                            
                                    </div>
                                </div>
                            </a>
                                            
                            <?php endforeach; else: ?>
                            
                                <!-- Empty state -->
                                <div class="text-center text-muted py-4 empty-placeholder"
                                     style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                            
                            <?php endif; ?>
                            
                        </div><!-- end kanban-column -->
                            
                    </div>
                </div>
            </div>

        <!-- end row 1 -->

        <!-- Row 2 -->
            <div class="row align-items-start g-4" style="margin-top: 1px; margin-top: px; margin-left: 200px; margin-right: 200px;">
  
            <!-- Column 1 -->
                <div class="col-md-4">

                    <div class="card"
                         style="border-radius: 15px; border: 2px solid rgb(154,196,254); background-color: rgb(233,241,254) !important;">
                        <div class="card-body">

                            <!-- Header badge -->
                            <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                                  style="font-size: 15px; padding: 10px 14px; background-color: rgb(68,125,252) !important;">
                                Planning
                                <?php
                                $engagements = getAllEngagements($conn);
                                $planningCount = count(array_filter(
                                    $engagements,
                                    fn($eng) => $eng['eng_status'] === 'planning'
                                ));
                                ?>
                                <span class="badge rounded-pill ms-2"
                                      style="color: white !important; background-color: rgb(123,162,249) !important;">
                                    <?php echo $planningCount; ?>
                                </span>
                            </span>

                            <!-- DROP ZONE -->
                            <div class="kanban-column" data-status="planning">

                                <?php
                                $planningEngagements = array_filter(
                                    $engagements,
                                    fn($eng) => $eng['eng_status'] === 'planning'
                                );

                                if (!empty($planningEngagements)):
                                    foreach ($planningEngagements as $eng):
                                    
                                        // Final Due formatting
                                        $finalDueRaw  = $eng['eng_final_due'] ?? null;
                                        $finalDueDate = $finalDueRaw
                                            ? date('M d, Y', strtotime($finalDueRaw))
                                            : 'No Final Due';
                                    
                                        // Overdue logic
                                        $today     = new DateTime('today');
                                        $dueDate   = $finalDueRaw ? new DateTime($finalDueRaw) : null;
                                        $dateColor = ($dueDate && $dueDate < $today)
                                            ? 'rgb(243,36,57)'
                                            : '#000';
                                ?>

                                <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_id']); ?>"
                                   class="text-decoration-none text-reset d-block">
                                    
                                    <!-- DRAGGABLE CARD -->
                                    <div class="card engagement-card-kanban mb-2"
                                         draggable="true"
                                         data-id="<?php echo $eng['eng_id']; ?>"
                                         data-name="<?php echo htmlspecialchars($eng['eng_name']); ?>"
                                         data-engno="<?php echo htmlspecialchars($eng['eng_idno']); ?>"
                                         data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                                         data-audit="<?php echo htmlspecialchars($eng['eng_audit_type']); ?>"
                                         data-final-due="<?php echo htmlspecialchars($finalDueRaw ?? ''); ?>"
                                         style="background-color: rgb(249,250,251);
                                                border: 1px solid rgb(208,213,219);
                                                border-radius: 15px;
                                                cursor: move;">

                                        <div class="card-body" style="margin-bottom: -15px !important;">
                                    
                                            <!-- Title -->
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="card-title fw-bold mb-0">
                                                    <?php echo htmlspecialchars($eng['eng_name']); ?>
                                                </h6>
                                                <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                                            </div>
                                    
                                            <!-- Details -->
                                            <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                                    
                                                <span style="color: rgb(106,115,130); font-size: 14px;">
                                                    <?php echo htmlspecialchars($eng['eng_idno']); ?>
                                                </span><br>
                                    
                                                <div class="pb-2"></div>
                                    
                                                <!-- Manager -->
                                                <span style="font-size: 14px;">
                                                    <i class="bi bi-people"></i>&nbsp;
                                                    <?php echo htmlspecialchars($eng['eng_manager'] ?? 'Unassigned'); ?>
                                                </span><br>
                                    
                                                <!-- Final Due -->
                                                <span style="font-size: 14px; color: <?php echo $dateColor; ?>;">
                                                    <i class="bi bi-calendar2"></i>&nbsp;
                                                    <?php echo htmlspecialchars($finalDueDate); ?>
                                                </span><br>
                                    
                                                <!-- Tags -->
                                                <div class="tags pt-2">
                                                    <?php if (!empty($eng['eng_audit_type'])): ?>
                                                        <span class="badge"
                                                              style="background-color: rgba(235,236,237,1);
                                                                     color: rgb(57,69,85);
                                                                     font-weight: 500;">
                                                            <?php
                                                            echo htmlspecialchars(
                                                                preg_replace('/\s*,\s*/', ', ', $eng['eng_audit_type'])
                                                            );
                                                            ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($dueDate && $dueDate < $today): ?>
                                                        <span class="badge"
                                                              style="background-color: rgb(255,226,226);
                                                                     color: rgb(201,0,18);
                                                                     font-weight: 500;">
                                                            Overdue
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                    
                                            </p>
                                                    
                                        </div>
                                    </div>
                                </a>
                                                    
                                <?php
                                    endforeach;
                                else:
                                ?>

                                <!-- Empty state -->
                                <div class="text-center text-muted py-4 empty-placeholder"
                                     style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                                
                                <?php endif; ?>
                                
                            </div><!-- end kanban-column -->
                                
                        </div>
                    </div>
                                
                </div>

            <!-- end col 1 -->

            <!-- Column 2 -->
                <div class="col-md-4">

                    <div class="card"
                         style="border-radius: 15px; border: 2px solid rgb(247,187,118); background-color: rgb(253,244,229) !important;">
                        <div class="card-body">
                                                
                            <!-- Header badge -->
                            <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                                  style="font-size: 15px; padding: 10px 14px; background-color: rgb(241,115,19) !important;">
                                In Progress
                                <?php
                                $engagements = getAllEngagements($conn);
                                $inProgressCount = count(array_filter(
                                    $engagements,
                                    fn($eng) => $eng['eng_status'] === 'in-progress'
                                ));
                                ?>
                                <span class="badge rounded-pill ms-2"
                                      style="color: white !important; background-color: rgb(243,155,89) !important;">
                                    <?php echo $inProgressCount; ?>
                                </span>
                            </span>
                                                
                            <!-- DROP ZONE -->
                            <div class="kanban-column" data-status="in-progress">
                                                
                                <?php
                                $inProgressEngagements = array_filter(
                                    $engagements,
                                    fn($eng) => $eng['eng_status'] === 'in-progress'
                                );
                                                
                                if (!empty($inProgressEngagements)):
                                    foreach ($inProgressEngagements as $eng):
                                    
                                        // Final Due formatting
                                        $finalDueRaw  = $eng['eng_final_due'] ?? null;
                                        $finalDueDate = $finalDueRaw
                                            ? date('M d, Y', strtotime($finalDueRaw))
                                            : 'No Final Due';
                                    
                                        // Overdue logic
                                        $today    = new DateTime('today');
                                        $dueDate  = $finalDueRaw ? new DateTime($finalDueRaw) : null;
                                        $dateColor = ($dueDate && $dueDate < $today)
                                            ? 'rgb(243,36,57)'
                                            : '#000';
                                ?>
                
                                <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_id']); ?>"
                                   class="text-decoration-none text-reset d-block">
                                    
                                    <!-- DRAGGABLE CARD -->
                                    <div class="card engagement-card-kanban mb-2"
                                         draggable="true"
                                         data-id="<?php echo $eng['eng_id']; ?>"
                                         data-name="<?php echo htmlspecialchars($eng['eng_name']); ?>"
                                         data-engno="<?php echo htmlspecialchars($eng['eng_idno']); ?>"
                                         data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                                         data-audit="<?php echo htmlspecialchars($eng['eng_audit_type']); ?>"
                                         data-final-due="<?php echo htmlspecialchars($finalDueRaw ?? ''); ?>"
                                         style="background-color: rgb(249,250,251);
                                                border: 1px solid rgb(208,213,219);
                                                border-radius: 15px;
                                                cursor: move;">
                
                                        <div class="card-body" style="margin-bottom: -15px !important;">
                                    
                                            <!-- Title -->
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="card-title fw-bold mb-0">
                                                    <?php echo htmlspecialchars($eng['eng_name']); ?>
                                                </h6>
                                                <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                                            </div>
                                    
                                            <!-- Details -->
                                            <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                                    
                                                <span style="color: rgb(106,115,130); font-size: 14px;">
                                                    <?php echo htmlspecialchars($eng['eng_idno']); ?>
                                                </span><br>
                                    
                                                <div class="pb-2"></div>
                                    
                                                <!-- Manager -->
                                                <span style="font-size: 14px;">
                                                    <i class="bi bi-people"></i>&nbsp;
                                                    <?php echo htmlspecialchars($eng['eng_manager'] ?? 'Unassigned'); ?>
                                                </span><br>
                                    
                                                <!-- Final Due -->
                                                <span style="font-size: 14px; color: <?php echo $dateColor; ?>;">
                                                    <i class="bi bi-calendar2"></i>&nbsp;
                                                    <?php echo htmlspecialchars($finalDueDate); ?>
                                                </span><br>
                                    
                                                <!-- Tags -->
                                                <div class="tags pt-2" style="margin-bottom: -15px !important;">
                                                    <?php if (!empty($eng['eng_audit_type'])): ?>
                                                        <span class="badge"
                                                              style="background-color: rgba(235,236,237,1);
                                                                     color: rgb(57,69,85);
                                                                     font-weight: 500;">
                                                            <?php
                                                            echo htmlspecialchars(
                                                                preg_replace('/\s*,\s*/', ', ', $eng['eng_audit_type'])
                                                            );
                                                            ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($dueDate && $dueDate < $today): ?>
                                                        <span class="badge"
                                                              style="background-color: rgb(255,226,226);
                                                                     color: rgb(201,0,18);
                                                                     font-weight: 500;">
                                                            Overdue
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                    
                                            </p>
                                                    
                                        </div>
                                    </div>
                                </a>
                                                    
                                <?php
                                    endforeach;
                                else:
                                ?>
                
                                <!-- Empty state -->
                                <div class="text-center text-muted py-4 empty-placeholder"
                                     style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                                
                                <?php endif; ?>
                                
                            </div><!-- end kanban-column -->
                                
                        </div>
                    </div>
                </div>

            <!-- end col 2 -->

            <!-- Column 3 -->
                <div class="col-md-4">

                    <div class="card"
                         style="border-radius: 15px; border: 2px solid rgb(212,179,254); background-color: rgb(247,241,254) !important;">
                        <div class="card-body">

                            <!-- Header badge -->
                            <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                                  style="font-size: 15px; padding: 10px 14px; background-color: rgb(160,77,253) !important;">
                                In Review
                                <?php
                                $engagements = getAllEngagements($conn);
                                $inReviewCount = count(array_filter(
                                    $engagements,
                                    fn($eng) => $eng['eng_status'] === 'in-review'
                                ));
                                ?>
                                <span class="badge rounded-pill ms-2"
                                      style="color: white !important; background-color: rgb(188,129,251) !important;">
                                    <?php echo $inReviewCount; ?>
                                </span>
                            </span>

                            <!-- DROP ZONE -->
                            <div class="kanban-column" data-status="in-review">

                                <?php
                                $inReviewEngagements = array_filter(
                                    $engagements,
                                    fn($eng) => $eng['eng_status'] === 'in-review'
                                );

                                if (!empty($inReviewEngagements)):
                                    foreach ($inReviewEngagements as $eng):
                                    
                                        // Final Due formatting
                                        $finalDueRaw  = $eng['eng_final_due'] ?? null;
                                        $finalDueDate = $finalDueRaw
                                            ? date('M d, Y', strtotime($finalDueRaw))
                                            : 'No Final Due';
                                    
                                        // Overdue logic
                                        $today    = new DateTime('today');
                                        $dueDate  = $finalDueRaw ? new DateTime($finalDueRaw) : null;
                                        $dateColor = ($dueDate && $dueDate < $today)
                                            ? 'rgb(243,36,57)'
                                            : '#000';
                                ?>

                                <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_id']); ?>"
                                   class="text-decoration-none text-reset d-block">
                                    
                                    <!-- DRAGGABLE CARD -->
                                    <div class="card engagement-card-kanban mb-2"
                                         draggable="true"
                                         data-id="<?php echo $eng['eng_id']; ?>"
                                         data-name="<?php echo htmlspecialchars($eng['eng_name']); ?>"
                                         data-engno="<?php echo htmlspecialchars($eng['eng_idno']); ?>"
                                         data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                                         data-audit="<?php echo htmlspecialchars($eng['eng_audit_type']); ?>"
                                         data-final-due="<?php echo htmlspecialchars($finalDueRaw ?? ''); ?>"
                                         style="background-color: rgb(249,250,251);
                                                border: 1px solid rgb(208,213,219);
                                                border-radius: 15px;
                                                cursor: move;">

                                        <div class="card-body" style="margin-bottom: -15px !important;">
                                    
                                            <!-- Title -->
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h6 class="card-title fw-bold mb-0">
                                                    <?php echo htmlspecialchars($eng['eng_name']); ?>
                                                </h6>
                                                <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                                            </div>
                                    
                                            <!-- Details -->
                                            <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                                    
                                                <span style="color: rgb(106,115,130); font-size: 14px;">
                                                    <?php echo htmlspecialchars($eng['eng_idno']); ?>
                                                </span><br>
                                    
                                                <div class="pb-2"></div>
                                    
                                                <!-- Manager -->
                                                <span style="font-size: 14px;">
                                                    <i class="bi bi-people"></i>&nbsp;
                                                    <?php echo htmlspecialchars($eng['eng_manager'] ?? 'Unassigned'); ?>
                                                </span><br>
                                    
                                                <!-- Final Due -->
                                                <span style="font-size: 14px; color: <?php echo $dateColor; ?>;">
                                                    <i class="bi bi-calendar2"></i>&nbsp;
                                                    <?php echo htmlspecialchars($finalDueDate); ?>
                                                </span><br>
                                    
                                                <!-- Tags -->
                                                <div class="tags pt-2" style="margin-bottom: -15px !important;">
                                                    <?php if (!empty($eng['eng_audit_type'])): ?>
                                                        <span class="badge"
                                                              style="background-color: rgba(235,236,237,1);
                                                                     color: rgb(57,69,85);
                                                                     font-weight: 500;">
                                                            <?php
                                                            echo htmlspecialchars(
                                                                preg_replace('/\s*,\s*/', ', ', $eng['eng_audit_type'])
                                                            );
                                                            ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($dueDate && $dueDate < $today): ?>
                                                        <span class="badge"
                                                              style="background-color: rgb(255,226,226);
                                                                     color: rgb(201,0,18);
                                                                     font-weight: 500;">
                                                            Overdue
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                    
                                            </p>
                                                    
                                        </div>
                                    </div>
                                </a>
                                                    
                                <?php
                                    endforeach;
                                else:
                                ?>

                                <!-- Empty state -->
                                <div class="text-center text-muted py-4 empty-placeholder"
                                     style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                                
                                <?php endif; ?>
                                
                            </div><!-- end kanban-column -->
                                
                        </div>
                    </div>
                </div>

            <!-- end col 3 -->

            </div>
        <!-- end row 2 -->
  
        <!-- Row 3 -->
            <div class="row align-items-center" style="margin-top: 20px; margin-left: 210px; margin-right: 210px;">

                <div class="card" style="border-radius: 15px; border: 2px solid rgb(153,239,174); background-color: rgb(239,252,242) !important;">
                    <div class="card-body">

                        <!-- Header badge -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(79,198,95) !important;">
                            Complete
                            <?php
                            $engagements = getAllEngagements($conn);
                            $completeCount = count(array_filter($engagements, fn($eng) => $eng['eng_status'] === 'complete'));
                            ?>
                            <span class="badge rounded-pill ms-2"
                                  style="color: white !important; background-color: rgb(130,212,141) !important;">
                                <?php echo $completeCount; ?>
                            </span>
                        </span>

                        <!-- ✅ DROP ZONE -->
                        <div class="kanban-column" data-status="complete">

                            <?php
                            $completeEngagements = array_filter($engagements, fn($eng) => $eng['eng_status'] === 'complete');

                            if (count($completeEngagements) > 0):
                                foreach ($completeEngagements as $eng):
                                
                                    // Final due date formatting
                                    $finalDueDateRaw = $eng['eng_final_due'] ?? null;
                                    $finalDueDate = $finalDueDateRaw
                                        ? date('M d, Y', strtotime($finalDueDateRaw))
                                        : '';
                                
                                    $today = new DateTime('today');
                                    $dueDate = $finalDueDateRaw ? new DateTime($finalDueDateRaw) : null;
                                
                                    // Red if overdue
                                    $dateColor = ($dueDate && $dueDate < $today)
                                        ? 'rgb(243,36,57)'
                                        : 'rgb(106,115,130)';
                            ?>

                            <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_id']); ?>"
                               class="text-decoration-none text-reset d-block">
                                
                                <!-- ✅ DRAGGABLE CARD -->
                                <div class="card engagement-card-kanban mb-2"
                                     draggable="true"
                                     data-id="<?php echo $eng['eng_id']; ?>"
                                     data-name="<?php echo htmlspecialchars($eng['eng_name'] ?? ''); ?>"
                                     data-engno="<?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?>"
                                     data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                                     data-audit="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? ''); ?>"
                                     data-final-due="<?php echo htmlspecialchars($finalDueDateRaw ?? ''); ?>"
                                     style="border-radius: 15px; border: 1px solid rgb(208,213,219); cursor: move;">
                                
                                    <div class="card-body d-flex align-items-center justify-content-between">
                                
                                        <!-- LEFT -->
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="bi bi-grip-horizontal text-secondary"></i>
                                            <div>
                                                <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">
                                                    <?php echo htmlspecialchars($eng['eng_name']); ?>
                                                </h5>
                                                <span class="text-muted" style="font-size: 14px;">
                                                    <?php echo htmlspecialchars($eng['eng_idno']); ?>
                                                </span>
                                            </div>
                                        </div>
                                
                                        <!-- RIGHT -->
                                        <div class="d-flex align-items-center gap-3 text-secondary">
                                
                                            <!-- ✅ Manager -->
                                            <?php if (!empty($eng['eng_manager'])): ?>
                                                <span style="font-size: 14px;">
                                                    <i class="bi bi-people"></i>&nbsp;
                                                    <?php echo htmlspecialchars($eng['eng_manager']); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- ✅ Final Due Date -->
                                            <?php if (!empty($finalDueDate)): ?>
                                                <span style="font-size: 14px; color: <?php echo $dateColor; ?>;">
                                                    <i class="bi bi-calendar2"></i>&nbsp;
                                                    <?php echo htmlspecialchars($finalDueDate); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Audit Type -->
                                            <?php if (!empty($eng['eng_audit_type'])): ?>
                                                <span class="badge"
                                                      style="background-color: rgba(235,236,237,1); color: rgb(57,69,85); font-weight: 500;">
                                                    <?php
                                                    echo htmlspecialchars(
                                                        preg_replace('/\s*,\s*/', ', ', $eng['eng_audit_type'])
                                                    );
                                                    ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <!-- Overdue badge -->
                                            <?php if ($dueDate && $dueDate < $today): ?>
                                                <span class="badge"
                                                      style="background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight: 500;">
                                                    Overdue
                                                </span>
                                            <?php endif; ?>
                                            
                                        </div>
                                            
                                    </div>
                                </div>
                            </a>
                                            
                            <?php endforeach; else: ?>
                            
                                <!-- Empty state -->
                                <div class="text-center text-muted py-4 empty-placeholder"
                                     style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                            
                            <?php endif; ?>
                            
                        </div><!-- end kanban-column -->
                            
                    </div>
                </div>
            </div>

        <!-- end row 3 -->

    <!-- end board sections -->


    <div class="mt-5"></div>

    <?php include_once '../includes/modals/adding_engagement_modal.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        let draggedWrapper = null;

        const layoutMap = {
            'on-hold': 'horizontal',
            'planning': 'vertical',
            'in-progress': 'vertical',
            'in-review': 'vertical',
            'complete': 'horizontal'
        };

        // Update badge count and empty-placeholder
        const updateColumnUI = (column) => {
            const cards = column.querySelectorAll('a > .engagement-card-kanban');
            const count = cards.length;

            // Update badge
            const badge = column.closest('.card')?.querySelector('.count-badge');
            if (badge) badge.textContent = count;

            // Show/hide empty placeholder
            const placeholder = column.querySelector('.empty-placeholder');
            if (placeholder) placeholder.style.display = count === 0 ? 'block' : 'none';
        };

        // Apply layout styles
        const applyLayoutStyles = (card, status) => {
            const body = card.querySelector('.card-body');
            if (!body) return;

            if (status === 'on-hold' || status === 'complete') {
                card.style.width = 'auto';
                body.classList.add('d-flex', 'align-items-center', 'justify-content-between');
                body.style.marginBottom = '';
            } else {
                card.style.width = '100%';
                body.classList.remove('d-flex', 'align-items-center', 'justify-content-between');
                body.style.marginBottom = status === 'planning' ? '-15px' : '';
            }
        };

        // Attach drag events to a card wrapper
        const attachDragEvents = (wrapper) => {
            wrapper.setAttribute('draggable', 'true');

            wrapper.addEventListener('dragstart', e => {
                draggedWrapper = wrapper;
                wrapper.querySelector('.engagement-card-kanban')?.classList.add('dragging');
                e.dataTransfer.effectAllowed = 'move';
            });

            wrapper.addEventListener('dragend', () => {
                if (draggedWrapper) draggedWrapper.querySelector('.engagement-card-kanban')?.classList.remove('dragging');
                draggedWrapper = null;
            });
        };

        // Transform card to horizontal layout
        const transformToHorizontal = (card) => {
            const { id, name, engno, manager, audit, finalDue } = card.dataset;
            const wrapper = document.createElement('a');
            wrapper.href = `engagement-details.php?eng_id=${encodeURIComponent(id)}`;
            wrapper.className = 'text-decoration-none text-reset d-block';
            wrapper.setAttribute('draggable', 'true');

            const newCard = document.createElement('div');
            newCard.className = 'card engagement-card-kanban mb-2';
            Object.assign(newCard.dataset, { id, name, engno, manager, audit, finalDue });
            newCard.style.cssText = 'border-radius:15px; border:1px solid rgb(208,213,219); cursor:move;';

            const body = document.createElement('div');
            body.className = 'card-body d-flex align-items-center justify-content-between';

            // Determine date color
            let dateColor = 'rgb(106,115,130)';
            if (finalDue && new Date(finalDue) < new Date()) dateColor = 'rgb(243,36,57)';

            body.innerHTML = `
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-grip-horizontal text-secondary"></i>
                    <div>
                        <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">${name}</h5>
                        <span class="text-muted" style="font-size: 14px;">${engno}</span>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3 text-secondary">
                    ${manager ? `<span style="font-size:14px;"><i class="bi bi-people"></i>&nbsp;${manager}</span>` : ''}
                    ${finalDue ? `<span style="font-size:14px; color:${dateColor};"><i class="bi bi-calendar2"></i>&nbsp;${finalDue}</span>` : ''}
                </div>
            `;

            const rightDiv = body.querySelector('div:last-child');
            if (audit) {
                const badge = document.createElement('span');
                badge.className = 'badge';
                badge.style.cssText = 'background-color: rgba(235,236,237,1); color: rgb(57,69,85); font-weight: 500;';
                badge.textContent = audit;
                rightDiv.appendChild(badge);
            }

            if (finalDue && new Date(finalDue) < new Date()) {
                const overdue = document.createElement('span');
                overdue.className = 'badge';
                overdue.style.cssText = 'background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight: 500; margin-left:5px;';
                overdue.textContent = 'Overdue';
                rightDiv.appendChild(overdue);
            }

            newCard.appendChild(body);
            wrapper.appendChild(newCard);
            attachDragEvents(wrapper);
            return wrapper;
        };

        // Transform card to vertical layout
        const transformToVertical = (card) => {
            const { id, name, engno, manager, audit, finalDue } = card.dataset;
            const wrapper = document.createElement('a');
            wrapper.href = `engagement-details.php?eng_id=${encodeURIComponent(id)}`;
            wrapper.className = 'text-decoration-none text-reset d-block';
            wrapper.setAttribute('draggable', 'true');

            const newCard = document.createElement('div');
            newCard.className = 'card engagement-card-kanban mb-2';
            Object.assign(newCard.dataset, { id, name, engno, manager, audit, finalDue });
            newCard.style.cssText = 'background-color: rgb(249,250,251); border:1px solid rgb(208,213,219); border-radius:15px; cursor:move;';

            const body = document.createElement('div');
            body.className = 'card-body';
            body.style.marginBottom = '-15px';

            // Determine date color
            let dateColor = '#000';
            if (finalDue && new Date(finalDue) < new Date()) dateColor = 'rgb(243,36,57)';

            body.innerHTML = `
                <div class="d-flex align-items-center justify-content-between">
                    <h6 class="card-title fw-bold mb-0">${name}</h6>
                    <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                </div>
                <p class="text-secondary" style="font-size:16px; margin-bottom:-5px;">
                    <span style="color: rgb(106,115,130); font-size:14px;">${engno}</span><br>
                    <div class="pb-2"></div>
                    ${manager ? `<span style="font-size:14px;"><i class="bi bi-people"></i>&nbsp;${manager}</span><br>` : ''}
                    ${finalDue ? `<span style="font-size:14px; color:${dateColor};"><i class="bi bi-calendar2"></i>&nbsp;${finalDue}</span><br>` : ''}
                    <div class="tags pt-2"></div>
                </p>
            `;

            const tagsDiv = body.querySelector('.tags');
            if (audit) {
                const badge = document.createElement('span');
                badge.className = 'badge';
                badge.style.cssText = 'background-color: rgba(235,236,237,1); color: rgb(57,69,85); font-weight:500;';
                badge.textContent = audit;
                tagsDiv.appendChild(badge);
            }

            if (finalDue && new Date(finalDue) < new Date()) {
                const overdue = document.createElement('span');
                overdue.className = 'badge';
                overdue.style.cssText = 'background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight:500; margin-left:5px;';
                overdue.textContent = 'Overdue';
                tagsDiv.appendChild(overdue);
            }

            newCard.appendChild(body);
            wrapper.appendChild(newCard);
            attachDragEvents(wrapper);
            return wrapper;
        };

        // Attach drag events to all existing cards
        document.querySelectorAll('.kanban-column a').forEach(a => attachDragEvents(a));

        // Drag & drop logic
        document.querySelectorAll('.kanban-column').forEach(column => {
            column.addEventListener('dragover', e => { e.preventDefault(); column.classList.add('drag-over'); });
            column.addEventListener('dragleave', () => column.classList.remove('drag-over'));
            column.addEventListener('drop', e => {
                e.preventDefault();
                column.classList.remove('drag-over');
                if (!draggedWrapper) return;

                const originalParent = draggedWrapper.parentElement;
                const newStatus = column.dataset.status;
                const cardData = draggedWrapper.querySelector('.engagement-card-kanban');

                let newWrapper = (layoutMap[newStatus] === 'horizontal') 
                    ? transformToHorizontal(cardData)
                    : transformToVertical(cardData);

                if (newWrapper !== draggedWrapper) draggedWrapper.remove();
                column.appendChild(newWrapper);
                applyLayoutStyles(newWrapper.querySelector('.engagement-card-kanban'), newStatus);

                // Update badges & placeholders dynamically
                [column, originalParent].forEach(updateColumnUI);

                // Update DB
    const engId = newWrapper.querySelector('.engagement-card-kanban').dataset.id;
    fetch('../includes/update-engagement-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ eng_id: engId, status: newStatus })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            // Revert if update failed
            originalParent.appendChild(newWrapper);
            applyLayoutStyles(newWrapper.querySelector('.engagement-card-kanban'), originalParent.dataset.status);
            [column, originalParent].forEach(updateColumnUI);
            alert('Failed to update DB. Reverted.');
        } else {
            // Refresh the page after successful update
            location.reload();
        }
    })
    .catch(() => {
        originalParent.appendChild(newWrapper);
        applyLayoutStyles(newWrapper.querySelector('.engagement-card-kanban'), originalParent.dataset.status);
        [column, originalParent].forEach(updateColumnUI);
        alert('Failed to update DB. Reverted.');
    });

                draggedWrapper = null;
            });
        });

        // Initialize all badges and placeholders on page load
        document.querySelectorAll('.kanban-column').forEach(column => {
            column.querySelectorAll('a > .engagement-card-kanban')
                  .forEach(card => applyLayoutStyles(card, column.dataset.status));
            updateColumnUI(column);
        });
    });
</script>







</body>
</html>