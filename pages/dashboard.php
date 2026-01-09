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

  <div class="tab-content mt-2">
    <div class="tab-pane fade show active" id="content-board" role="tabpanel">
  
        <!-- Board sections -->
  
          <!-- Row 1 -->
            <div class="row align-items-center" style="margin-top: 20px; margin-left: 210px; margin-right: 210px;">
                <div class="card" style="border-radius: 15px; border: 2px solid rgb(208,213,219); background-color: rgb(247,248,250) !important;">
                    <div class="card-body">
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(105,114,129) !important;">
                          On Hold
                          <?php
                            $engagements = getAllEngagements($conn);

                            // Count in-progress engagements
                            $onHoldCount = count(array_filter($engagements, function($eng) {
                                return $eng['eng_status'] === 'on-hold';
                            }));
                            ?>
                          <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(149,156,166) !important;">
                             <?php echo $onHoldCount; ?>
                          </span>
                        </span>
    
                        <!-- kanban card -->
                          <?php
                          $engagements = getAllEngagements($conn);

                          // Filter only 'on-hold' engagements
                          $onHoldEngagements = array_filter($engagements, function($eng) {
                              return $eng['eng_status'] === 'on-hold';
                          });

                          if (count($onHoldEngagements) > 0):
                              foreach ($onHoldEngagements as $eng):
                                  // Format the fieldwork date nicely
                                  $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d', strtotime($eng['eng_fieldwork'])) : '';
                          ?>
                              <div class="card engagement-card-kanban mb-2" style="border-radius: 15px; border: 1px solid rgb(208,213,219); cursor: move;">
                                  <div class="card-body d-flex align-items-center justify-content-between">
                              
                                      <!-- LEFT -->
                                      <div class="left d-flex align-items-center gap-3">
                                          <i class="bi bi-grip-horizontal text-secondary"></i>
                                          <div>
                                              <h5 class="mb-0" style="font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($eng['eng_name']); ?></h5>
                                              <span class="text-muted" style="font-size: 14px;"><?php echo htmlspecialchars($eng['eng_idno']); ?></span>
                                          </div>
                                      </div>
                              
                                      <!-- RIGHT -->
                                      <div class="right d-flex align-items-center gap-3 text-secondary">
                                          <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?></span>
                                          <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?></span>
                              
                                          <?php if (!empty($eng['eng_audit_type'])): ?>
                                              <span class="badge text-bg-secondary" style="background-color: rgba(235, 236, 237, 1) !important; color: rgb(57,69,85) !important; font-weight:                           500 !important;">
                                                  <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                              </span>
                                          <?php endif; ?>
                                          
                                          <?php
                                          $today = date('Y-m-d');
                                          if (!empty($eng['eng_final_due']) && $eng['eng_final_due'] < $today):
                                          ?>
                                              <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important; font-weight: 500                          !important;">
                                                  Overdue
                                              </span>
                                          <?php endif; ?>
                                      </div>
                                          
                                  </div>
                              </div>
                          <?php
                              endforeach;
                          else:
                          ?>
                              <div class="text-center text-muted py-4" style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                  Drop engagements here
                              </div>
                          <?php
                          endif;
                          ?>
                        <!-- end kanban card -->
                   
                    </div>
                </div>
            </div>
          <!-- end row 1 -->
  
          <!-- Row 2 -->
            <div class="row align-items-start g-4" style="margin-top: 1px; margin-top: px; margin-left: 200px; margin-right: 200px;">
  
              <!-- Column 1 -->
                <div class="col-md-4">
  
                  <div class="card" style="border-radius: 15px; border: 2px solid rgb(154,196,254); background-color: rgb(233,241,254) !important;">
                    <div class="card-body">
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(68, 125,252) !important;">
                          Planning
                          <?php
                            $engagements = getAllEngagements($conn);

                            // Count in-progress engagements
                            $planningCount = count(array_filter($engagements, function($eng) {
                                return $eng['eng_status'] === 'planning';
                            }));
                            ?>
                          <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(123,162,249) !important;">
                            <?php echo $planningCount; ?>
                          </span>
                        </span>
  
  
                        <!-- PHP Kanban card -->
                          <?php
                            $engagements = getAllEngagements($conn);

                            // Filter only 'planning' engagements
                            $planningEngagements = array_filter($engagements, function($eng) {
                                return $eng['eng_status'] === 'planning';
                            });

                            if (count($planningEngagements) > 0):
                                foreach ($planningEngagements as $eng):
                                    // Format date
                                    $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d, Y', strtotime($eng['eng_fieldwork'])) : '';
                            ?>
                                <div class="card engagement-card-kanban mb-2" style="background-color: rgb(249,250,251); border: 1px solid rgb(208,213,219); border-radius: 15px; cursor: move;">
                                    <div class="card-body" style="margin-bottom: -15px !important;">
                                
                                        <!-- Title row -->
                                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                                            <h6 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($eng['eng_name']); ?></h6>
                                            <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                                        </div>
                                
                                        <!-- Subtext -->
                                        <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                                            <span style="color: rgb(106,115,130); font-size: 14px !important;"><?php echo htmlspecialchars($eng['eng_idno']); ?></span><br>
                                            <div class="pb-2"></div>
                                            <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?></span><br>
                                            <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?></span><br>
                                
                                            <div class="tags pt-2">
                                                <?php if (!empty($eng['eng_audit_type'])): ?>
                                                    <span class="badge text-bg-secondary" style="background-color: rgba(235,236,237,1) !important; color: rgb(57,69,85) !important; font-weight:                            500 !important;">
                                                        <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php
                                                $today = date('Y-m-d');
                                                if (!empty($eng['eng_final_due']) && $eng['eng_final_due'] < $today):
                                                ?>
                                                    <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important; font-weight: 500                            !important;">
                                                        Overdue
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </p>
                                                
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <div class="text-center text-muted py-4" style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                            <?php
                            endif;
                            ?>

                        <!-- end PHP kanban card -->
  
  
  
                    </div>
                  </div>
                </div> 
              <!-- end col 1 -->
  
              <!-- Column 2 -->
                <div class="col-md-4">
  
                  <div class="card" style="border-radius: 15px; border: 2px solid rgb(247,187,118); background-color: rgb(253,244,229) !important;">
                    <div class="card-body">
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(241,115,19) !important;">
                          In Progress
                          <?php
                            $engagements = getAllEngagements($conn);

                            // Count in-progress engagements
                            $inProgressCount = count(array_filter($engagements, function($eng) {
                                return $eng['eng_status'] === 'in-progress';
                            }));
                            ?>
                          <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(243,155,89) !important;">
                            <?php echo $inProgressCount; ?>
                          </span>
                        </span>
  
                        <!-- PHP Kanban card -->
                          <?php
                            $engagements = getAllEngagements($conn);
                                                      
                            // Filter only 'planning' engagements
                            $planningEngagements = array_filter($engagements, function($eng) {
                                return $eng['eng_status'] === 'in-progress';
                            });
                            
                            if (count($planningEngagements) > 0):
                                foreach ($planningEngagements as $eng):
                                    // Format date
                                    $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d, Y', strtotime($eng['eng_fieldwork'])) : '';
                            ?>
                                <div class="card engagement-card-kanban mb-2" style="background-color: rgb(249,250,251); border: 1px solid rgb(208,213,219); border-radius: 15px; cursor: move;">
                                    <div class="card-body" style="margin-bottom: -15px !important;">
                                
                                        <!-- Title row -->
                                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                                            <h6 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($eng['eng_name']); ?></h6>
                                            <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                                        </div>
                                
                                        <!-- Subtext -->
                                        <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                                            <span style="color: rgb(106,115,130); font-size: 14px !important;"><?php echo htmlspecialchars($eng['eng_idno']); ?></span><br>
                                            <div class="pb-2"></div>
                                            <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?></span><br>
                                            <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?></span><br>
                                
                                            <div class="tags pt-2">
                                                <?php if (!empty($eng['eng_audit_type'])): ?>
                                                    <span class="badge text-bg-secondary" style="background-color: rgba(235,236,237,1) !important; color: rgb(57,69,85) !important; font-weight:                            500 !important;">
                                                        <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php
                                                $today = date('Y-m-d');
                                                if (!empty($eng['eng_final_due']) && $eng['eng_final_due'] < $today):
                                                ?>
                                                    <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important; font-weight: 500                            !important;">
                                                        Overdue
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </p>
                                                
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <div class="text-center text-muted py-4" style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                            <?php
                            endif;
                            ?>

                        <!-- end PHP kanban card -->


                    </div>
                  </div>
                </div> 
              <!-- end col 2 -->
  
              <!-- Column 3 -->
                <div class="col-md-4">
  
                  <div class="card" style="border-radius: 15px; border: 2px solid rgb(212,179,254); background-color: rgb(247,241,254) !important;">
                    <div class="card-body">
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(160,77,253) !important;">
                          In Review
                            <?php
                            $engagements = getAllEngagements($conn);

                            // Count in-progress engagements
                            $inReviewCount = count(array_filter($engagements, function($eng) {
                                return $eng['eng_status'] === 'in-review';
                            }));
                            ?>

                            <!-- Badge showing the number -->
                            <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(188,129,251) !important;">
                                <?php echo $inReviewCount; ?>
                            </span>
                        </span>
  
  
                        <!-- PHP Kanban card -->
                          <?php
                            $engagements = getAllEngagements($conn);
                                                      
                            // Filter only 'planning' engagements
                            $planningEngagements = array_filter($engagements, function($eng) {
                                return $eng['eng_status'] === 'in-review';
                            });
                            
                            if (count($planningEngagements) > 0):
                                foreach ($planningEngagements as $eng):
                                    // Format date
                                    $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d, Y', strtotime($eng['eng_fieldwork'])) : '';
                            ?>
                                <div class="card engagement-card-kanban mb-2" style="background-color: rgb(249,250,251); border: 1px solid rgb(208,213,219); border-radius: 15px; cursor: move;">
                                    <div class="card-body" style="margin-bottom: -15px !important;">
                                
                                        <!-- Title row -->
                                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                                            <h6 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($eng['eng_name']); ?></h6>
                                            <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                                        </div>
                                
                                        <!-- Subtext -->
                                        <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                                            <span style="color: rgb(106,115,130); font-size: 14px !important;"><?php echo htmlspecialchars($eng['eng_idno']); ?></span><br>
                                            <div class="pb-2"></div>
                                            <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?></span><br>
                                            <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?></span><br>
                                
                                            <div class="tags pt-2">
                                                <?php if (!empty($eng['eng_audit_type'])): ?>
                                                    <span class="badge text-bg-secondary" style="background-color: rgba(235,236,237,1) !important; color: rgb(57,69,85) !important; font-weight:                            500 !important;">
                                                        <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php
                                                $today = date('Y-m-d');
                                                if (!empty($eng['eng_final_due']) && $eng['eng_final_due'] < $today):
                                                ?>
                                                    <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important; font-weight: 500                            !important;">
                                                        Overdue
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </p>
                                                
                                    </div>
                                </div>
                            <?php
                                endforeach;
                            else:
                            ?>
                                <div class="text-center text-muted py-4" style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                            <?php
                            endif;
                            ?>

                        <!-- end PHP kanban card -->
  
  
  
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
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(79,198,95) !important;">
                          Complete
                          <?php
                            $engagements = getAllEngagements($conn);

                            // Count in-progress engagements
                            $completeCount = count(array_filter($engagements, function($eng) {
                                return $eng['eng_status'] === 'complete';
                            }));
                            ?>
                          <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(130,212,141) !important;">
                            <?php echo $completeCount; ?>
                          </span>
                        </span>
  
  
                       <!-- kanban card -->
                          <?php
                          $engagements = getAllEngagements($conn);

                          // Filter only 'on-hold' engagements
                          $onHoldEngagements = array_filter($engagements, function($eng) {
                              return $eng['eng_status'] === 'complete';
                          });

                          if (count($onHoldEngagements) > 0):
                              foreach ($onHoldEngagements as $eng):
                                  // Format the fieldwork date nicely
                                  $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d', strtotime($eng['eng_fieldwork'])) : '';
                          ?>
                              <div class="card engagement-card-kanban mb-2" style="border-radius: 15px; border: 1px solid rgb(208,213,219); cursor: move;">
                                  <div class="card-body d-flex align-items-center justify-content-between">
                              
                                      <!-- LEFT -->
                                      <div class="left d-flex align-items-center gap-3">
                                          <i class="bi bi-grip-horizontal text-secondary"></i>
                                          <div>
                                              <h5 class="mb-0" style="font-size: 18px; font-weight: 600;"><?php echo htmlspecialchars($eng['eng_name']); ?></h5>
                                              <span class="text-muted" style="font-size: 14px;"><?php echo htmlspecialchars($eng['eng_idno']); ?></span>
                                          </div>
                                      </div>
                              
                                      <!-- RIGHT -->
                                      <div class="right d-flex align-items-center gap-3 text-secondary">
                                          <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?></span>
                                          <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?></span>
                              
                                          <?php if (!empty($eng['eng_audit_type'])): ?>
                                              <span class="badge text-bg-secondary" style="background-color: rgba(235, 236, 237, 1) !important; color: rgb(57,69,85) !important; font-weight:                           500 !important;">
                                                  <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                              </span>
                                          <?php endif; ?>
                                          
                                          <?php
                                          $today = date('Y-m-d');
                                          if (!empty($eng['eng_final_due']) && $eng['eng_final_due'] < $today):
                                          ?>
                                              <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important; font-weight: 500                          !important;">
                                                  Overdue
                                              </span>
                                          <?php endif; ?>
                                      </div>
                                          
                                  </div>
                              </div>
                          <?php
                              endforeach;
                          else:
                          ?>
                              <div class="text-center text-muted py-4" style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                  Drop engagements here
                              </div>
                          <?php
                          endif;
                          ?>
                        <!-- end kanban card -->
  
  
                        
                    </div>
                </div>
            </div>
          <!-- end row 3 -->
  
        <!-- end board sections -->

    </div>

    <div class="tab-pane fade" id="content-list" role="tabpanel">

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
      
    </div>

    <div class="tab-pane fade" id="content-timeline" role="tabpanel">

      <!-- Calendar -->
        <div class="calendar mt-4" style="margin-left: 210px; margin-right: 210px;">

          <div class="calendar-header">
            <h5 id="calendar-title"></h5>
            <div class="calendar-nav">
              <button id="prevMonth" class="nav-btn">
                <i class="bi bi-chevron-left"></i>
              </button>
              <button id="nextMonth" class="nav-btn">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>

          <!-- Weekdays -->
          <div class="calendar-weekdays">
            <div>Sun</div>
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
          </div>

          <!-- Days -->
          <div id="calendar-days" class="calendar-days"></div>

          <hr>

          <div class="table-key" style="display: flex; gap: 20px; align-items: center;">
            <div class="final_due" style="display: flex; align-items: center; gap: 5px;">
              <span style="display:inline-block; width:10px; height:10px; background-color: rgba(55, 182, 38, 1); border-radius: 3px;"></span>
              Final Due
            </div>
            <div class="draft_due" style="display: flex; align-items: center; gap: 5px;">
              <span style="display:inline-block; width:10px; height:10px; background-color: rgba(195, 119, 38, 1); border-radius: 3px;"></span>
              Draft Due
            </div>
            <div class="fieldwork_start" style="display: flex; align-items: center; gap: 5px;">
              <span style="display:inline-block; width:10px; height:10px; background-color: rgba(41, 133, 193, 1); border-radius: 3px;"></span>
              Fieldwork Start
            </div>
            <div class="planning_call" style="display: flex; align-items: center; gap: 5px;">
              <span style="display:inline-block; width:10px; height:10px; background-color: rgba(131, 38, 193, 1); border-radius: 3px;"></span>
              Planning Call
            </div>
          </div>
        </div>
      <!-- end calendar -->
       
    </div>

    <div class="tab-pane fade" id="content-analytics" role="tabpanel">

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
    
    </div>

    
  </div>





    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


 <?php
// ----------------- Manager Workload -----------------
$managerCounts = [];
foreach ($engagements as $eng) {
    if (!empty($eng['archived'])) continue; // Skip archived
    $manager = $eng['eng_manager'];
    if (!empty($manager)) {
        if (!isset($managerCounts[$manager])) $managerCounts[$manager] = 0;
        $managerCounts[$manager]++;
    }
}
$managerLabels = array_keys($managerCounts);
$managerData   = array_values($managerCounts);

// ----------------- Status Counts -----------------
$statusCounts = [
    'on-hold' => 0,
    'planning' => 0,
    'in-progress' => 0,
    'in-review' => 0,
    'complete' => 0
];
foreach ($engagements as $eng) {
    $status = strtolower($eng['eng_status']);
    if (isset($statusCounts[$status])) $statusCounts[$status]++;
}
$statusLabels = ['On-Hold', 'Planning', 'In-Progress', 'In-Review', 'Complete'];
$statusData = array_values($statusCounts);
?>

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
let statusChart;
let managerChart;

// ----------------- Status Pie Chart -----------------
function renderStatusChart() {
    const ctx = document.getElementById('status_distribution').getContext('2d');

    if (statusChart) statusChart.destroy();

    statusChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($statusLabels); ?>,
            datasets: [{
                label: 'Engagement Status',
                data: <?php echo json_encode($statusData); ?>,
                backgroundColor: [
                    'rgba(107,114,128,0.8)',   // On-Hold
                    'rgba(68,125,252,0.8)',    // Planning
                    'rgba(241,115,19,0.8)',    // In-Progress
                    'rgba(160,77,253,0.8)',    // In-Review
                    'rgba(79,198,95,0.8)'      // Complete
                ],
                borderColor: [
                    'rgba(107,114,128,1)',
                    'rgba(68,125,252,1)',
                    'rgba(241,115,19,1)',
                    'rgba(160,77,253,1)',
                    'rgba(79,198,95,1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' },
                tooltip: { enabled: true }
            }
        }
    });
}

// ----------------- Manager Workload Bar Chart -----------------
function renderManagerChart() {
    const ctx = document.getElementById('manager_workload').getContext('2d');

    if (managerChart) managerChart.destroy();

    // Generate multi-color bars
    const colors = <?php echo json_encode($colors); ?>;
    const borders = <?php echo json_encode($borders); ?>;

    // Find max value in the data
    const dataValues = <?php echo json_encode($managerData); ?>;
    const maxValue = Math.max(...dataValues);

    managerChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($managerLabels); ?>,
            datasets: [{
                label: 'Active Engagements',
                data: dataValues,
                backgroundColor: colors,
                borderColor: borders,
                borderWidth: 1,
                borderRadius: { topLeft: 15, topRight: 15 } // top two corners rounded
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: { enabled: true }
            },
            scales: {
                y: { 
                    beginAtZero: true, 
                    max: maxValue + 2, // top value + 2
                    ticks: { stepSize: 1 } 
                }
            }
        }
    });
}


// ----------------- Only render charts when Analytics tab is active -----------------
document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabBtn => {
    tabBtn.addEventListener('shown.bs.tab', (e) => {
        if (e.target.getAttribute('data-bs-target') === '#content-analytics') {
            renderStatusChart();
            renderManagerChart();
        }
    });
});
</script>






  

<script>
const engagements = [
<?php foreach ($engagements as $index => $eng): ?>
{
    id: <?php echo $eng['eng_id']; ?>,
    name: "<?php echo addslashes($eng['eng_name']); ?>",
    planningCall: "<?php echo $eng['eng_client_planning_call']; ?>",
    fieldworkStart: "<?php echo $eng['eng_fieldwork']; ?>",
    draftDue: "<?php echo $eng['eng_draft_due']; ?>",
    finalDue: "<?php echo $eng['eng_final_due']; ?>"
}<?php echo ($index < count($engagements) - 1) ? ',' : ''; ?>
<?php endforeach; ?>
];

let currentDate = new Date();

function renderCalendar() {
    const daysContainer = document.getElementById("calendar-days");
    if (!daysContainer) return;

    daysContainer.innerHTML = "";

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    document.getElementById("calendar-title").textContent =
        currentDate.toLocaleString("default", { month: "long", year: "numeric" });

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const todayStr = new Date().toISOString().split("T")[0];

    // Empty placeholders
    for (let i = 0; i < firstDay; i++) {
        daysContainer.appendChild(document.createElement("div"));
    }

    // Generate each day
    for (let day = 1; day <= daysInMonth; day++) {
        const dayEl = document.createElement("div");
        dayEl.className = "calendar-day";

        const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

        if (dateStr === todayStr) {
            dayEl.classList.add("today");
        }

        dayEl.innerHTML = `<div class="calendar-day-number">${day}</div>`;

        // Add engagements
        engagements.forEach(e => {
            const eventMap = [
                { date: e.planningCall, type: "Planning Call" },
                { date: e.fieldworkStart, type: "Fieldwork Start" },
                { date: e.draftDue, type: "Draft Due" },
                { date: e.finalDue, type: "Final Due" }
            ];

            eventMap.forEach(ev => {
                if (ev.date === dateStr) {
                    const eventEl = document.createElement("div");
                    const typeClassMap = {
                        "Planning Call": "planning_call",
                        "Fieldwork Start": "fieldwork_start",
                        "Draft Due": "draft_due",
                        "Final Due": "final_due"
                    };
                    eventEl.className = `event ${typeClassMap[ev.type]}`;
                    eventEl.textContent = `${e.name} (${ev.type})`;
                    dayEl.appendChild(eventEl);
                }
            });
        });

        daysContainer.appendChild(dayEl);
    }
}

// Month navigation
document.getElementById("prevMonth").onclick = () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
};
document.getElementById("nextMonth").onclick = () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
};

// Show/hide calendar based on active tab
const calendarContainer = document.querySelector(".calendar");
document.querySelectorAll('button[data-bs-toggle="tab"]').forEach(tabBtn => {
    tabBtn.addEventListener('shown.bs.tab', (e) => {
        const target = e.target.getAttribute('data-bs-target');
        if (target === '#content-timeline') {
            calendarContainer.style.display = "block"; // show
            renderCalendar(); // render when tab is active
        } else {
            calendarContainer.style.display = "none"; // hide on other tabs
        }
    });
});

// Hide calendar by default if timeline tab is not active
if (!document.querySelector('#content-timeline').classList.contains('active')) {
    calendarContainer.style.display = "none";
}
</script>



</body>
</html>