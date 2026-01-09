<?php 
// sessions_start();

require_once '../includes/functions.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools - Engagement Tracker</title>
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
            <a class="btn btn-sm me-2 tab-btn" href="dashboard.php">
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

          <a class="btn menu-btn btn-sm ms-3" href="archive.php"><i class="bi bi-archive"></i>&nbsp;&nbsp;Archive</a>
          <a class="btn menu-btn btn-sm ms-3 active" href="tools.php"><i class="bi bi-tools"></i>&nbsp;&nbsp;Tools</a>
          <button class="btn new-btn btn-sm ms-3"><i class="bi bi-plus"></i>&nbsp;&nbsp;New Engagement</button>
        </div>
      </div>
    </div>
  <!-- Header -->

  <div class="mt-4"></div>

    <!-- Status Cards -->
          <div class="row row-cols-1 row-cols-md-5 g-4" style="margin-left: 200px; margin-right: 200px;">
              <div class="col">
                  <div class="card custom-card position-relative" style="background-color: rgb(225,238,253); border-color: rgb(225,228,232); overflow: hidden;     border-radius: 15px;  border-radius: 15px;">
                      <div class="card-body">
  
                          <!-- Small icon -->
                          <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(30,117,    255); color: white; font-size: 1.5rem; border-radius: 15px;">
                              <i class="bi bi-graph-up-arrow"></i>
                          </div>
  
                          <!-- Title -->
                          <h6 class="card-title mb-2" style="color: rgb(104,115,128);">Total Engagements</h6>
  
                          <!-- Big Number -->
                          <h2 class="fw-bold" style="color: rgb(30,117,225);">1,250</h2>
  
                          <!-- Decorative Icon behind -->
                          <i class="bi bi-graph-up-arrow position-absolute" style="font-size: 5rem; top: 100px; right: -10px; color: rgba(30,117,255,0.15); z-index:  0;   "></i>
                      </div>
                  </div>
              </div>
              <div class="col">
                  <div class="card custom-card position-relative" style="background-color: rgb(255,241,224); border-color: rgb(225,228,232); overflow: hidden;     border-radius: 15px;">
                      <div class="card-body">
  
                          <!-- Small icon -->
                          <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(255,92,    0); color: white; font-size: 1.5rem; border-radius: 15px;">
                              <i class="bi bi-clock"></i>
                          </div>
  
                          <!-- Title -->
                          <h6 class="card-title mb-2" style="color: rgb(104,115,128);">In Progress</h6>
  
                          <!-- Big Number -->
                          <h2 class="fw-bold" style="color: rgb(255,92,0);">1,250</h2>
  
                          <!-- Decorative Icon behind -->
                          <i class="bi bi-clock position-absolute" style="font-size: 5rem; top: 100px; right: -10px; color: rgba(255,92,0,0.15); z-index: 0;"></i>
                      </div>
                  </div>
              </div>
              <div class="col">
                  <div class="card custom-card position-relative" style="background-color: rgb(226,253,237); border-color: rgb(225,228,232); overflow: hidden;     border-radius: 15px;">
                      <div class="card-body">
  
                          <!-- Small icon -->
                          <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(0,194,     81); color: white; font-size: 1.5rem; border-radius: 15px;">
                              <i class="bi bi-check2-circle"></i>
                          </div>
  
                          <!-- Title -->
                          <h6 class="card-title mb-2" style="color: rgb(104,115,128);">Completed</h6>
  
                          <!-- Big Number -->
                          <h2 class="fw-bold" style="color: rgb(0,194,81);">1,250</h2>
  
                          <!-- Decorative Icon behind -->
                          <i class="bi bi-check2-circle position-absolute" style="font-size: 5rem; top: 100px; right: -10px; color: rgba(0,194,81,0.15); z-index: 0;     "></i>
                      </div>
                  </div>
              </div>
              <div class="col">
                  <div class="card custom-card position-relative" style="background-color: rgb(255,231,231); border-color: rgb(225,228,232); overflow: hidden;     border-radius: 15px;">
                      <div class="card-body">
  
                          <!-- Small icon -->
                          <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(255,35,    52); color: white; font-size: 1.5rem; border-radius: 15px;">
                              <i class="bi bi-exclamation-triangle"></i>
                          </div>
  
                          <!-- Title -->
                          <h6 class="card-title mb-2" style="color: rgb(104,115,128);">Overdue</h6>
  
                          <!-- Big Number -->
                          <h2 class="fw-bold" style="color: rgb(252,35,52);">1,250</h2>
  
                          <!-- Decorative Icon behind -->
                          <i class="bi bi-exclamation-triangle position-absolute" style="font-size: 5rem; top: 100px; right: -10px; color: rgba(255,35,52,0.15);     z-index: 0;"></i>
                      </div>
                  </div>
              </div>
              <div class="col">
                  <div class="card custom-card position-relative" style="background-color: rgb(247,236,254); border-color: rgb(225,228,232); overflow: hidden;     border-radius: 15px;">
                    <div class="card-body position-relative">
  
                      <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(172,63, 255);    color: white; font-size: 1.5rem; border-radius: 15px;">
                        <i class="bi bi-calendar2"></i>
                      </div>
  
                      <h6 class="card-title mb-2" style="color: rgb(104,115,128);">Due This Week</h6>
  
                      <h2 class="fw-bold" style="color: rgb(172,63,255);">1,250</h2>
  
                      <i class="bi bi-calendar2 position-absolute" style="font-size: 5rem; top: 100px; right: -10px; color: rgba(172,63,255,0.15); z-index: 0;"></i>
  
                    </div>
                  </div>
              </div>
      
          </div>
    <!-- end status cards -->

    <!-- Search bar -->
          <div class="row align-items-center mt-4" style="margin-left: 210px; margin-right: 210px;">
              <div class="p-3 border d-flex align-items-center" style="box-shadow: 1px 1px 4px rgba(0,0,0,0.15); border-radius: 15px;">
                  <div class="input-group flex-grow-1 me-3">
                      <span class="input-group-text border-end-0" style="background-color: rgb(248,249,251); color: rgb(142,151,164);">
                          <i class="bi bi-search"></i>
                      </span>
                      <input type="text" class="form-control border-start-0" style="background-color: rgb(248,249,251);;" placeholder="Search...">
                  </div>
                  <!-- Filter button -->
                  <button class="btn filter-btn d-flex" >
                    <i class="bi bi-funnel me-1"></i> Filter
                  </button>
              </div>
          </div>
    <!-- end search bar -->
  
        
  <!-- status updates -->
    <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">

      <div class="d-flex align-items-center" class="margin-bottom: -12px !important;">
        <div class="icon-square d-flex align-items-center justify-content-center me-2"
             style="width: 36px; height: 36px; border-radius: 8px; background-color: rgb(86,151,255);">
          <i class="bi bi-database" style="color: rgb(255,255,255);"></i>
        </div>

        <h5 class="fw-semibold mb-0">
          Data Management
        </h5>
      </div>


      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(198,224,254); background-color: rgb(233,243,254);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(86,151,255);">
                <i class="bi bi-file-earmark-arrow-down" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              Export Data
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              Export engagements to CSV or Excel
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(198,224,254); background-color: rgb(233,243,254);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(86,151,255);">
                <i class="bi bi-file-earmark-arrow-up" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              Import Data
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              Bulk import engagements from file
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(198,224,254); background-color: rgb(233,243,254);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(86,151,255);">
                <i class="bi bi-database" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              Backup & Restore
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              Create and restore data backups
            </div>
          </div>
        </div>
      </div>      

    </div>
  <!-- end status updates -->

  <!-- status updates -->
    <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">

      <div class="d-flex align-items-center" class="margin-bottom: -12px !important;">
        <div class="icon-square d-flex align-items-center justify-content-center me-2"
             style="width: 36px; height: 36px; border-radius: 8px; background-color: rgb(199,118,255);">
          <i class="bi bi-file-earmark-text" style="color: rgb(255,255,255);"></i>
        </div>

        <h5 class="fw-semibold mb-0">
          Reports & Analytics
        </h5>
      </div>


      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(241,223,254); background-color: rgb(251,245,254);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(199,118,255);">
                <i class="bi bi-file-earmark-text" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              Generate Reports
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              Create custom engagement reports
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(241,223,254); background-color: rgb(251,245,254);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(199,118,255);">
                <i class="bi bi-calculator" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              Resource Calculator
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              Calculate team capacity and allocation
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(241,223,254); background-color: rgb(251,245,254);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(199,118,255);">
                <i class="bi bi-calculator" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              Performance Metrics
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              View team and engagement metrics
            </div>
          </div>
        </div>
      </div>

    </div>
  <!-- end status updates -->

  <!-- status updates -->
    <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">

      <div class="d-flex align-items-center" class="margin-bottom: -12px !important;">
        <div class="icon-square d-flex align-items-center justify-content-center me-2"
             style="width: 36px; height: 36px; border-radius: 8px; background-color: rgb(64,214,133);">
          <i class="bi bi-file-earmark-text" style="color: rgb(255,255,255);"></i>
        </div>

        <h5 class="fw-semibold mb-0">
          Team Management
        </h5>
      </div>


      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(196,250,221); background-color: rgb(238,254,245);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(64,214,133);">
                <i class="bi bi-people" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              Team Directory
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              Manage team members and roles
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(196,250,221); background-color: rgb(238,254,245);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(64,214,133);">
                <i class="bi bi-people" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              Workload Balance
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              Analyze and balance team workload
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-4 visually-hidden">
        
      </div>

    </div>
  <!-- end status updates -->

  <!-- status updates -->
    <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">

      <div class="d-flex align-items-center" class="margin-bottom: -12px !important;">
        <div class="icon-square d-flex align-items-center justify-content-center me-2"
             style="width: 36px; height: 36px; border-radius: 8px; background-color: rgb(255,141,64);">
          <i class="bi bi-file-earmark-text" style="color: rgb(255,255,255);"></i>
        </div>

        <h5 class="fw-semibold mb-0">
          Settings
        </h5>
      </div>


      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(255,224,192); background-color: rgb(255,248,236);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(255,141,64);">
                <i class="bi bi-gear" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              System Settings
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              Configure application preferences
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card h-100" style="border-radius: 15px; border: 1px solid rgb(255,224,192); background-color: rgb(255,248,236);">
          <div class="card-body p-4">
            <!-- Icon (left) + Badge (right) -->
            <div class="d-flex align-items-center justify-content-between mb-3">
              <div class="icon-square d-flex align-items-center justify-content-center"
                   style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(255,141,64);">
                <i class="bi bi-gear" style="color: rgb(255,255,255);"></i>
              </div>
              <span class="badge" style="color: rgb(112,120,130); background-color: rgb(232,235,238);">
                Coming Soon
              </span>
            </div>
            <!-- Title -->
            <div class="fw-bold" style="font-size: 14px; color: rgb(77,81,90);">
              Custom Fields
            </div>
            <!-- Description -->
            <div class="text-secondary mt-2" style="font-size: 14px; color: rgb(137,146,158);">
              Add or modify engagement fields
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-4 visually-hidden">
        
      </div>

    </div>
  <!-- end status updates -->

  <!-- status updates -->
    <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">

      <div class="col-md-12">
  <div class="card h-100"
       style="border-radius: 15px; border: 1px solid rgb(255,224,192); background-color: rgb(255,248,236);">
    <div class="card-body p-4">

      <div class="d-flex align-items-start">

        <!-- Icon -->
        <div class="icon-square d-flex align-items-center justify-content-center me-3"
             style="width: 40px; height: 40px; border-radius: 8px; background-color: rgb(255,141,64); flex-shrink: 0;">
          <i class="bi bi-gear" style="color: rgb(255,255,255);"></i>
        </div>

        <!-- Text Content -->
        <div>
          <div class="fw-bold" style="font-size: 14px; color: rgb(27,58,139);">
            More Tools Coming Soon
          </div>

          <div class="text-secondary mt-1"
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
  <!-- end status updates -->


  <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>