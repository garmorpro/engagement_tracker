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

// // Check if eng_idno is in the URL
// if (isset($_GET['eng_id'])) {
//     $eng_id = $_GET['eng_id'];

//     $stmt = $conn->prepare("SELECT * FROM engagements WHERE eng_idno = ?");
//     $stmt->bind_param("s", $eng_id);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result->num_rows > 0) {
//         $eng = $result->fetch_assoc();
//     } else {
//         echo "<div class='alert alert-warning'>Engagement not found.</div>";
//         exit;
//     }

//     $stmt->close();
// } else {
//     echo "<div class='alert alert-danger'>No engagement specified.</div>";
//     exit;
// }


if (isset($_GET['eng_id'])) {

    $eng_id = (int) $_GET['eng_id'];

    /* =========================
       1️⃣ Engagement info
    ========================= */
    $stmt = $conn->prepare("SELECT * FROM engagements WHERE eng_id = ?");
    $stmt->bind_param("i", $eng_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo "<div class='alert alert-warning'>Engagement not found.</div>";
        exit;
    }
    $eng = $result->fetch_assoc();
    $stmt->close();

/* =========================
   2️⃣ Team members
========================= */
$stmt = $conn->prepare("
    SELECT *
    FROM engagement_team
    WHERE eng_id = ?
    ORDER BY FIELD(role, 'Manager', 'Senior', 'Staff'), emp_name
");
$stmt->bind_param("i", $eng_id);
$stmt->execute();
$team_result = $stmt->get_result();

// Organize team by role, then by person, then collect DOLs per audit type
$team = ['Manager' => [], 'Senior' => [], 'Staff' => []];

while ($row = $team_result->fetch_assoc()) {
    $role = $row['role'];
    $name = $row['emp_name'];

    // Initialize person in the team array
    if (!isset($team[$role][$name])) {
        $team[$role][$name] = [
            'emp_name' => $name,
            'audit_dols' => [] // will hold SOC 1 / SOC 2 DOLs
        ];
    }

    // Only populate DOLs for Seniors and Staff
    if ($role !== 'Manager' && !empty($row['audit_type'])) {
        $audit_type = $row['audit_type']; // SOC 1 or SOC 2
        $dol = $row['emp_dol'];
        $team[$role][$name]['audit_dols'][$audit_type] = $dol;
    }
}

$stmt->close();


    /* =========================
       3️⃣ Milestones
    ========================= */
    $stmt = $conn->prepare("
        SELECT *
        FROM engagement_milestones
        WHERE eng_id = ?
        ORDER BY due_date ASC
    ");
    $stmt->bind_param("i", $eng_id);
    $stmt->execute();
    $ms_result = $stmt->get_result();
    $milestones = [];
    while ($row = $ms_result->fetch_assoc()) {
        $milestones[] = $row;
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
    $section_3_requested = $_POST['eng_section_3_requested'] ?? 'N';

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
       PREPARE UPDATE
    ============================ */
    $stmt = $conn->prepare("
        UPDATE engagements SET
            eng_name = ?,
            eng_poc = ?,
            eng_location = ?,
            eng_repeat = ?,
            eng_audit_type = ?,
            eng_scope = ?,
            eng_tsc = ?,
            eng_start_period = ?,
            eng_end_period = ?,
            eng_as_of_date = ?,
            eng_archive = ?,
            eng_section_3_requested = ?,
            eng_last_communication = ?,
            eng_notes = ?,
            eng_status = ?
        WHERE eng_idno = ?
    ");

    /* ============================
       BIND (15 fields + ID)
    ============================ */
    $stmt->bind_param(
        "ssssssssssssssss",
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
        header("Location: engagement-details.php?eng_id=" . urlencode($engId) . "&message=Engagement updated successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>
            Failed to update engagement: " . htmlspecialchars($stmt->error) . "
        </div>";
    }
}




// Handle adding engagement
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
    $section_3_requested = $_POST['eng_section_3_requested'] ?? 'N';

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
       PREPARE INSERT
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
            eng_section_3_requested,
            eng_last_communication,
            eng_notes,
            eng_status
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
        )
    ");

    /* ============================
       BIND (16 params)
    ============================ */
    $stmt->bind_param(
        "ssssssssssssssss",
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
        $section_3_requested,
        $last_communication,
        $notes,
        $status
    );

    /* ============================
       EXECUTE
    ============================ */
    if ($stmt->execute()) {
        header("Location: engagement-details.php?eng_id=" . urlencode($engId) . "&message=Engagement added successfully");
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
          <button class="btn new-btn btn-sm ms-3" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus"></i>&nbsp;&nbsp;New Engagement
          </button>
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
              <?php //echo htmlspecialchars(formatDate($eng['eng_final_due']) ?? 'N/A'); ?>
              N/A
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
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
          <div class="icon-square me-2" style="background-color: rgb(222,234,252); height: 40px; width: 40px;">
            <i class="bi bi-people" style="color: rgb(0,42,241);"></i>
          </div>
          <h6 class="fw-semibold mb-0" style="color: rgb(0,0,0); font-size: 20px !important;">Team</h6>
        </div>

        <!-- Add/Edit DOL Buttons -->
        <div id="dolButtonsContainer" class="d-flex gap-2"></div>
      </div>

      <!-- Add Buttons -->
      <div class="d-flex mb-3 gap-3">
        <a href="javascript:void(0);" id="addManagerBtn" class="text-decoration-none text-blue fw-semibold">
          <i class="bi bi-plus-circle me-1"></i> Add Manager
        </a>
        <a href="javascript:void(0);" id="addSeniorBtn" class="text-decoration-none text-purple fw-semibold">
          <i class="bi bi-plus-circle me-1"></i> Add Senior
        </a>
        <a href="javascript:void(0);" id="addStaffBtn" class="text-decoration-none text-success fw-semibold">
          <i class="bi bi-plus-circle me-1"></i> Add Staff
        </a>
      </div>

      <div id="managerContainer">
  <?php if (!empty($team['Manager'])): 
        $manager = array_values($team['Manager'])[0]; ?>
    <div class="card mb-4" data-name="<?php echo htmlspecialchars($manager['emp_name']); ?>" style="border-color: rgb(190,215,252); border-radius: 20px; background-color: rgb(230,240,252);">
      <div class="card-body p-3 position-relative">

        <!-- Trash Icon -->
        <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 delete-team-member" style="border:none; background:transparent;">
          <i class="bi bi-trash"></i>
        </button>

        <div class="d-flex align-items-center mb-3">
          <h6 class="mb-0 text-uppercase" style="color: rgb(21,87,242); font-weight: 600 !important; font-size: 12px !important;">Manager</h6>
        </div>
        <h6 class="fw-semibold" style="color: rgb(0,37,132); font-size: 20px;">
          <?php echo htmlspecialchars($manager['emp_name']); ?>
        </h6>
      </div>
    </div>
  <?php endif; ?>
</div>


      <div id="seniorsContainer">
  <?php 
  $i = 1;
  if (!empty($team['Senior'])): 
    foreach ($team['Senior'] as $senior): ?>
      <div class="card mb-4 senior-card" data-index="<?php echo $i; ?>" data-name="<?php echo htmlspecialchars($senior['emp_name']); ?>" style="border-color: rgb(228,209,253); border-radius: 20px; background-color: rgb(242,235,253);">
        <div class="card-body p-3 position-relative">

          <!-- Trash Icon -->
          <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 delete-team-member" style="border:none; background:transparent;">
            <i class="bi bi-trash"></i>
          </button>

          <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 text-uppercase" style="color: rgb(123,0,240); font-weight: 600 !important; font-size: 12px !important;">Senior <?php echo $i; ?></h6>
          </div>
          <h6 class="fw-semibold" style="color: rgb(74,0,133); font-size: 20px;">
            <?php echo htmlspecialchars($senior['emp_name']); ?>
          </h6>

          <?php if (!empty($senior['audit_dols']['SOC 1'])): ?>
            <p class="pt-2" style="color: rgb(97,0,206); font-size: 12px;">
              <strong>SOC 1 DOL:</strong> <?php echo htmlspecialchars($senior['audit_dols']['SOC 1']); ?>
            </p>
          <?php endif; ?>

          <?php if (!empty($senior['audit_dols']['SOC 2'])): ?>
            <p class="pt-1" style="color: rgb(97,0,206); font-size: 12px;">
              <strong>SOC 2 DOL:</strong> <?php echo htmlspecialchars($senior['audit_dols']['SOC 2']); ?>
            </p>
          <?php endif; ?>
        </div>
      </div>
  <?php 
    $i++;
    endforeach; 
  endif; ?>
</div>


      <!-- Staff Container -->
<div id="staffContainer">
  <?php 
  $i = 1;
  if (!empty($team['Staff'])): 
    foreach ($team['Staff'] as $staff): ?>
      <div class="card mb-4 staff-card" data-index="<?php echo $i; ?>" data-name="<?php echo htmlspecialchars($staff['emp_name']); ?>" style="border-color: rgb(198,246,210); border-radius: 20px; background-color: rgb(234,252,239);">
        <div class="card-body p-3 position-relative">

          <!-- Trash Icon -->
          <button type="button" class="btn btn-sm btn-outline-danger position-absolute top-0 end-0 m-2 delete-team-member" style="border:none; background:transparent;">
            <i class="bi bi-trash"></i>
          </button>

          <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 text-uppercase" style="color: rgb(69,166,81); font-weight: 600 !important; font-size: 12px !important;">Staff <?php echo $i; ?></h6>
          </div>
          <h6 class="fw-semibold" style="color: rgb(0,42,0); font-size: 20px;">
            <?php echo htmlspecialchars($staff['emp_name']); ?>
          </h6>

          <?php if (!empty($staff['audit_dols']['SOC 1'])): ?>
            <p class="pt-2" style="color: rgb(0,142,0); font-size: 12px;">
              <strong>SOC 1 DOL:</strong> <?php echo htmlspecialchars($staff['audit_dols']['SOC 1']); ?>
            </p>
          <?php endif; ?>

          <?php if (!empty($staff['audit_dols']['SOC 2'])): ?>
            <p class="pt-1" style="color: rgb(0,142,0); font-size: 12px;">
              <strong>SOC 2 DOL:</strong> <?php echo htmlspecialchars($staff['audit_dols']['SOC 2']); ?>
            </p>
          <?php endif; ?>
        </div>
      </div>
  <?php 
    $i++;
    endforeach; 
  endif; ?>
</div>


      <!-- No team assigned message -->
      <?php if (empty($team['Manager']) && empty($team['Senior']) && empty($team['Staff'])): ?>
        <p id="noTeamMsg" class="text-muted" style="font-style: italic;">No team assigned yet.</p>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
  document.querySelectorAll('.delete-team-member').forEach(btn => {
  btn.addEventListener('click', async e => {
    const card = e.target.closest('.card');
    const name = card.getAttribute('data-name');

    if (!confirm(`Are you sure you want to delete ${name}?`)) return;

    const formData = new FormData();
    formData.append('eng_id', "<?php echo $eng['eng_id']; ?>");
    formData.append('name', name);

    try {
      const res = await fetch('../includes/delete_team_member.php', { method: 'POST', body: formData });
      const data = await res.json();

      if (data.success) card.remove();
      else alert('Failed to delete: ' + (data.error || 'Unknown error'));
    } catch (err) {
      console.error(err);
      alert('Failed to delete: network or server error.');
    }
  });
});


</script>




<!-- Modal for this engagement -->
<?php include_once '../includes/team_management_section.php'; ?>











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
<?php include_once '../includes/modals/edit_engagement_modal.php'; ?>

<!-- Modal for Adding Engagement -->
<?php include_once '../includes/modals/adding_engagement_modal.php'; ?>

<!-- Modal for this engagement -->
<?php include_once '../includes/modals/add_edit_dol.php'; ?>









    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Engagement Status JS -->
  <script src="../assets/js/engagement_status.js"></script> 
  <script src="../assets/js/toggle_check.js"></script>

</body>
</html>