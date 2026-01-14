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
                            <?php
                            $result = $conn->query("SELECT COUNT(*) AS total_engagements FROM engagements");
                            $row = $result->fetch_assoc();
                            $totalEngagements = $row['total_engagements'] ?? 0;
                            ?>

                          <h2 class="fw-bold" style="color: rgb(30,117,225);"><?php echo number_format($totalEngagements); ?></h2>
  
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
                           <?php
                            $result = $conn->query("SELECT COUNT(*) AS in_progress_engagements FROM engagements WHERE eng_status = 'in-progress'");
                            $row = $result->fetch_assoc();
                            $inProgressCount = $row['in_progress_engagements'] ?? 0;
                            ?>
                          <h2 class="fw-bold" style="color: rgb(255,92,0);"><?php echo number_format($inProgressCount); ?></h2>
  
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
                           <?php
                            $result = $conn->query("SELECT COUNT(*) AS completed_engagements FROM engagements WHERE eng_status = 'complete'");
                            $row = $result->fetch_assoc();
                            $completeCount = $row['completed_engagements'] ?? 0;
                            ?>
                          <h2 class="fw-bold" style="color: rgb(0,194,81);"><?php echo number_format($completeCount); ?></h2>
  
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
                           <?php
                            // $today = date('Y-m-d');

                            // $result = $conn->query("
                            //     SELECT COUNT(*) AS overdue_engagements 
                            //     FROM engagements 
                            //     WHERE eng_status = 'in-progress'
                            //       AND eng_final_due IS NOT NULL
                            //       AND eng_final_due < '$today'
                            // ");                         

                            // $row = $result->fetch_assoc();
                            // $overdueCount = $row['overdue_engagements'] ?? 0;
                            ?>
                          <h2 class="fw-bold" style="color: rgb(252,35,52);"><?php //echo number_format($overdueCount); ?></h2>
  
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
  
                      <?php
                        // Get the current date
                        $today = date('Y-m-d');                     

                        // Calculate the start (Sunday) and end (Saturday) of the current week
                        $startOfWeek = date('Y-m-d', strtotime('last sunday', strtotime($today)));
                        $endOfWeek   = date('Y-m-d', strtotime('next saturday', strtotime($today)));                        

                        // If today is Sunday, last sunday returns last week, so adjust
                        if (date('w', strtotime($today)) == 0) { 
                            $startOfWeek = $today;
                        }                       

                        // Query engagements with eng_final_due in this week
                        $result = $conn->query("
                            SELECT COUNT(*) AS thisWeekEngagements
                            FROM engagements
                            WHERE eng_status = 'in-progress'
                              AND eng_final_due IS NOT NULL
                              AND eng_final_due BETWEEN '$startOfWeek' AND '$endOfWeek'
                        ");                     

                        $row = $result->fetch_assoc();
                        $thisWeekCount = $row['thisWeekEngagements'] ?? 0;
                        ?>
                      <h2 class="fw-bold" style="color: rgb(172,63,255);"><?php echo number_format($thisWeekCount); ?></h2>

                      <i class="bi bi-calendar2 position-absolute" style="font-size: 5rem; top: 100px; right: -10px; color: rgba(172,63,255,0.15); z-index: 0;"></i>
  
                    </div>
                  </div>
              </div>
      
          </div>
    <!-- end status cards -->