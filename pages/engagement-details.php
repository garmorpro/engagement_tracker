<?php
ob_start(); // start output buffering

// sessions_start();
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['archive_eng_id'])) {
    $engId = $_POST['archive_eng_id'];

    // Get current date in Y-m-d format
    $archiveDate = date('Y-m-d');

    // Update status and archive date
    $stmt = $conn->prepare("UPDATE engagements SET eng_status = 'archived', eng_archive = ? WHERE eng_idno = ?");
    $stmt->bind_param("ss", $archiveDate, $engId);

    if ($stmt->execute()) {
        // Redirect before any output
        header("Location: dashboard.php?message=Engagement archived successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Failed to archive engagement.</div>";
    }
}

// Check if eng_idno is in the URL
if (isset($_GET['eng_id'])) {
    $eng_id = $_GET['eng_id'];

    $stmt = $conn->prepare("SELECT * FROM engagements WHERE eng_idno = ?");
    $stmt->bind_param("s", $eng_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $eng = $result->fetch_assoc();
    } else {
        echo "<div class='alert alert-warning'>Engagement not found.</div>";
        exit;
    }

    $stmt->close();
} else {
    echo "<div class='alert alert-danger'>No engagement specified.</div>";
    exit;
}

// Handle editing engagement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['edit_eng_id'])) {

    $engId = $_POST['edit_eng_id'];

    /* ============================
       TEXT FIELDS
    ============================ */
    $name        = $_POST['eng_name'] ?? '';
    $manager     = $_POST['eng_manager'] ?? '';
    $senior      = $_POST['eng_senior'] ?? '';
    $staff       = $_POST['eng_staff'] ?? '';
    $senior_dol  = $_POST['eng_senior_dol'] ?? '';
    $staff_dol   = $_POST['eng_staff_dol'] ?? '';
    $poc         = $_POST['eng_poc'] ?? '';
    $location    = $_POST['eng_location'] ?? '';
    $audit_type  = $_POST['eng_audit_type'] ?? '';
    $scope       = $_POST['eng_scope'] ?? '';
    $tsc         = $_POST['eng_tsc'] ?? '';
    $notes       = $_POST['eng_notes'] ?? '';

    /* ============================
       Y / N FIELDS
    ============================ */
    $repeat                      = $_POST['repeat'] ?? 'N';
    $completed_internal_planning = $_POST['eng_completed_internal_planning'] ?? 'N';
    $irl_sent                    = $_POST['eng_irl_sent'] ?? 'N';
    $completed_client_planning   = $_POST['eng_completed_client_planning'] ?? 'N';
    $fieldwork_complete          = $_POST['eng_fieldwork_complete'] ?? 'N';
    $leadsheet_complete          = $_POST['eng_leadsheet_complete'] ?? 'N';
    $draft_sent                  = $_POST['eng_draft_sent'] ?? 'N';
    $final_sent                  = $_POST['eng_final_sent'] ?? 'N';
    $section_3_requested         = $_POST['eng_section_3_requested'] ?? 'N';

    /* ============================
       DATE FIELDS (EMPTY → NULL)
    ============================ */
    function nullDate($key) {
        return !empty($_POST[$key]) ? $_POST[$key] : null;
    }

    $start_period              = nullDate('eng_start_period');
    $end_period                = nullDate('eng_end_period');
    $as_of_date                = nullDate('eng_as_of_date');
    $internal_planning_call    = nullDate('eng_internal_planning_call');
    $irl_due                   = nullDate('eng_irl_due');
    $client_planning_call      = nullDate('eng_client_planning_call');
    $fieldwork                 = nullDate('eng_fieldwork');
    $leadsheet_due             = nullDate('eng_leadsheet_due');
    $draft_due                 = nullDate('eng_draft_due');
    $final_due                 = nullDate('eng_final_due');
    $archive                   = nullDate('eng_archive');
    $last_communication        = nullDate('eng_last_communication');

    /* ============================
       STATUS (SINGLE SELECT)
    ============================ */
    $status = $_POST['eng_status'] ?? '';

    /* ============================
       PREPARE UPDATE
    ============================ */
    $stmt = $conn->prepare("
        UPDATE engagements SET
            eng_name = ?, 
            eng_manager = ?, 
            eng_senior = ?, 
            eng_staff = ?,
            eng_senior_dol = ?, 
            eng_staff_dol = ?, 
            eng_poc = ?, 
            eng_location = ?,
            eng_repeat = ?, 
            eng_audit_type = ?, 
            eng_scope = ?, 
            eng_tsc = ?,
            eng_start_period = ?, 
            eng_end_period = ?, 
            eng_as_of_date = ?,
            eng_internal_planning_call = ?, 
            eng_completed_internal_planning = ?,
            eng_irl_due = ?, 
            eng_irl_sent = ?, 
            eng_client_planning_call = ?,
            eng_completed_client_planning = ?, 
            eng_fieldwork = ?, 
            eng_fieldwork_complete = ?,
            eng_leadsheet_due = ?, 
            eng_leadsheet_complete = ?, 
            eng_draft_due = ?, 
            eng_draft_sent = ?,
            eng_final_due = ?, 
            eng_final_sent = ?, 
            eng_archive = ?, 
            eng_section_3_requested = ?,
            eng_last_communication = ?, 
            eng_notes = ?, 
            eng_status = ?
        WHERE eng_idno = ?
    ");

    /* ============================
       BIND (35 params + ID)
    ============================ */
    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssssss",
        $name,
        $manager,
        $senior,
        $staff,
        $senior_dol,
        $staff_dol,
        $poc,
        $location,
        $repeat,
        $audit_type,
        $scope,
        $tsc,
        $start_period,
        $end_period,
        $as_of_date,
        $internal_planning_call,
        $completed_internal_planning,
        $irl_due,
        $irl_sent,
        $client_planning_call,
        $completed_client_planning,
        $fieldwork,
        $fieldwork_complete,
        $leadsheet_due,
        $leadsheet_complete,
        $draft_due,
        $draft_sent,
        $final_due,
        $final_sent,
        $archive,
        $section_3_requested,
        $last_communication,
        $notes,
        $status,
        $engId
    );

    /* ============================
       EXECUTE
    ============================ */
    if ($stmt->execute()) {
        header("Location: engagement-details.php?eng_id=" . $engId . "&message=Engagement updated successfully");
        exit;

    } else {
        echo "<div class='alert alert-danger'>
            Failed to update engagement: " . htmlspecialchars($stmt->error) . "
        </div>";
    }
}

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

    <style>
      .modal-dialog-scrollable .modal-body {
    max-height: 70vh; /* keeps modal from exceeding viewport */
    overflow-y: auto;
}
    </style>

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
          <button class="btn new-btn btn-sm ms-3"><i class="bi bi-plus"></i>&nbsp;&nbsp;New Engagement</button>
        </div>
      </div>
    </div>
  <!-- Header -->

  <div class="mt-4"></div>

    <!-- top buttons -->
      <div style="margin-left: 200px; margin-right: 200px;">
        <div class="d-flex justify-content-between align-items-center">
  
          <!-- Left: Back link -->
          <div class="left">
            <a href="dashboard.php" class="back-link text-decoration-none">
              <div class="d-flex align-items-center mb-3 back-link-inner">
                <div class="icon-square me-2 d-flex align-items-center justify-content-center">
                  <i class="bi bi-arrow-left"></i>
                </div>
                <p class="mb-0">Back to Dashboard</p>
              </div>
            </a>
          </div>
  
          <!-- Right: Edit, Delete, (optional Archive) -->
<div class="right d-flex align-items-center gap-2">
    <!-- Edit button -->
    <button class="btn btn-edit me-2" data-bs-toggle="modal" data-bs-target="#editModal-<?php echo $eng['eng_idno']; ?>" title="Edit">
      Edit
    </button>

    <!-- Delete button -->
    <button class="btn btn-delete me-2">
        <i class="bi bi-trash me-1"></i> Delete
    </button>

    <!-- <?php //if ($eng['eng_status'] === 'complete'): ?>
<a href="engagement-details.php?eng_id=<?php //echo urlencode($eng['eng_idno']); ?>"
   class="btn btn-outline-danger btn-archive"
   onclick="return confirm('Are you sure you want to archive this engagement?');">
    <i class="bi bi-archive me-1"></i> Archive
</a>
<?php //endif; ?> -->

<?php if ($eng['eng_status'] === 'complete'): ?>
<form method="post" style="display:inline;">
    <input type="hidden" name="archive_eng_id" value="<?php echo htmlspecialchars($eng['eng_idno']); ?>">
    <button type="submit"
            name="archive_button"
            class="btn btn-outline-danger"
            onclick="return confirm('Are you sure you want to archive this engagement?');">
        <i class="bi bi-archive me-1"></i> Archive
    </button>
</form>
<?php endif; ?>

</div>

  
        </div>
      </div>
    <!-- end top buttons -->

    <!-- php color change for status -->
     <?php
      // Map engagement status to background, border, and pill colors
      $statusColors = [
          'on-hold' => [
              'bg' => 'rgb(249,250,251)',
              'border' => 'rgb(229,231,235)',
              'pill' => 'rgb(105,114,129)',
          ],
          'planning' => [
              'bg' => 'rgb(238,246,254)',
              'border' => 'rgb(187,219,253)',
              'pill' => 'rgb(33,128,255)',
          ],
          'in-progress' => [
              'bg' => 'rgb(255,247,238)',
              'border' => 'rgb(255,214,171)',
              'pill' => 'rgb(255,103,0)',
          ],
          'in-review' => [
              'bg' => 'rgb(251,245,254)',
              'border' => 'rgb(236,213,254)',
              'pill' => 'rgb(181,72,255)',
          ],
          'complete' => [
              'bg' => 'rgb(239,253,245)',
              'border' => 'rgb(176,248,209)',
              'pill' => 'rgb(0,201,92)',
          ],
          'archived' => [
              'bg' => 'rgb(249,250,251)',
              'border' => 'rgb(229,231,235)',
              'pill' => 'rgb(105,114,129)',
          ],
      ];

      // Fallback in case status is unexpected
      $status = $eng['eng_status'];
      $bgColor = $statusColors[$status]['bg'] ?? '#fff';
      $borderColor = $statusColors[$status]['border'] ?? '#ccc';
      $pillColor = $statusColors[$status]['pill'] ?? '#000';
      ?>
    <!-- end php color change for status -->

  <!-- upper bay -->
    <div class="row g-4 mt-1" style="margin-left: 200px; margin-right: 200px;">
      <div class="col-md-12">
        <div class="card h-100"
             style="border-radius: 15px; border: 1px solid <?php echo $borderColor; ?>; background-color: <?php echo $bgColor; ?>;">
          <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-start mb-5 mt-2">

              <!-- Left Side -->
              <div class="d-flex flex-column">

                <!-- Top badges row -->
                <div class="mb-2 d-flex align-items-center flex-wrap gap-2">
                  <!-- Engagement ID -->
                  <span class="badge fw-normal" style="background-color: rgb(252,252,252); border: 1px solid rgb(229,231,235); color: rgb(0,0,0);"><?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?></span>

                  <!-- Status -->
                  <span class="badge rounded-pill" style="background-color: <?php echo $pillColor; ?>; color: white;">
                    <?php 
                      // Replace '-' with ' ' and capitalize each word
                      echo htmlspecialchars(ucwords(str_replace('-', ' ', $eng['eng_status']))); 
                    ?>
                  </span>


                  <!-- Overdue badge -->
                  <?php
                  $today = new DateTime();
                  if (!empty($eng['eng_final_due']) && new DateTime($eng['eng_final_due']) < $today): ?>
                    <span class="badge rounded-pill badge-overdue">Overdue</span>
                  <?php endif; ?>
                  
                  <!-- Repeat Client badge -->
                  <?php if (!empty($eng['eng_repeat']) && $eng['eng_repeat'] === 'Y'): ?>
                    <span class="badge rounded-pill text-white" style="background-color: #2563eb;">Repeat Client</span>
                  <?php endif; ?>
                </div>
                  
                <!-- Engagement Name -->
                <h3 class="fw-bold mb-2"><?php echo htmlspecialchars($eng['eng_name']); ?></h3>
                  
                <!-- Meta row: Audit Type, Location, Period -->
                <div class="d-flex gap-3 text-secondary" style="font-size: 14px;">
                  <?php if(!empty($eng['eng_audit_type'])): ?>
                    <div><i class="bi bi-file-earmark-text me-1"></i><?php echo htmlspecialchars($eng['eng_audit_type']); ?></div>
                  <?php endif; ?>
                  
                  <?php if(!empty($eng['eng_location'])): ?>
                    <div><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($eng['eng_location']); ?></div>
                  <?php endif; ?>
                  
                  <?php if(!empty($eng['eng_period'])): ?>
                    <div><i class="bi bi-calendar2 me-1"></i><?php echo htmlspecialchars($eng['eng_period']); ?></div>
                  <?php endif; ?>
                </div>
                  
              </div>
                  
              <!-- Right Side: Due Box -->
              <div class="d-flex flex-column align-items-center justify-content-center"
                   style="width: 120px; height: 80px; border-radius: 10px; background-color: #fff; text-align: center;">
                  
                <?php
                  if (!empty($eng['eng_final_due'])) {
                      $finalDue = new DateTime($eng['eng_final_due']);
                      $diff = $today->diff($finalDue);
                      $days = (int)$diff->format('%r%a'); // negative if past
                      if ($days < 0) {
                          echo "<div style='font-size: 20px; font-weight: bold; color: #e53e3e;'>" . abs($days) . "</div>";
                          echo "<div style='font-size: 12px; color: #e53e3e;'>Days Overdue</div>";
                      } else {
                          echo "<div style='font-size: 20px; font-weight: bold; color: #000;'>" . $days . "</div>";
                          echo "<div style='font-size: 12px; color: #000;'>Days Until Due</div>";
                      }
                  }
                ?>

              </div>
                
            </div>
                
          </div>
        </div>
      </div>
    </div>


  <!-- end upper bay -->

  <!-- second row details -->
    <div class="row g-4 mt-1" style="margin-left: 200px; margin-right: 200px;">

      <div class="col-md-3">
        <div class="card h-100" style="border-color: rgb(214,232,254); border-radius: 15px; background-color: rgb(226,238,253);">
          <div class="card-body p-4">
            <!-- Header -->
            <div class="d-flex align-items-center mb-3">
              <div class="icon-square me-2" style="background-color: rgb(33,128,255);">
                <i class="bi bi-bullseye" style="color: rgb(255,255,255);"></i>
              </div>
              <h6 class="fw-semibold mb-0" style="color: rgb(0,0,123);">Trust Services Criteria</h6>
            </div>
            <p style="color: rgb(0,0,123);">
              <?php echo htmlspecialchars($eng['eng_tsc'] ?? 'N/A'); ?>
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card h-100" style="border-color: rgb(241,225,254); border-radius: 15px; background-color: rgb(248,239,254);">
          <div class="card-body p-4">
            <!-- Header -->
            <div class="d-flex align-items-center mb-3">
              <div class="icon-square me-2" style="background-color: rgb(181,72,255);">
                <i class="bi bi-calendar2" style="color: rgb(255,255,255);"></i>
              </div>
              <?php
              $periodLabel = '';
                                
              if (!empty($eng['eng_start_period']) && !empty($eng['eng_end_period'])) {
                  $periodLabel = 'Review Period';
              } elseif (!empty($eng['eng_as_of_date'])) {
                  $periodLabel = 'As of Date';
              } else {
                  $periodLabel = 'Period'; // fallback if nothing exists
              }
              ?>
              <h6 class="fw-semibold mb-0" style="color: rgb(89,0,135);">
                <?php echo htmlspecialchars($periodLabel); ?>
              </h6>

            </div>
            <?php
            function formatDate($date) {
                return !empty($date) ? date('M j, Y', strtotime($date)) : 'N/A';
            }

            if (!empty($eng['eng_start_period']) && !empty($eng['eng_end_period'])): ?>
                <p style="color: rgb(89,0,135);">
                    <?php echo htmlspecialchars(formatDate($eng['eng_start_period'])); ?> through <?php echo htmlspecialchars(formatDate($eng['eng_end_period'])); ?>
                </p>
            <?php elseif (!empty($eng['eng_as_of_date'])): ?>
                <p style="color: rgb(89,0,135);">
                    As of <?php echo htmlspecialchars(formatDate($eng['eng_as_of_date'])); ?>
                </p>
            <?php else: ?>
                <p style="color: rgb(89,0,135);">N/A</p>
            <?php endif; ?>

          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card h-100" style="border-color: rgb(203,250,224); border-radius: 15px; background-color: rgb(230,253,239);">
          <div class="card-body p-4">
            <!-- Header -->
            <div class="d-flex align-items-center mb-3">
              <div class="icon-square me-2" style="background-color: rgb(0,201,92);">
                <i class="bi bi-check-circle" style="color: rgb(255,255,255);"></i>
              </div>
              <h6 class="fw-semibold mb-0" style="color: rgb(0,66,0);">Final Due Date</h6>
            </div>
            <p style="color: rgb(0,66,0);">
              <?php echo htmlspecialchars(formatDate($eng['eng_final_due'])); ?>
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card h-100" style="border-color: rgb(255,228,201); border-radius: 15px; background-color: rgb(255,242,227);">
          <div class="card-body p-4">
            <!-- Header -->
            <div class="d-flex align-items-center mb-3">
              <div class="icon-square me-2" style="background-color: rgb(255,103,0);">
                <i class="bi bi-clock" style="color: rgb(255,255,255);"></i>
              </div>
              <h6 class="fw-semibold mb-0" style="color: rgb(98,0,0);">Last Contact</h6>
            </div>
            <p style="color: rgb(98,0,0);">
              <?php echo htmlspecialchars(formatDate($eng['eng_last_communication'])); ?>
            </p>
          </div>
        </div>
      </div>

      



    </div>


  <!-- end second row details -->

  <!-- next section (main) -->
    <div class="row align-items-stretch mt-4" style="margin-left: 200px; margin-right: 200px;">

      <!-- LEFT COLUMN (team) -->
        <div class="col-md-4 d-flex">
          <div class="card w-100" style="border-color: rgb(229,231,235); border-radius: 15px; background-color: rgb(255,255,255);">
            <div class="card-body p-4">
              <!-- Header -->
              <div class="d-flex align-items-center mb-4">
                <div class="icon-square me-2" style="background-color: rgb(222,234,252); height: 40px; width: 40px;">
                  <i class="bi bi-people" style="color: rgb(0,42,241);"></i>
                </div>
                <h6 class="fw-semibold mb-0" style="color: rgb(0,0,0); font-size: 20px !important;">Team</h6>
              </div>

              <div class="card mb-4" style="border-color: rgb(190,215,252); border-radius: 20px; background-color: rgb(230,240,252);  ">
                <div class="card-body p-3">
                  <!-- Header -->
                  <div class="d-flex align-items-center mb-3">
                    <h6 class="mb-0 text-uppercase" style="color: rgb(21,87,242); font-weight: 600 !important; font-size: 12px  !important;">Manager</h6>
                  </div>
                  <h6 class="fw-semibold" style="color: rgb(0,37,132); font-size: 20px;">
                    <?php echo htmlspecialchars($eng['eng_manager'] ?? 'Manager not assigned'); ?>
                  </h6>
                </div>
              </div>

              <div class="card mb-4" style="border-color: rgb(228,209,253); border-radius: 20px; background-color: rgb(242,235,253);  ">
                <div class="card-body p-3">
                  <!-- Header -->
                  <div class="d-flex align-items-center mb-3">
                    <h6 class="mb-0 text-uppercase" style="color: rgb(123,0,240); font-weight: 600 !important; font-size: 12px  !important;">Senior</h6>
                  </div>
                  <h6 class="fw-semibold" style="color: rgb(74,0,133); font-size: 20px;">
                    <?php echo htmlspecialchars($eng['eng_senior'] ?? 'Senior not assigned'); ?>
                  </h6>
                  <p class="pt-2" style="color: rgb(97,0,206); font-size: 12px;">
                    <strong>DOL:</strong> <?php echo htmlspecialchars($eng['eng_senior_dol'] ?? 'DOL not assigned'); ?>
                  </p>
                </div>
              </div>

              <div class="card mb-4" style="border-color: rgb(198,246,210); border-radius: 20px; background-color: rgb(234,252,239);  ">
                <div class="card-body p-3">
                  <!-- Header -->
                  <div class="d-flex align-items-center mb-3">
                    <h6 class="mb-0 text-uppercase" style="color: rgb(69,166,81); font-weight: 600 !important; font-size: 12px  !important;">Staff</h6>
                  </div>
                  <h6 class="fw-semibold" style="color: rgb(0,42,0); font-size: 20px;">
                    <?php echo htmlspecialchars($eng['eng_staff'] ?? 'Staff not assigned'); ?>
                  </h6>
                  <p class="pt-2" style="color: rgb(0,142,0); font-size: 12px;">
                    <strong>DOL:</strong> <?php echo htmlspecialchars($eng['eng_staff_dol'] ?? 'DOL not assigned'); ?>
                  </p>
                </div>
              </div>

            </div>
          </div>
        </div>
      <!-- end LEFT COLUMN (team) -->

      <!-- RIGHT COLUMN -->
        <div class="col-md-8 d-flex">
        <div class="d-flex flex-column w-100">

          <!-- TOP ROW (client information) -->
            <div class="card mb-3" style="border-color: rgb(229,231,235); border-radius: 15px; background-color: rgb(255,255,255);">
              <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                <div class="icon-square me-2" style="background-color: rgb(241,232,253); height: 40px; width: 40px;">
                  <i class="bi bi-buildings" style="color: rgb(139,33,241);"></i>
                </div>
                <h6 class="fw-semibold mb-0" style="color: rgb(0,0, 0); font-size: 20px !important;">Client Information</h6>
              </div>

              <div class="row">
                <div class="col-md-6">
                  <div class="card mb-4" style="border: none; border-radius: 20px; background-color: rgb(249,250,251);">
                <div class="card-body p-3">
                  <!-- Header -->
                  <div class="d-flex align-items-center mb-3">
                    <h6 class="mb-0 text-uppercase" style="color: rgb(87,95,108); font-weight: 600 !important; font-size: 12px  !important;">Point of Contact</h6>
                  </div>
                  <h6 class="fw-semibold" style="color: rgb(0,0,0); font-size: 20px;">
                    <?php echo htmlspecialchars($eng['eng_poc'] ?? 'Point of contact not found'); ?>
                  </h6>
                </div>
              </div>
                </div>
                <div class="col-md-6">
                  <div class="card mb-4" style="border: none; border-radius: 20px; background-color: rgb(249,250,251);">
                <div class="card-body p-3">
                  <!-- Header -->
                  <div class="d-flex align-items-center mb-3">
                    <h6 class="mb-0 text-uppercase" style="color: rgb(87,95,108); font-weight: 600 !important; font-size: 12px  !important;">Location</h6>
                  </div>
                  <h6 class="fw-semibold" style="color: rgb(0,0,0); font-size: 20px;">
                    <?php echo htmlspecialchars($eng['eng_location'] ?? 'Location not found'); ?>
                  </h6>
                </div>
              </div>
                </div>
              </div>

              </div>
            </div>
          <!-- end TOP ROW (client information) -->
          
          <!-- BOTTOM ROW (timeline) -->
            <div class="card flex-grow-1" style="border-color: rgb(229,231,235); border-radius: 15px; background-color: rgb(255,255,  255);   ">
              <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                <div class="icon-square me-2" style="background-color: rgb(252,237,215); height: 40px; width: 40px;">
                  <i class="bi bi-calendar2" style="color: rgb(223,50,0);"></i>
                </div>
                <h6 class="fw-semibold mb-0" style="color: rgb(0,0, 0); font-size: 20px !important;">Engagement Timeline</h6>
              </div>

                <?php
                function timelineDate($dateValue) {
                    $hasDate = !empty($dateValue);

                    return [
                        'color'     => $hasDate ? 'rgb(60,163,74)' : 'rgb(220,53,69)',
                        'textClass' => $hasDate ? 'text-success' : 'text-danger',
                        'text'      => $hasDate
                            ? htmlspecialchars(date('M j, Y', strtotime($dateValue)))
                            : null
                    ];
                }
                ?>

                <?php
                $internalPlanning = timelineDate($eng['eng_internal_planning_call'] ?? null);
                $irlDue           = timelineDate($eng['eng_irl_due'] ?? null);
                $clientPlanning   = timelineDate($eng['eng_client_planning_call'] ?? null);
                $fieldwork        = timelineDate($eng['eng_fieldwork'] ?? null);
                $leadsheetStart   = timelineDate($eng['eng_leadsheet_start'] ?? null);
                $leadsheetDue     = timelineDate($eng['eng_leadsheet_due'] ?? null);
                $draftDue         = timelineDate($eng['eng_draft_due'] ?? null);
                $finalDue         = timelineDate($eng['eng_final_due'] ?? null);
                $archiveDate      = timelineDate($eng['eng_archive'] ?? null);
                ?>

                <div class="timeline position-relative">

                <?php
                function timelineStatus($scheduledDate, $completedFlag) {
                    $isCompleted = ($completedFlag === 'Y');
                    $hasDate     = !empty($scheduledDate);
                            
                    return [
                        'color'     => $isCompleted ? 'rgb(60,163,74)' : 'rgb(220,53,69)',
                        'textClass' => $isCompleted ? 'text-success' : 'text-danger',
                        'text'      => $hasDate
                            ? htmlspecialchars(date('M j, Y', strtotime($scheduledDate)))
                            : null
                    ];
                }
                ?>
                
                <?php
                $internalPlanning = timelineStatus(
                    $eng['eng_internal_planning_call'] ?? null,
                    $eng['eng_completed_internal_planning'] ?? 'N'
                );
                            
                $irlDue = timelineStatus(
                    $eng['eng_irl_due'] ?? null,
                    $eng['eng_irl_sent'] ?? 'N'
                );
                            
                $clientPlanning = timelineStatus(
                    $eng['eng_client_planning_call'] ?? null,
                    $eng['eng_completed_client_planning'] ?? 'N'
                );
                            
                $fieldwork = timelineStatus(
                    $eng['eng_fieldwork'] ?? null,
                    $eng['eng_fieldwork_complete'] ?? 'N'
                );
                            
                $leadsheetDue = timelineStatus(
                    $eng['eng_leadsheet_due'] ?? null,
                    $eng['eng_leadsheet_complete'] ?? 'N'
                );
                            
                $draftDue = timelineStatus(
                    $eng['eng_draft_due'] ?? null,
                    $eng['eng_draft_sent'] ?? 'N'
                );
                            
                $finalDue = timelineStatus(
                    $eng['eng_final_due'] ?? null,
                    $eng['eng_final_sent'] ?? 'N'
                );
                ?>


              <!-- Internal Planning Call -->
                <div class="d-flex align-items-center position-relative">
                  <div class="d-flex flex-column align-items-center me-3 position-relative z-1">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background-color: <?= $internalPlanning['color']; ?>;">
                      <i class="bi bi-telephone"></i>
                    </div>
                  </div>

                  <div class="flex-grow-1">
                    <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f9fafb;">
                      <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Internal Planning Call</span>
                        <span class="fw-semibold <?= $internalPlanning['textClass']; ?>"
                              style="color: <?= $internalPlanning['color']; ?>;">
                          <?= $internalPlanning['text'] ?? 'Internal planning call not found'; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              <!-- end Internal Planning Call -->

              <!-- IRL Due -->
                <div class="d-flex align-items-center position-relative mt-3">
                  <div class="d-flex flex-column align-items-center me-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background-color: <?= $irlDue['color']; ?>;">
                      <i class="bi bi-calendar2-event"></i>
                    </div>
                  </div>

                  <div class="flex-grow-1">
                    <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f9fafb;">
                      <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">IRL Due Date</span>
                        <span class="fw-semibold <?= $irlDue['textClass']; ?>"
                              style="color: <?= $irlDue['color']; ?>;">
                          <?= $irlDue['text'] ?? 'IRL due date not found'; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              <!-- end IRL Due -->

              <!-- Client Planning Call -->
                <div class="d-flex align-items-center position-relative mt-3">
                  <div class="d-flex flex-column align-items-center me-3 position-relative z-1">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background-color: <?= $clientPlanning['color']; ?>;">
                      <i class="bi bi-telephone"></i>
                    </div>
                    <div class="bg-primary" style="width:2px;flex-grow:1;margin-top:6px;"></div>
                  </div>

                  <div class="flex-grow-1">
                    <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f9fafb;">
                      <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Client Planning Call</span>
                        <span class="fw-semibold <?= $clientPlanning['textClass']; ?>"
                              style="color: <?= $clientPlanning['color']; ?>;">
                          <?= $clientPlanning['text'] ?? 'Client planning call not found'; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

              <!-- end Client Planning Call -->

              <?php
              function yesNoStatus($flag, $yesText = 'Completed', $noText = 'Not requested yet') {
                  $isYes = ($flag === 'Y');
                          
                  return [
                      'color'     => $isYes ? 'rgb(60,163,74)' : 'rgb(220,53,69)',
                      'textClass' => $isYes ? 'text-success' : 'text-danger',
                      'text'      => $isYes ? $yesText : $noText
                  ];
              }
              ?>
              
              <?php
              $section3Requested = yesNoStatus(
                  $eng['eng_section_3_requested'] ?? 'N',
                  'Completed',
                  'Not requested yet'
              );
              ?>

              <!-- Section 3 Requested -->
                <div class="d-flex align-items-center position-relative mt-3">
                  <div class="d-flex flex-column align-items-center me-3">

                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background-color: <?= $section3Requested['color']; ?>;">
                      <i class="bi bi-file-earmark-text"></i>
                    </div>

                    <div class="bg-primary" style="width:2px;flex-grow:1;margin-top:6px;"></div>
                  </div>

                  <div class="flex-grow-1">
                    <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f9fafb;">
                      <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">

                        <span class="fw-semibold">Section 3 Requested</span>

                        <span class="fw-semibold <?= $section3Requested['textClass']; ?>"
                              style="color: <?= $section3Requested['color']; ?>;">
                          <?= $section3Requested['text']; ?>
                        </span>

                      </div>
                    </div>
                  </div>
                </div>
              <!-- end Section 3 requested -->

              <!-- Fieldwork -->
                <div class="d-flex align-items-center position-relative mt-3">
                  <div class="d-flex flex-column align-items-center me-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background-color: <?= $fieldwork['color']; ?>;">
                      <i class="bi bi-calendar2-range"></i>
                    </div>
                    <div class="bg-primary" style="width:2px;flex-grow:1;margin-top:6px;"></div>
                  </div>

                  <div class="flex-grow-1">
                    <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f9fafb;">
                      <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Fieldwork</span>
                        <span class="fw-semibold <?= $fieldwork['textClass']; ?>"
                              style="color: <?= $fieldwork['color']; ?>;">
                          <?= $fieldwork['text'] ?? 'Fieldwork date not found'; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              <!-- end Fieldwork -->

              <!-- Leadsheet Due -->
                <div class="d-flex align-items-center position-relative mt-3">
                  <div class="d-flex flex-column align-items-center me-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background-color: <?= $leadsheetDue['color']; ?>;">
                      <i class="bi bi-calendar2-event"></i>
                    </div>
                    <div class="bg-primary" style="width:2px;flex-grow:1;margin-top:6px;"></div>
                  </div>

                  <div class="flex-grow-1">
                    <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f9fafb;">
                      <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Leadsheet Due</span>
                        <span class="fw-semibold <?= $leadsheetDue['textClass']; ?>"
                              style="color: <?= $leadsheetDue['color']; ?>;">
                          <?= $leadsheetDue['text'] ?? 'Leadsheet due date not found'; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              <!-- end Leadsheet Due -->

              <!-- Draft Due -->
                <div class="d-flex align-items-center position-relative mt-3">
                  <div class="d-flex flex-column align-items-center me-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background-color: <?= $draftDue['color']; ?>;">
                      <i class="bi bi-calendar2-event"></i>
                    </div>
                    <div class="bg-primary" style="width:2px;flex-grow:1;margin-top:6px;"></div>
                  </div>

                  <div class="flex-grow-1">
                    <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f9fafb;">
                      <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Draft Report Due</span>
                        <span class="fw-semibold <?= $draftDue['textClass']; ?>"
                              style="color: <?= $draftDue['color']; ?>;">
                          <?= $draftDue['text'] ?? 'Draft report due not found'; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              <!-- end Draft Due -->

              <!-- Final Due -->
                <div class="d-flex align-items-center position-relative mt-3">
                  <div class="d-flex flex-column align-items-center me-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background-color: <?= $finalDue['color']; ?>;">
                      <i class="bi bi-calendar2-event"></i>
                    </div>
                    <div class="bg-primary" style="width:2px;flex-grow:1;margin-top:6px;"></div>
                  </div>                

                  <div class="flex-grow-1">
                    <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f9fafb;">
                      <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Final Report Due</span>
                        <span class="fw-semibold <?= $finalDue['textClass']; ?>"
                              style="color: <?= $finalDue['color']; ?>;">
                          <?= $finalDue['text'] ?? 'Final report due not found'; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

              <!-- end Final Due -->

              <!-- Archive Date -->
                <div class="d-flex align-items-center position-relative mt-3">
                  <div class="d-flex flex-column align-items-center me-3">
                    <div class="rounded-circle text-white d-flex align-items-center justify-content-center"
                         style="width:44px;height:44px;background-color: <?= $archiveDate['color']; ?>;">
                      <i class="bi bi-archive"></i>
                    </div>
                    <div class="bg-primary" style="width:2px;flex-grow:1;margin-top:6px;"></div>
                  </div>
                  <div class="flex-grow-1">
                    <div class="card border-0 shadow-sm" style="border-radius:20px;background:#f9fafb;">
                      <div class="card-body py-3 px-4 d-flex justify-content-between align-items-center">
                        <span class="fw-semibold">Archive Date</span>
                        <span class="fw-semibold <?= $archiveDate['textClass']; ?>" style="color: <?= $archiveDate['color']; ?>;">
                          <?= $archiveDate['text'] ?? 'Not archived yet'; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              <!-- end Archive Date -->

              </div>

              </div>
            </div>
          <!-- END BOTTOM ROW (timeline) -->

        </div>
        </div>
      <!-- end RIGHT columns -->

    </div>
  <!-- end next section (main) -->

  <!-- lower bay -->
    <div class="row g-4 mt-1" style="margin-left: 200px; margin-right: 200px;">
      <div class="col-md-12">
        <div class="card h-100"
             style="border-radius: 15px; border: 1px solid rgb(229,231,235); ?>; background-color: rgb(255,255,255);">
          <div class="card-body p-4">

              <div class="d-flex align-items-center mb-4">
                <div class="icon-square me-2" style="background-color: rgb(241,232,253); height: 40px; width: 40px;">
                  <i class="bi bi-buildings" style="color: rgb(139,33,241);"></i>
                </div>
                <h6 class="fw-semibold mb-0" style="color: rgb(0,0, 0); font-size: 20px !important;">Scope & Notes</h6>
              </div>

              

              <div class="d-flex align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"
                    style="color: rgb(62,73,90); font-weight: 600; font-size: 12px;">
                  Audit Scope
                </h6>
              </div>

              <div class="card mb-4"
                   style="border-color: rgb(182,210,251); border-radius: 20px; background-color: rgb(240,246,254);">
                <div class="card-body d-flex align-items-center">
                  <p class="mb-0" style="font-size:14px;">
                    <?= htmlspecialchars($eng['eng_scope'] ?? 'Scope not set'); ?> - <?= htmlspecialchars($eng['eng_audit_type'] ?? 'Audit type not assigned'); ?>
                  </p>
                </div>
              </div>

              <div class="d-flex align-items-center mb-3">
                <h6 class="mb-0 text-uppercase"
                    style="color: rgb(62,73,90); font-weight: 600; font-size: 12px;">
                  Notes
                </h6>
              </div>
                          
              <div class="card"
                   style="border-color: rgb(230,213,253); border-radius: 20px; background-color: rgb(249,245,254);">
                <div class="card-body d-flex align-items-center">
                  <p class="mb-0" style="font-size:14px;">
                    <?= htmlspecialchars($eng['eng_notes'] ?? 'No notes written yet'); ?>
                  </p>
                </div>
              </div>
                
          </div>
        </div>
      </div>
    </div>


  <!-- end lower bay -->

  <!-- sub lower bay -->
    <div class="row g-4 mt-1" style="margin-left: 200px; margin-right: 200px;">
      <div class="col-md-12">
        <div class="card h-100"
             style="border-radius: 15px; border: 1px solid rgb(235,237,240); background-color: rgb(246,247,249);">
          <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center text-muted" style="font-size: 14px;">

              <?php
              $createdDate = !empty($eng['eng_created'])
                  ? (new DateTime($eng['eng_created']))->format('M j, Y')
                  : '—';

              $updatedDate = !empty($eng['eng_updated'])
                  ? (new DateTime($eng['eng_updated']))->format('M j, Y')
                  : '—';
              ?>

              <!-- Created (left) -->
              <div class="d-flex align-items-center">
                <i class="bi bi-clock me-2"></i>
                <span>
                  Created:
                  <span class="fw-semibold"><?php echo htmlspecialchars($createdDate); ?></span>
                </span>
              </div>

              <!-- Updated (right) -->
              <div class="d-flex align-items-center">
                <i class="bi bi-calendar2 me-2"></i>
                <span>
                  Updated:
                  <span class="fw-semibold"><?php echo htmlspecialchars($updatedDate); ?></span>
                </span>
              </div>

            </div>

          </div>
        </div>
      </div>
    </div>
  <!-- end sub lower bay -->


<!-- Modal for this engagement -->
<div class="modal fade" id="editModal-<?php echo $eng['eng_idno']; ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">

<form method="POST">
<input type="hidden" name="edit_eng_id" value="<?php echo $eng['eng_idno']; ?>">

<div class="modal-header">
  <h5 class="modal-title">Edit Engagement</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<div class="row g-3">

<!-- =====================
     TEXT FIELDS
===================== -->

<h6 class="fw-semibold mt-4">
  Basic Information
</h6>
<hr>

<?php
$checked = (($eng['eng_repeat'] ?? 'N') === 'Y');
?>
<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">
    Engagement ID<sup>*</sup>
  </label>
  <div class="d-flex align-items-center gap-2">

    <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;"
           name="<?php echo $eng['eng_idno'] ?? ''; ?>"
           value="<?php echo $eng['eng_idno'] ?? ''; ?>">

    <div class="yn-toggle <?php echo $checked ? 'active' : ''; ?>"
         onclick="toggleYN(this)">
      <?php echo $checked ? '<i class="bi bi-check"></i> Y' : 'N'; ?>
      <!-- <br><br>  -->
    </div>

    <!-- ALWAYS POST -->
    <input type="hidden" name="repeat" value="<?php echo $checked ? 'Y' : 'N'; ?>">

  </div>
</div>



<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Engagement Name<sup>*</sup></label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_name"
         value="<?php echo htmlspecialchars($eng['eng_name'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Audit Type</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_audit_type"
         value="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? '', ENT_QUOTES); ?>">
</div>

<!-- =====================
     STATUS
===================== -->

<div class="col-12 mb-3 engagement-status-container">
    <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Status</label>
    <div class="d-flex gap-2 flex-wrap">
        <?php
        $statuses = [
            'on-hold' => ['label' => 'On Hold', 'border' => '107,114,129', 'bg' => '249,250,251', 'text' => '56,65,82', 'icon' => 'bi-pause-circle'],
            'planning' => ['label' => 'Planning', 'border' => '68,125,252', 'bg' => '240,246,254', 'text' => '35,70,221', 'icon' => 'bi-clock'],
            'in-progress' => ['label' => 'In Progress', 'border' => '241,115,19', 'bg' => '254,247,238', 'text' => '186,66,13', 'icon' => 'bi-play-circle'],
            'in-review' => ['label' => 'In Review', 'border' => '160,77,253', 'bg' => '249,245,254', 'text' => '119,17,210', 'icon' => 'bi-eye'],
            'complete' => ['label' => 'Completed', 'border' => '79,198,95', 'bg' => '242,253,245', 'text' => '51,128,63', 'icon' => 'bi-check2-circle'],
        ];
        $defaultBorder = '229,231,235';
        $defaultBg = '255,255,255';
        $defaultText = '76,85,100';
        ?>
        <?php foreach ($statuses as $key => $s): 
            $selected = (($eng['eng_status'] ?? '') === $key);
        ?>
        <div class="status-card text-center p-2 flex-fill"
             data-status="<?php echo $key; ?>"
             style="
                cursor: pointer;
                border: 2px solid rgb(<?php echo $selected ? $s['border'] : $defaultBorder; ?>);
                background-color: rgb(<?php echo $selected ? $s['bg'] : $defaultBg; ?>);
                color: rgb(<?php echo $selected ? $s['text'] : $defaultText; ?>);
                font-weight: 500;
                border-radius: 1rem;
             ">
            <i class="bi <?php echo $s['icon']; ?>" style="font-size: 1.1rem;"></i>
            <div style="margin-top: 0.25rem; font-size: 12px;"><?php echo $s['label']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <!-- Use class instead of ID -->
    <input type="hidden" name="eng_status" class="eng_status_input" value="<?php echo htmlspecialchars($eng['eng_status'] ?? ''); ?>">
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Loop over each container separately
    document.querySelectorAll('.engagement-status-container').forEach(container => {
        const cards = container.querySelectorAll('.status-card');
        const hiddenInput = container.querySelector('.eng_status_input');

        const defaultBorder = '229,231,235';
        const defaultBg = '255,255,255';
        const defaultText = '76,85,100';

        const statusColors = {
            'on-hold': {border: '107,114,129', bg: '249,250,251', text: '56,65,82'},
            'planning': {border: '68,125,252', bg: '240,246,254', text: '35,70,221'},
            'in-progress': {border: '241,115,19', bg: '254,247,238', text: '186,66,13'},
            'in-review': {border: '160,77,253', bg: '249,245,254', text: '119,17,210'},
            'complete': {border: '79,198,95', bg: '242,253,245', text: '51,128,63'},
        };

        const applyColors = (card, status, isSelected) => {
            if (isSelected) {
                const colors = statusColors[status];
                card.style.border = `2px solid rgb(${colors.border})`;
                card.style.backgroundColor = `rgb(${colors.bg})`;
                card.style.color = `rgb(${colors.text})`;
            } else {
                card.style.border = `2px solid rgb(${defaultBorder})`;
                card.style.backgroundColor = `rgb(${defaultBg})`;
                card.style.color = `rgb(${defaultText})`;
            }
        };

        cards.forEach(card => {
            const status = card.dataset.status;
            const isSelected = hiddenInput.value === status;
            applyColors(card, status, isSelected);

            card.addEventListener('click', () => {
                hiddenInput.value = status;
                // Reset all cards in this container
                cards.forEach(c => applyColors(c, c.dataset.status, c.dataset.status === status));
            });

            card.addEventListener('mouseenter', () => applyColors(card, status, true));
            card.addEventListener('mouseleave', () => applyColors(card, status, hiddenInput.value === status));
        });
    });
});
</script>


<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">TSC</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_tsc"
         value="<?php echo htmlspecialchars($eng['eng_tsc'] ?? '', ENT_QUOTES); ?>">
</div>

<!-- =====================
     DATE ONLY
===================== -->

<?php
$dateFields = [
  'eng_start_period'       => 'Start Period',
  'eng_end_period'         => 'End Period',
  'eng_as_of_date'         => 'As Of Date'
];
foreach ($dateFields as $field => $label):
?>
<div class="col-md-4">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);"><?php echo $label; ?></label>
  <input type="date" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;"
         name="<?php echo $field; ?>"
         value="<?php echo $eng[$field] ?? ''; ?>">
</div>
<?php endforeach; ?>


<h6 class="fw-semibold mt-5">Team Members</h6>
<hr>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Manager</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_manager"
         value="<?php echo htmlspecialchars($eng['eng_manager'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Senior(s)</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_senior"
         value="<?php echo htmlspecialchars($eng['eng_senior'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Staff</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_staff"
         value="<?php echo htmlspecialchars($eng['eng_staff'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Senior DOL</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_senior_dol"
         value="<?php echo htmlspecialchars($eng['eng_senior_dol'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Staff DOL</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_staff_dol"
         value="<?php echo htmlspecialchars($eng['eng_staff_dol'] ?? '', ENT_QUOTES); ?>">
</div>


<h6 class="fw-semibold mt-5">Client Information</h6>
<hr>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">POC</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_poc"
         value="<?php echo htmlspecialchars($eng['eng_poc'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Location</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_location"
         value="<?php echo htmlspecialchars($eng['eng_location'] ?? '', ENT_QUOTES); ?>">
</div>


<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Scope</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_scope"
         value="<?php echo htmlspecialchars($eng['eng_scope'] ?? '', ENT_QUOTES); ?>">
</div>



<h6 class="fw-semibold mt-5">Important Dates & Milestones</h6>
<hr>

<!-- =====================
     DATE + TOGGLE PAIRS
===================== -->

<?php
$pairs = [
  'eng_internal_planning_call' => 'eng_completed_internal_planning',
  'eng_irl_due'                => 'eng_irl_sent',
  'eng_client_planning_call'   => 'eng_completed_client_planning',
  'eng_fieldwork'              => 'eng_fieldwork_complete',
  'eng_leadsheet_due'          => 'eng_leadsheet_complete',
  'eng_draft_due'              => 'eng_draft_sent',
  'eng_final_due'              => 'eng_final_sent'
];
foreach ($pairs as $date => $yn):
$checked = (($eng[$yn] ?? 'N') === 'Y');
?>
<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);"><?php echo ucwords(str_replace('_',' ',$date)); ?></label>
  <div class="d-flex align-items-center gap-2">

    <input type="date" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;"
           name="<?php echo $date; ?>"
           value="<?php echo $eng[$date] ?? ''; ?>">

    <div class="yn-toggle <?php echo $checked ? 'active' : ''; ?>"
         onclick="toggleYN(this)">
      <?php echo $checked ? '✓ Y' : 'N'; ?>
    </div>

    <!-- ALWAYS POST -->
    <input type="hidden" name="<?php echo $yn; ?>" value="<?php echo $checked ? 'Y' : 'N'; ?>">

  </div>
</div>
<?php endforeach; ?>

<!-- =====================
     DATE ONLY
===================== -->

<?php
$dateFields = [
  'eng_archive'            => 'Archive Date',
  'eng_last_communication' => 'Last Communication'
];
foreach ($dateFields as $field => $label):
?>
<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);"><?php echo $label; ?></label>
  <input type="date" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;"
         name="<?php echo $field; ?>"
         value="<?php echo $eng[$field] ?? ''; ?>">
</div>
<?php endforeach; ?>

<!-- =====================
     Y / N SELECTS
===================== -->

<div class="col-md-3">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Repeat</label>
  <select class="form-select" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_repeat">
    <option value="Y" <?php echo (($eng['eng_repeat'] ?? 'N') === 'Y') ? 'selected' : ''; ?>>Yes</option>
    <option value="N" <?php echo (($eng['eng_repeat'] ?? 'N') === 'N') ? 'selected' : ''; ?>>No</option>
  </select>
</div>

<div class="col-md-3">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Section 3 Requested</label>
  <select class="form-select" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_section_3_requested">
    <option value="Y" <?php echo (($eng['eng_section_3_requested'] ?? 'N') === 'Y') ? 'selected' : ''; ?>>Yes</option>
    <option value="N" <?php echo (($eng['eng_section_3_requested'] ?? 'N') === 'N') ? 'selected' : ''; ?>>No</option>
  </select>
</div>

<!-- =====================
     NOTES
===================== -->

<div class="col-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Notes</label>
  <textarea class="form-control" name="eng_notes" rows="4"><?php
    echo htmlspecialchars($eng['eng_notes'] ?? '', ENT_QUOTES);
  ?></textarea>
</div>

</div>
</div>

<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
  <button type="submit" class="btn btn-primary">Save Changes</button>
</div>

</form>
</div>
</div>
</div>






    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <script>
function toggleYN(el) {
  const hidden = el.nextElementSibling;
  el.classList.toggle('active');

  if (el.classList.contains('active')) {
    el.innerHTML = '<i class="bi bi-check"></i> Y';
    hidden.value = 'Y';
  } else {
    el.innerHTML = 'N';
    hidden.value = 'N';
  }
}
</script>


</body>
</html>