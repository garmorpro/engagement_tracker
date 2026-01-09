<?php 
// sessions_start();

require_once '../includes/functions.php';

$engagements = getAllEngagements($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Engagement Tracker</title>
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
            <a class="btn btn-sm tab-btn active" href="engagement-analytics.php">
              <i class="bi bi-graph-up"></i> Analytics
            </a>
          </div>

          <button class="btn menu-btn btn-sm ms-3"><i class="bi bi-archive"></i>&nbsp;&nbsp;Archive</button>
          <button class="btn menu-btn btn-sm ms-3"><i class="bi bi-tools"></i>&nbsp;&nbsp;Tools</button>
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
  
              <!-- Overdue -->
              <div class="col-md-3">
                <div class="card h-100" style="border-color: rgb(255,201,202); border-radius: 15px;">
                  <div class="card-body p-4">
  
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-3">
                      <div class="icon-square me-2" style="background-color: rgb(255,241,242);">
                        <i class="bi bi-exclamation-circle" style="color: rgb(241,0,24);"></i>
                      </div>
                      <h6 class="fw-semibold mb-0">Overdue</h6>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
  
  
  
                  </div>
                </div>
              </div>
  
              <!-- Due This Week -->
              <div class="col-md-3">
                <div class="card h-100" style="border-color: rgb(255,214,171);  border-radius: 15px;">
                  <div class="card-body">
  
                    <div class="d-flex align-items-center mb-3">
                      <div class="icon-square me-2" style="background-color: rgb(255,247,238);">
                        <i class="bi bi-calendar-week" style="color: rgb(255,71,0);"></i>
                      </div>
                      <h6 class="fw-semibold mb-0">Due This Week</h6>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                  </div>
                </div>
              </div>
  
              <!-- High Priority -->
              <div class="col-md-3">
                <div class="card h-100" style="border-color: rgb(236,213,254);  border-radius: 15px;">
                  <div class="card-body">
  
                    <div class="d-flex align-items-center mb-3">
                      <div class="icon-square me-2" style="background-color: rgb(251,245,254);">
                        <i class="bi bi-flag-fill" style="color: rgb(162,27,244);"></i>
                      </div>
                      <h6 class="fw-semibold mb-0">High Priority</h6>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                  </div>
                </div>
              </div>
  
              <!-- Recent Updates -->
              <div class="col-md-3">
                <div class="card h-100" style="border-color: rgb(187,219,253);  border-radius: 15px;">
                  <div class="card-body">
  
                    <div class="d-flex align-items-center mb-3">
                      <div class="icon-square me-2" style="background-color: rgb(238,246,254);">
                        <i class="bi bi-clock-history" style="color: rgb(20,95,245);"></i>
                      </div>
                      <h6 class="fw-semibold mb-0">Recent Updates</h6>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                  </div>
                </div>
              </div>
  
          </div>
      
      <!-- end status updates -->

      <!-- analytic charts -->
  
          <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">
  
              <!-- Status Distribution -->
              <div class="col-md-6">
                <div class="card h-100" style="border-color: rgb(229,231,235); border-radius: 15px;">
                  <div class="card-body p-4 d-flex flex-column align-items-center">
                                                    
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-3 w-100">
                      <h6 class="fw-semibold mb-0">Status Distribution</h6>
                    </div>
                                                    
                    <!-- Chart container centered -->
                    <div style="width: 400px; height: 400px; display: flex; justify-content: center; align-items: center;">
                      <canvas id="status_distribution"></canvas>
                    </div>
                                                    
                  </div>
                </div>
              </div>
                                                    
              <!-- Manager Workload -->
              <div class="col-md-6">
                <div class="card h-100" style="border-color: rgb(229,231,235); border-radius: 15px;">
                  <div class="card-body p-4 d-flex flex-column align-items-center">
                                                    
                    <div class="d-flex align-items-center mb-3 w-100">
                      <h6 class="fw-semibold mb-0">Manager Workload</h6>
                    </div>
                                                    
                    <div style="width: 400px; height: 400px; display: flex; justify-content: center; align-items: center;">
                      <canvas id="manager_workload"></canvas>
                    </div>
                                                    
                  </div>
                </div>
              </div>


  
          </div>
      
      <!-- end analytic charts -->

      <!-- audit breakdown -->
  
        <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">
          <div class="col-md-12">
            <div class="card h-100" style="border-color: rgb(229,231,235); border-radius: 15px;">
              <div class="card-body p-4">
                <!-- Header -->
                <div class="d-flex align-items-center mb-3 w-100">
                  <h6 class="fw-semibold mb-0">Audit Type Breakdown</h6>
                </div>          

                <!-- 4-column cards -->
                <div class="row mt-4">
                  <!-- Card 1 -->
                  <div class="col-md-3">
                    <div class="card h-100" style="background-color: rgb(247,248,250); border-radius: 12px; border: 1px solid rgb(229,231,235);">
                      <div class="card-body d-flex flex-column align-items-start">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; font-weight: bold;">
                          5
                        </div>
                        <div class="mt-2" style="font-size: 14px;">SOC 2</div>
                      </div>
                    </div>
                  </div>          

                  <!-- Card 2 -->
                  <div class="col-md-3">
                    <div class="card h-100" style="background-color: rgb(247,248,250); border-radius: 12px; border: 1px solid rgb(229,231,235);">
                      <div class="card-body d-flex flex-column align-items-start">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; font-weight: bold;">
                          3
                        </div>
                        <div class="mt-2" style="font-size: 14px;">SOC 1</div>
                      </div>
                    </div>
                  </div>          

                  <!-- Card 3 -->
                  <div class="col-md-3">
                    <div class="card h-100" style="background-color: rgb(247,248,250); border-radius: 12px; border: 1px solid rgb(229,231,235);">
                      <div class="card-body d-flex flex-column align-items-start">
                        <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; font-weight: bold;">
                          7
                        </div>
                        <div class="mt-2" style="font-size: 14px;">PCI</div>
                      </div>
                    </div>
                  </div>          

                  <!-- Card 4 -->
                  <div class="col-md-3">
                    <div class="card h-100" style="background-color: rgb(247,248,250); border-radius: 12px; border: 1px solid rgb(229,231,235);">
                      <div class="card-body d-flex flex-column align-items-start">
                        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; font-weight: bold;">
                          2
                        </div>
                        <div class="mt-2" style="font-size: 14px;">Other</div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- End 4-column cards -->         

              </div>
            </div>
          </div>
        </div>

      
      <!-- end audit breakdown -->
    





    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <?php
// Generate a single random RGB color
$red = rand(50, 250);
$green = rand(50, 250);
$blue = rand(50, 250);

// Fill arrays with the same color
$colors = array_fill(0, count($managerLabels), "rgba($red,$green,$blue,0.2)");
$borders = array_fill(0, count($managerLabels), "rgba($red,$green,$blue,1)");
?>


 <script>
// ----------------- Prepare engagement data -----------------
const engagements = <?php echo json_encode(array_map(function($eng) {
    return [
        'id' => (int)$eng['eng_id'],
        'idno' => $eng['eng_idno'],
        'name' => $eng['eng_name'],
        'manager' => $eng['eng_manager'] ?: 'Unassigned',
        'status' => strtolower($eng['eng_status']) ?: 'unknown',
        'planningCall' => $eng['eng_client_planning_call'] ?: null,
        'fieldworkStart' => $eng['eng_fieldwork'] ?: null,
        'draftDue' => $eng['eng_draft_due'] ?: null,
        'finalDue' => $eng['eng_final_due'] ?: null,
        'archived' => !empty($eng['eng_archive'])
    ];
}, $engagements), JSON_HEX_TAG | JSON_HEX_QUOT | JSON_HEX_APOS); ?>;

console.log("Loaded engagements:", engagements);

// ----------------- Compute manager workload -----------------
const managerCounts = {};
engagements.forEach(e => {
    if (e.archived) return;
    if (!managerCounts[e.manager]) managerCounts[e.manager] = 0;
    managerCounts[e.manager]++;
});
const managerLabels = Object.keys(managerCounts);
const managerData = Object.values(managerCounts);

// ----------------- Compute status counts -----------------
const statusCategories = ['on-hold','planning','in-progress','in-review','complete'];
const statusCounts = statusCategories.reduce((acc, s) => {
    acc[s] = 0;
    return acc;
}, {});

engagements.forEach(e => {
    if (statusCounts.hasOwnProperty(e.status)) statusCounts[e.status]++;
});

const statusLabels = ['On-Hold','Planning','In-Progress','In-Review','Complete'];
const statusData = Object.values(statusCounts);

// ----------------- Generate colors for manager chart -----------------
function randomColorArray(count, alpha=0.8) {
    const arr = [];
    for(let i=0;i<count;i++){
        const r=Math.floor(Math.random()*200+50);
        const g=Math.floor(Math.random()*200+50);
        const b=Math.floor(Math.random()*200+50);
        arr.push(`rgba(${r},${g},${b},${alpha})`);
    }
    return arr;
}
const managerColors = randomColorArray(managerLabels.length,0.2);
const managerBorders = randomColorArray(managerLabels.length,1);

// ----------------- Render Charts -----------------
let statusChart, managerChart;

function renderStatusChart() {
    const ctx = document.getElementById('status_distribution').getContext('2d');
    if(statusChart) statusChart.destroy();
    statusChart = new Chart(ctx, {
        type:'pie',
        data: {
            labels: statusLabels,
            datasets:[{
                data: statusData,
                backgroundColor:[
                    'rgba(107,114,128,0.8)',
                    'rgba(68,125,252,0.8)',
                    'rgba(241,115,19,0.8)',
                    'rgba(160,77,253,0.8)',
                    'rgba(79,198,95,0.8)'
                ],
                borderColor:[
                    'rgba(107,114,128,1)',
                    'rgba(68,125,252,1)',
                    'rgba(241,115,19,1)',
                    'rgba(160,77,253,1)',
                    'rgba(79,198,95,1)'
                ],
                borderWidth:1
            }]
        },
        options: {
            responsive:true,
            plugins: {
                legend:{position:'bottom'},
                tooltip:{enabled:true}
            }
        }
    });
}

function renderManagerChart() {
    const ctx = document.getElementById('manager_workload').getContext('2d');
    if(managerChart) managerChart.destroy();
    const maxValue = Math.max(...managerData);
    managerChart = new Chart(ctx, {
        type:'bar',
        data:{
            labels: managerLabels,
            datasets:[{
                label:'Active Engagements',
                data: managerData,
                backgroundColor: colors,
                borderColor: borders,
                borderWidth:1,
                borderRadius:{topLeft:15,topRight:15}
            }]
        },
        options:{
            responsive:true,
            plugins:{legend:{display:false}, tooltip:{enabled:true}},
            scales:{y:{beginAtZero:true, max:maxValue+2, ticks:{stepSize:1}}}
        }
    });
}

// ----------------- Render on load -----------------
document.addEventListener('DOMContentLoaded', () => {
    renderStatusChart();
    renderManagerChart();
});
</script>



</body>
</html>