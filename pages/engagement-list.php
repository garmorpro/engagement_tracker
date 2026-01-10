<?php 
// sessions_start();

require_once '../includes/functions.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List - Engagement Tracker</title>
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
            <a class="btn btn-sm me-2 tab-btn active" href="engagement-list.php">
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

    <?php include_once '../includes/status_cards.php'; ?>

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
                              <!-- <th class="text-uppercase" style="font-size: 14px;" scope="col">Draft Due</th> -->
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
                                  <?php
                                    $periodText = '—';

                                    // Case 1: Start + End exist
                                    if (!empty($eng['eng_start_period']) && !empty($eng['eng_end_period'])) {
                                        $start = date('M j, Y', strtotime($eng['eng_start_period']));
                                        $end   = date('M j, Y', strtotime($eng['eng_end_period']));
                                        $periodText = "{$start} – {$end}";

                                    // Case 2: No range, but As Of date exists
                                    } elseif (!empty($eng['eng_as_of_date'])) {
                                        $asOf = date('M j, Y', strtotime($eng['eng_as_of_date']));
                                        $periodText = "As of {$asOf}";
                                    }
                                    ?>

                                  <td><?php echo htmlspecialchars($periodText); ?></td>
                                  <!-- <td>
                                      <?php
                                        //   if (!empty($eng['eng_draft_due'])) {
                                        //       echo date('Y-m-d', strtotime($eng['eng_draft_due']));
                                        //   }
                                      ?>
                                  </td> -->
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