<?php 
// sessions_start();

require_once '../includes/functions.php';


// Check if eng_idno is in the URL
if (isset($_GET['eng_id'])) {
    $eng_id = $_GET['eng_id'];

    // Prepare SQL to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM engagements WHERE eng_idno = ?");
    $stmt->bind_param("s", $eng_id); // "s" = string
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $eng = $result->fetch_assoc();
    } else {
        // No engagement found
        echo "<div class='alert alert-warning'>Engagement not found.</div>";
        exit;
    }

    $stmt->close();
} else {
    // eng_id not provided
    echo "<div class='alert alert-danger'>No engagement specified.</div>";
    exit;
}


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
  
          <!-- Right: Edit & Delete buttons -->
          <div class="right">
            <!-- Edit button -->
            <button class="btn btn-edit me-2">
              <i class="bi bi-pencil-square me-1"></i> Edit
            </button>
  
            <!-- Delete button -->
            <button class="btn btn-delete">
              <i class="bi bi-trash me-1"></i> Delete
            </button>
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
                  <span class="badge text-bg-secondary"><?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?></span>

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
                <i class="bi bi-exclamation-circle" style="color: rgb(255,255,255);"></i>
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
                <i class="bi bi-exclamation-circle" style="color: rgb(255,255,255);"></i>
              </div>
              <h6 class="fw-semibold mb-0" style="color: rgb(89,0,135);">Review Period/As of Date</h6>
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
                    <?php echo htmlspecialchars(formatDate($eng['eng_as_of_date'])); ?>
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
                <i class="bi bi-exclamation-circle" style="color: rgb(255,255,255);"></i>
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
                <i class="bi bi-exclamation-circle" style="color: rgb(255,255,255);"></i>
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

  <!-- <div class="container-fluid vh-100"> -->
  <div class="row h-100" style="margin-left: 200px; margin-right: 200px;">

    <!-- LEFT COLUMN (Full Height) -->
    <div class="col-md-6 h-100">
      <div class="h-100 bg-light p-3">
        Left column (full height)
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-md-6 h-100">
      <div class="d-flex flex-column h-100">

        <!-- TOP ROW (Short) -->
        <div class="bg-secondary text-white p-3">
          Top right (short)
        </div>

        <!-- BOTTOM ROW (Fills remaining height) -->
        <div class="flex-grow-1 bg-dark text-white p-3">
          Bottom right (fills height)
        </div>

      </div>
    </div>

  </div>
<!-- </div> -->








    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>