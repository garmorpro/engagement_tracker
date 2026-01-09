<?php 
// sessions_start();

require_once '../includes/functions.php';

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

  <!-- upper bay -->
    <div class="row g-4" style="margin-left: 200px; margin-right: 200px;">

      <div class="col-md-12">
        <div class="card h-100"
             style="border-radius: 15px; border: 1px solid rgb(187,219,253); background-color: rgb(238,246,254);">
          <div class="card-body p-4">

            <div class="d-flex align-items-start">

              <!-- Icon -->
              <div class="icon-square d-flex align-items-center justify-content-center me-3"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(33,128,255); flex-shrink: 0;">
                <i class="bi bi-tools" style="color: rgb(255,255,255);"></i>
              </div>

              <!-- Text Content -->
              <div>
                <div class="fw-bold" style="font-size: 14px; color: rgb(27,58,139);">
                  More Tools Coming Soon
                </div>

                <div class="mt-1"
                     style="font-size: 14px; color: rgb(52,83,189);">
                  We're actively developing new tools to help streamline your audit engagement workflow.
                  Check back regularly for updates and new features!
                </div>
              </div>

            </div>

          </div>
        </div>
      </div>


    </div>
  <!-- end upper bay -->





    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>