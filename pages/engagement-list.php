<?php 
// sessions_start();

require_once '../includes/functions.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engagement Tracker</title>
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
            <button class="btn btn-sm me-2 tab-btn active" id="tab-board" data-bs-toggle="tab" data-bs-target="#content-board" type="button" role="tab">
              <i class="bi bi-grid"></i> Board
            </button>
            <button class="btn btn-sm me-2 tab-btn" id="tab-list" data-bs-toggle="tab" data-bs-target="#content-list" type="button" role="tab">
              <i class="bi bi-list-ul"></i> List
            </button>
            <button class="btn btn-sm me-2 tab-btn" id="tab-timeline" data-bs-toggle="tab" data-bs-target="#content-timeline" type="button" role="tab">
              <i class="bi bi-calendar2"></i> Timeline
            </button>
            <button class="btn btn-sm tab-btn" id="tab-analytics" data-bs-toggle="tab" data-bs-target="#content-analytics" type="button" role="tab">
              <i class="bi bi-graph-up"></i> Analytics
            </button>
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


      <!-- table -->
        <?php
          $engagements = getAllEngagements($conn);
          $totalEngagements = count($engagements);
          ?>
          
          <div class="mt-4" style="margin-left: 210px; margin-right: 210px;">
              Showing <?php echo $totalEngagements; ?> of <?php echo $totalEngagements; ?> engagements
                                  
              <div class="table-wrapper mt-3">
                  <table class="table align-middle mb-0">
                      <thead>
                          <tr style="background-color: rgb(236,236,240) !important;">
                              <th class="text-uppercase" style="font-size: 14px;" scope="col">ID</th>
                              <th class="text-uppercase" style="font-size: 14px;" scope="col">Engagement Name</th>
                              <th class="text-uppercase" style="font-size: 14px;" scope="col">Manager</th>
                              <th class="text-uppercase" style="font-size: 14px;" scope="col">Status</th>
                              <th class="text-uppercase" style="font-size: 14px;" scope="col">Period</th>
                              <th class="text-uppercase" style="font-size: 14px;" scope="col">Draft Due</th>
                              <th class="text-uppercase" style="font-size: 14px;" scope="col">Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach ($engagements as $eng): ?>
                              <tr>
                                  <td><?php echo htmlspecialchars($eng['eng_idno']); ?></td>
                                  <td>
                                      <strong><?php echo htmlspecialchars($eng['eng_name']); ?></strong><br>
                                      <?php if (!empty($eng['eng_audit_type'])): ?>
                                          <span class="text-secondary" style="font-size: 12px;"><?php echo htmlspecialchars($eng['eng_audit_type']); ?></span>
                                      <?php endif; ?>
                                  </td>
                                  <td><?php echo htmlspecialchars($eng['eng_manager']); ?></td>
                                  <td><?php echo htmlspecialchars(ucfirst($eng['eng_status'])); ?></td>
                                  <td><?php echo htmlspecialchars($eng['eng_period']); ?></td>
                                  <td>
                                      <?php
                                          if (!empty($eng['eng_draft_due'])) {
                                              echo date('Y-m-d', strtotime($eng['eng_draft_due']));
                                          }
                                      ?>
                                  </td>
                                  <td><i class="bi bi-trash"></i></td>
                              </tr>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>
          </div>




        </div>
      <!-- end table -->
    
    



    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


</body>
</html>