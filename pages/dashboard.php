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
  
    <!-- Board sections -->
  
        <!-- Row 1 -->
            <div class="row align-items-center" style="margin-top: 20px; margin-left: 210px; margin-right: 210px;">

                <div class="card" style="border-radius: 15px; border: 2px solid rgb(208,213,219); background-color: rgb(247,248,250) !important;">
                    <div class="card-body">

                        <!-- Header badge -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(105,114,129) !important;">
                            On Hold
                            <?php
                            $engagements = getAllEngagements($conn);
                            $onHoldCount = count(array_filter($engagements, fn($eng) => $eng['eng_status'] === 'on-hold'));
                            ?>
                            <span class="badge rounded-pill ms-2"
                                  style="color: white !important; background-color: rgb(149,156,166) !important;">
                                <?php echo $onHoldCount; ?>
                            </span>
                        </span>

                        <!-- ✅ DROP ZONE -->
                        <div class="kanban-column" data-status="on-hold">

                            <?php
                            $onHoldEngagements = array_filter($engagements, fn($eng) => $eng['eng_status'] === 'on-hold');

                            if (count($onHoldEngagements) > 0):
                                foreach ($onHoldEngagements as $eng):
                                    $fieldworkDate = !empty($eng['eng_fieldwork'])
                                        ? date('M d', strtotime($eng['eng_fieldwork']))
                                        : '';
                            ?>

                            <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno']); ?>"
                               class="text-decoration-none text-reset d-block">
                                
                                <!-- ✅ DRAGGABLE CARD -->
                                <div class="card engagement-card-kanban mb-2"
                                     draggable="true"
                                     data-id="<?php echo $eng['eng_idno']; ?>"
                                     data-name="<?php echo htmlspecialchars($eng['eng_name'] ?? ''); ?>"
                                     data-engno="<?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?>"
                                     data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                                     data-fieldwork="<?php echo $fieldworkDate; ?>"
                                     data-audit="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? ''); ?>"
                                     data-final-due="<?php echo htmlspecialchars($eng['eng_final_due'] ?? ''); ?>"
                                     style="border-radius: 15px; border: 1px solid rgb(208,213,219); cursor: move;">
                                
                                    <div class="card-body d-flex align-items-center justify-content-between">
                                
                                        <!-- LEFT -->
                                        <div class="d-flex align-items-center gap-3">
                                            <i class="bi bi-grip-horizontal text-secondary"></i>
                                            <div>
                                                <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">
                                                    <?php echo htmlspecialchars($eng['eng_name']); ?>
                                                </h5>
                                                <span class="text-muted" style="font-size: 14px;">
                                                    <?php echo htmlspecialchars($eng['eng_idno']); ?>
                                                </span>
                                            </div>
                                        </div>
                                
                                        <!-- RIGHT -->
                                        <div class="d-flex align-items-center gap-3 text-secondary">
                                            <span style="font-size: 14px;">
                                                <i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?>
                                            </span>
                                
                                            <span style="font-size: 14px; color: rgb(243,36,57);">
                                                <i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?>
                                            </span>
                                
                                            <?php if (!empty($eng['eng_audit_type'])): ?>
                                                <span class="badge"
                                                      style="background-color: rgba(235,236,237,1); color: rgb(57,69,85); font-weight: 500;">
                                                    <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                                </span>
                                            <?php endif; ?>
                                            
                                            <?php
                                            $today = date('Y-m-d');
                                            if (!empty($eng['eng_final_due']) && $eng['eng_final_due'] < $today):
                                            ?>
                                                <span class="badge"
                                                      style="background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight: 500;">
                                                    Overdue
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                            
                                    </div>
                                </div>
                            </a>
                                            
                            <?php endforeach; else: ?>
                            
                                <!-- Empty state -->
                                <div class="text-center text-muted py-4 empty-placeholder"
                                     style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                            
                            <?php endif; ?>
                            
                        </div><!-- end kanban-column -->
                            
                    </div>
                </div>
            </div>
        <!-- end row 1 -->

  
        <!-- Row 2 -->
            <div class="row align-items-start g-4" style="margin-top: 1px; margin-top: px; margin-left: 200px; margin-right: 200px;">
  
            <!-- Column 1 -->
                <div class="col-md-4">

                    <div class="card"
                         style="border-radius: 15px; border: 2px solid rgb(154,196,254); background-color: rgb(233,241,254) !important;">
                        <div class="card-body">

                            <!-- Header badge -->
                            <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                                  style="font-size: 15px; padding: 10px 14px; background-color: rgb(68,125,252) !important;">
                                Planning
                                <?php
                                $engagements = getAllEngagements($conn);
                                $planningCount = count(array_filter($engagements, fn($eng) => $eng['eng_status'] === 'planning'));
                                ?>
                                <span class="badge rounded-pill ms-2"
                                      style="color: white !important; background-color: rgb(123,162,249) !important;">
                                    <?php echo $planningCount; ?>
                                </span>
                            </span>

                            <!-- ✅ DROP ZONE -->
                            <div class="kanban-column" data-status="planning">

                                <?php
                                $planningEngagements = array_filter($engagements, fn($eng) => $eng['eng_status'] === 'planning');

                                if (count($planningEngagements) > 0):
                                    foreach ($planningEngagements as $eng):
                                        $fieldworkDate = !empty($eng['eng_fieldwork'])
                                            ? date('M d, Y', strtotime($eng['eng_fieldwork']))
                                            : '';
                                ?>

                                <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno']); ?>"
                                   class="text-decoration-none text-reset d-block">
                                    
                                    <!-- ✅ DRAGGABLE CARD -->
                                    <div class="card engagement-card-kanban mb-2"
                                         draggable="true"
                                         data-id="<?php echo $eng['eng_idno'] ?? ''; ?>"
                                         data-name="<?php echo htmlspecialchars($eng['eng_name'] ?? ''); ?>"
                                         data-engno="<?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?>"
                                         data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                                         data-fieldwork="<?php echo $fieldworkDate; ?>"
                                         data-audit="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? ''); ?>"
                                         data-final-due="<?php echo htmlspecialchars($eng['eng_final_due'] ?? ''); ?>"
                                         style="background-color: rgb(249,250,251);
                                                border: 1px solid rgb(208,213,219);
                                                border-radius: 15px;
                                                cursor: move;">

                                        <div class="card-body" style="margin-bottom: -15px !important;">
                                    
                                            <!-- Title row -->
                                            <div class="d-flex align-items-center justify-content-between"
                                                 style="margin-top: -5px !important;">
                                                <h6 class="card-title fw-bold mb-0">
                                                    <?php echo htmlspecialchars($eng['eng_name']); ?>
                                                </h6>
                                                <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                                            </div>
                                    
                                            <!-- Subtext -->
                                            <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                                                <span style="color: rgb(106,115,130); font-size: 14px;">
                                                    <?php echo htmlspecialchars($eng['eng_idno']); ?>
                                                </span><br>
                                    
                                                <div class="pb-2"></div>
                                    
                                                <span style="font-size: 14px;">
                                                    <i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?>
                                                </span><br>
                                    
                                                <span style="font-size: 14px; color: rgb(243,36,57);">
                                                    <i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?>
                                                </span><br>
                                    
                                                <div class="tags pt-2">
                                                    <?php if (!empty($eng['eng_audit_type'])): ?>
                                                        <span class="badge"
                                                              style="background-color: rgba(235,236,237,1);
                                                                     color: rgb(57,69,85);
                                                                     font-weight: 500;">
                                                            <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </p>
                                                    
                                        </div>
                                    </div>
                                </a>
                                                    
                                <?php endforeach; else: ?>
                                
                                    <!-- Empty state -->
                                    <div class="text-center text-muted py-4 empty-placeholder"
                                         style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                        Drop engagements here
                                    </div>
                                
                                <?php endif; ?>
                                
                            </div><!-- end kanban-column -->
                                
                        </div>
                    </div>
                                
                </div>
            <!-- end col 1 -->

  
            <!-- Column 2 -->
                <div class="col-md-4">

                    <div class="card" style="border-radius: 15px; border: 2px solid rgb(247,187,118); background-color: rgb(253,244,229) !important;">
                        <div class="card-body">

                            <!-- Header badge -->
                            <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                                  style="font-size: 15px; padding: 10px 14px; background-color: rgb(241,115,19) !important;">
                                In Progress
                                <?php
                                $engagements = getAllEngagements($conn);
                                $inProgressCount = count(array_filter($engagements, fn($eng) => $eng['eng_status'] === 'in-progress'));
                                ?>
                                <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(243,155,89) !important;">
                                    <?php echo $inProgressCount; ?>
                                </span>
                            </span>

                            <!-- ✅ DROP ZONE -->
                            <div class="kanban-column" data-status="in-progress">

                                <?php
                                $inProgressEngagements = array_filter($engagements, fn($eng) => $eng['eng_status'] === 'in-progress');

                                if (count($inProgressEngagements) > 0):
                                    foreach ($inProgressEngagements as $eng):
                                        $fieldworkDate = !empty($eng['eng_fieldwork'])
                                            ? date('M d, Y', strtotime($eng['eng_fieldwork']))
                                            : '';
                                ?>

                                <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno'] ?? ''); ?>"
                                   class="text-decoration-none text-reset d-block">
                                    
                                    <!-- ✅ DRAGGABLE CARD -->
                                    <div class="card engagement-card-kanban mb-2"
                                         draggable="true"
                                         data-id="<?php echo $eng['eng_idno'] ?? ''; ?>"
                                         data-name="<?php echo htmlspecialchars($eng['eng_name'] ?? ''); ?>"
                                         data-engno="<?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?>"
                                         data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                                         data-fieldwork="<?php echo $fieldworkDate; ?>"
                                         data-audit="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? ''); ?>"
                                         data-final-due="<?php echo htmlspecialchars($eng['eng_final_due'] ?? ''); ?>"
                                         style="background-color: rgb(249,250,251);
                                                border: 1px solid rgb(208,213,219);
                                                border-radius: 15px;
                                                cursor: move;">

                                        <div class="card-body" style="margin-bottom: -15px !important;">
                                    
                                            <!-- Title row -->
                                            <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                                                <h6 class="card-title fw-bold mb-0">
                                                    <?php echo htmlspecialchars($eng['eng_name'] ?? ''); ?>
                                                </h6>
                                                <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                                            </div>
                                    
                                            <!-- Subtext -->
                                            <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                                                <span style="color: rgb(106,115,130); font-size: 14px !important;">
                                                    <?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?>
                                                </span><br>
                                    
                                                <div class="pb-2"></div>
                                    
                                                <span style="font-size: 14px;">
                                                    <i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>
                                                </span><br>
                                    
                                                <span style="font-size: 14px; color: rgb(243,36,57);">
                                                    <i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?>
                                                </span><br>
                                    
                                                <div class="tags pt-2">
                                                    <?php if (!empty($eng['eng_audit_type'])): ?>
                                                        <span class="badge"
                                                              style="background-color: rgba(235,236,237,1); color: rgb(57,69,85); font-weight: 500;">
                                                            <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    
                                                    <?php
                                                    $today = date('Y-m-d');
                                                    if (!empty($eng['eng_final_due']) && $eng['eng_final_due'] < $today):
                                                    ?>
                                                        <span class="badge"
                                                              style="background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight: 500;">
                                                            Overdue
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </p>
                                                    
                                        </div>
                                    </div>
                                </a>
                                                    
                                <?php
                                    endforeach;
                                else:
                                ?>

                                <!-- Empty state -->
                                <div class="text-center text-muted py-4 empty-placeholder"
                                     style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                                    Drop engagements here
                                </div>
                                
                                <?php endif; ?>
                                
                            </div><!-- end kanban-column -->
                                
                        </div>
                    </div>
                </div> 
            <!-- end col 2 -->

            <!-- Column 3 -->
                <div class="col-md-4">

                  <div class="card" style="border-radius: 15px; border: 2px solid rgb(212,179,254); background-color: rgb(247,241,254) !important;">
                    <div class="card-body">

                      <!-- Header badge -->
                      <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                            style="font-size: 15px; padding: 10px 14px; background-color: rgb(160,77,253) !important;">
                        In Review
                        <?php
                        $engagements = getAllEngagements($conn);
                        $inReviewCount = count(array_filter($engagements, fn($eng) => $eng['eng_status'] === 'in-review'));
                        ?>
                        <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(188,129,251) !important;">
                          <?php echo $inReviewCount; ?>
                        </span>
                      </span>

                      <!-- Kanban column -->
                      <div class="kanban-column" data-status="in-review">

                        <?php
                        $inReviewEngagements = array_filter($engagements, fn($eng) => $eng['eng_status'] === 'in-review');

                        if (count($inReviewEngagements) > 0):
                          foreach ($inReviewEngagements as $eng):
                            $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d, Y', strtotime($eng['eng_fieldwork'])) : '';
                        ?>
                        <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno']); ?>" class="text-decoration-none text-reset d-block">
                          <div class="card engagement-card-kanban mb-2"
                               draggable="true"
                               data-id="<?php echo $eng['eng_idno'] ?? ''; ?>"
                               data-name="<?php echo htmlspecialchars($eng['eng_name'] ?? ''); ?>"
                               data-engno="<?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?>"
                               data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                               data-fieldwork="<?php echo $fieldworkDate; ?>"
                               data-audit="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? ''); ?>"
                               data-final-due="<?php echo htmlspecialchars($eng['eng_final_due'] ?? ''); ?>"
                               style="background-color: rgb(249,250,251); border: 1px solid rgb(208,213,219); border-radius: 15px; cursor: move;">
                        
                            <div class="card-body" style="margin-bottom: -15px !important;">
                        
                              <!-- Title row -->
                              <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                                <h6 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($eng['eng_name']); ?></h6>
                                <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                              </div>
                        
                              <!-- Subtext -->
                              <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                                <span style="color: rgb(106,115,130); font-size: 14px;"><?php echo htmlspecialchars($eng['eng_idno']); ?></span><br>
                                <div class="pb-2"></div>
                                <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?></span><br>
                                <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?></span><br>
                        
                                <div class="tags pt-2">
                                  <?php if (!empty($eng['eng_audit_type'])): ?>
                                    <span class="badge"
                                          style="background-color: rgba(235,236,237,1); color: rgb(57,69,85); font-weight: 500;">
                                      <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                    </span>
                                  <?php endif; ?>
                                
                                  <?php
                                  $today = date('Y-m-d');
                                  if (!empty($eng['eng_final_due']) && $eng['eng_final_due'] < $today):
                                  ?>
                                    <span class="badge"
                                          style="background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight: 500; margin-left: 5px;">
                                      Overdue
                                    </span>
                                  <?php endif; ?>
                                </div>
                                
                              </p>
                            </div>
                          </div>
                        </a>
                        <?php
                          endforeach;
                        else:
                        ?>
                          <div class="text-center text-muted py-4 empty-placeholder" style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                            Drop engagements here
                          </div>
                        <?php endif; ?>
                        
                      </div><!-- end kanban-column -->
                        
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

            <!-- Header badge -->
            <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                  style="font-size: 15px; padding: 10px 14px; background-color: rgb(79,198,95) !important;">
                Complete
                <?php
                $engagements = getAllEngagements($conn);
                $completeCount = count(array_filter($engagements, fn($eng) => $eng['eng_status'] === 'complete'));
                ?>
                <span class="badge rounded-pill ms-2"
                      style="color: white !important; background-color: rgb(130,212,141) !important;">
                    <?php echo $completeCount; ?>
                </span>
            </span>

            <!-- ✅ DROP ZONE -->
            <div class="kanban-column" data-status="complete">

                <?php
                $completeEngagements = array_filter($engagements, fn($eng) => $eng['eng_status'] === 'complete');

                if (count($completeEngagements) > 0):
                    foreach ($completeEngagements as $eng):
                        $fieldworkDate = !empty($eng['eng_fieldwork'])
                            ? date('M d', strtotime($eng['eng_fieldwork']))
                            : '';
                ?>

                <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno']); ?>"
                   class="text-decoration-none text-reset d-block position-relative engagement-card-wrapper">
                    
                    <!-- ✅ DRAGGABLE CARD -->
                    <div class="card engagement-card-kanban mb-2"
                         draggable="true"
                         data-id="<?php echo $eng['eng_idno']; ?>"
                         data-name="<?php echo htmlspecialchars($eng['eng_name'] ?? ''); ?>"
                         data-engno="<?php echo htmlspecialchars($eng['eng_idno'] ?? ''); ?>"
                         data-manager="<?php echo htmlspecialchars($eng['eng_manager'] ?? ''); ?>"
                         data-fieldwork="<?php echo $fieldworkDate; ?>"
                         data-audit="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? ''); ?>"
                         data-final-due="<?php echo htmlspecialchars($eng['eng_final_due'] ?? ''); ?>"
                         style="border-radius: 15px; border: 1px solid rgb(208,213,219); cursor: move; position: relative;">
                    
                        <div class="card-body d-flex align-items-center justify-content-between">
                    
                            <!-- LEFT -->
                            <div class="d-flex align-items-center gap-3">
                                <i class="bi bi-grip-horizontal text-secondary"></i>
                                <div>
                                    <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">
                                        <?php echo htmlspecialchars($eng['eng_name']); ?>
                                    </h5>
                                    <span class="text-muted" style="font-size: 14px;">
                                        <?php echo htmlspecialchars($eng['eng_idno']); ?>
                                    </span>
                                </div>
                            </div>
                    
                            <!-- RIGHT -->
                            <div class="d-flex align-items-center gap-3 text-secondary">
                                <span style="font-size: 14px;">
                                    <i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?>
                                </span>
                    
                                <span style="font-size: 14px; color: rgb(243,36,57);">
                                    <i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?>
                                </span>
                    
                                <?php if (!empty($eng['eng_audit_type'])): ?>
                                    <span class="badge"
                                          style="background-color: rgba(235,236,237,1); color: rgb(57,69,85); font-weight: 500;">
                                        <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                    </span>
                                <?php endif; ?>
                                
                                <?php
                                $today = date('Y-m-d');
                                if (!empty($eng['eng_final_due']) && $eng['eng_final_due'] < $today):
                                ?>
                                    <span class="badge"
                                          style="background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight: 500;">
                                        Overdue
                                    </span>
                                <?php endif; ?>
                            </div>
                                
                        </div>

                        <!-- Archive Button -->
    <!-- Archive Button -->
    <button type="button" class="btn btn-sm btn-outline-danger archive-btn"
            onclick="archiveEngagement('...')">
        <i class="bi bi-archive"></i> Archive
    </button>

                    </div>
                </a>
                                
                <?php endforeach; else: ?>
                
                    <!-- Empty state -->
                    <div class="text-center text-muted py-4 empty-placeholder"
                         style="border: 1px dashed rgb(208,213,219); border-radius: 15px;">
                        Drop engagements here
                    </div>
                
                <?php endif; ?>
                
            </div><!-- end kanban-column -->
                
        </div>
    </div>
</div>
<!-- end row 3 -->



  
    <!-- end board sections -->


    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    let draggedWrapper = null;

    const layoutMap = {
        'on-hold': 'horizontal',
        'planning': 'vertical',
        'in-progress': 'vertical',
        'in-review': 'vertical',
        'complete': 'horizontal'
    };

    // -----------------------------
    // Helper: Archive button
    // -----------------------------
    const archiveEngagement = (engId) => {
        if (!confirm("Archive this engagement?")) return;

        fetch('../includes/archive-engagement.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ eng_id: engId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const card = document.querySelector(`.engagement-card-kanban[data-id='${engId}']`);
                if (card) card.remove();
            } else {
                alert('Failed to archive engagement.');
            }
        })
        .catch(() => alert('Failed to archive engagement.'));
    };

    // -----------------------------
    // Helper: Add archive button to card
    // -----------------------------
    const addArchiveButton = (card, engId) => {
        const btn = document.createElement('button');
        btn.className = 'btn btn-sm btn-outline-danger archive-btn';
        btn.style.cssText = 'position:absolute; top:8px; right:8px; display:none; z-index:10;';
        btn.innerHTML = '<i class="bi bi-archive"></i> Archive';
        btn.onclick = (e) => {
            e.stopPropagation(); // prevent link click if card is clickable
            archiveEngagement(engId);
        };
        card.appendChild(btn);
    };

    // -----------------------------
    // Update badge count & placeholder
    // -----------------------------
    const updateColumnUI = (column) => {
        const cards = column.querySelectorAll('.engagement-card-kanban');
        const count = cards.length;

        const badge = column.closest('.card')?.querySelector('.count-badge');
        if (badge) badge.textContent = count;

        const placeholder = column.querySelector('.empty-placeholder');
        if (placeholder) placeholder.style.display = count === 0 ? 'block' : 'none';
    };

    // -----------------------------
    // Apply layout styles
    // -----------------------------
    const applyLayoutStyles = (card, status) => {
        const body = card.querySelector('.card-body');
        if (!body) return;

        if (status === 'on-hold' || status === 'complete') {
            card.style.width = 'auto';
            body.classList.add('d-flex', 'align-items-center', 'justify-content-between');
            body.style.marginBottom = '';
        } else {
            card.style.width = '100%';
            body.classList.remove('d-flex', 'align-items-center', 'justify-content-between');
            body.style.marginBottom = status === 'planning' ? '-15px' : '';
        }
    };

    // -----------------------------
    // Drag events
    // -----------------------------
    const attachDragEvents = (card) => {
        card.setAttribute('draggable', 'true');

        card.addEventListener('dragstart', e => {
            draggedWrapper = card;
            card.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        });

        card.addEventListener('dragend', () => {
            card.classList.remove('dragging');
            draggedWrapper = null;
        });
    };

    // -----------------------------
    // Create card functions
    // -----------------------------
    const createCard = (data, status) => {
        const { id, name, engno, manager, fieldwork, audit, finalDue } = data;

        const card = document.createElement('div');
        card.className = 'card engagement-card-kanban mb-2';
        card.dataset.id = id;
        card.dataset.name = name;
        card.dataset.engno = engno;
        card.dataset.manager = manager;
        card.dataset.fieldwork = fieldwork;
        card.dataset.audit = audit;
        card.dataset.finalDue = finalDue;
        card.style.cssText = 'position:relative; cursor:move; border-radius:15px; border:1px solid rgb(208,213,219);';

        const body = document.createElement('div');
        body.className = 'card-body d-flex align-items-center justify-content-between';
        body.innerHTML = `
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-grip-horizontal text-secondary"></i>
                <div>
                    <h5 class="mb-0" style="font-size:18px; font-weight:600;">${name}</h5>
                    <span class="text-muted" style="font-size:14px;">${engno}</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-3 text-secondary">
                <span style="font-size:14px;"><i class="bi bi-people"></i>&nbsp;${manager}</span>
                <span style="font-size:14px; color:rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;${fieldwork}</span>
            </div>
        `;

        if (audit) {
            const badge = document.createElement('span');
            badge.className = 'badge';
            badge.style.cssText = 'background-color: rgba(235,236,237,1); color: rgb(57,69,85); font-weight:500;';
            badge.textContent = audit;
            body.querySelector('div:last-child').appendChild(badge);
        }

        if (finalDue && new Date(finalDue) < new Date()) {
            const overdue = document.createElement('span');
            overdue.className = 'badge';
            overdue.style.cssText = 'background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight:500;';
            overdue.textContent = 'Overdue';
            body.querySelector('div:last-child').appendChild(overdue);
        }

        card.appendChild(body);

        if (status === 'complete') addArchiveButton(card, id);

        attachDragEvents(card);

        return card;
    };

    // -----------------------------
    // Initialize cards from DOM
    // -----------------------------
    document.querySelectorAll('.engagement-card-kanban').forEach(card => {
        attachDragEvents(card);

        // Add archive button if complete
        const parentColumn = card.closest('.kanban-column');
        if (parentColumn && parentColumn.dataset.status === 'complete') {
            const engId = card.dataset.id;
            addArchiveButton(card, engId);
        }
    });

    // -----------------------------
    // Drag & Drop logic
    // -----------------------------
    document.querySelectorAll('.kanban-column').forEach(column => {
        column.addEventListener('dragover', e => { e.preventDefault(); column.classList.add('drag-over'); });
        column.addEventListener('dragleave', () => column.classList.remove('drag-over'));
        column.addEventListener('drop', e => {
            e.preventDefault();
            column.classList.remove('drag-over');
            if (!draggedWrapper) return;

            const originalParent = draggedWrapper.parentElement;
            const newStatus = column.dataset.status;
            const data = { ...draggedWrapper.dataset };

            // Create new card based on layout
            const newCard = createCard(data, newStatus);
            draggedWrapper.remove();
            column.appendChild(newCard);

            applyLayoutStyles(newCard, newStatus);
            [column, originalParent].forEach(updateColumnUI);

            // Update DB
            fetch('../includes/update-engagement-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ eng_id: newCard.dataset.id, status: newStatus })
            }).then(res => res.json()).then(data => {
                if (!data.success) {
                    originalParent.appendChild(newCard);
                    applyLayoutStyles(newCard, originalParent.dataset.status);
                    [column, originalParent].forEach(updateColumnUI);
                    alert('Failed to update DB. Reverted.');
                }
            }).catch(() => {
                originalParent.appendChild(newCard);
                applyLayoutStyles(newCard, originalParent.dataset.status);
                [column, originalParent].forEach(updateColumnUI);
                alert('Failed to update DB. Reverted.');
            });
        });
    });

    // -----------------------------
    // Initialize columns
    // -----------------------------
    document.querySelectorAll('.kanban-column').forEach(column => {
        column.querySelectorAll('.engagement-card-kanban').forEach(card => {
            applyLayoutStyles(card, column.dataset.status);
        });
        updateColumnUI(column);
    });
});
</script>

<style>
/* Show Archive button on hover */
.engagement-card-kanban .archive-btn {
    display: none;
}
.engagement-card-kanban:hover .archive-btn {
    display: inline-block;
}
</style>





<script>
function archiveEngagement(engId) {
    if (!confirm('Are you sure you want to archive this engagement?')) return;

    // Example AJAX request to archive
    fetch('../includes/archive-engagement.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ eng_id: engId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Engagement archived!');
            location.reload(); // or remove card from DOM
        } else {
            alert('Failed to archive engagement.');
        }
    })
    .catch(err => console.error(err));
}
</script>






</body>
</html>