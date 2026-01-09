<?php 
// sessions_start();

require_once '../includes/functions.php';
// require_once '../includes/update-engagement-status.php';

// Read raw POST data
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// DEBUG: include raw input in response for JS
if (!empty($data['eng_id']) && !empty($data['new_status'])) {
    $eng_id = $data['eng_id'];
    $new_status = $data['new_status'];

    $stmt = $conn->prepare("UPDATE engagements SET eng_status = ? WHERE eng_idno = ?");
    $stmt->bind_param("ss", $new_status, $eng_id);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'eng_id' => $eng_id,
            'new_status' => $new_status,
            'raw_input' => $raw
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'DB execute failed',
            'db_error' => $stmt->error,
            'raw_input' => $raw
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Missing eng_id or new_status',
        'raw_input' => $raw,
        'decoded_data' => $data
    ]);

}

$engagements = getAllEngagements($conn);

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
  
        <!-- Board sections -->
  
          <!-- Row 1: On Hold -->
<div class="row align-items-center" style="margin-top:20px; margin-left:210px; margin-right:210px;">
  <div class="card kanban-column" data-status="on-hold" style="border-radius:15px; border:2px solid rgb(208,213,219); background-color:rgb(247,248,250) !important;">
    <div class="card-body">
      <span class="badge rounded-pill mb-3" style="font-size:15px; padding:10px 14px; background-color: rgb(105,114,129) !important;">
        On Hold
        <?php $onHoldCount = count(array_filter($engagements, fn($e) => $e['eng_status']==='on-hold')); ?>
        <span class="badge rounded-pill ms-2" style="color:white !important; background-color: rgb(149,156,166) !important;">
          <?php echo $onHoldCount; ?>
        </span>
      </span>

      <?php
        $onHoldEngagements = array_filter($engagements, fn($e) => $e['eng_status']==='on-hold');
        if(count($onHoldEngagements) > 0):
          foreach($onHoldEngagements as $eng):
            $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d', strtotime($eng['eng_fieldwork'])) : '';
      ?>
      <div class="engagement-card-wrapper mb-2" data-eng-id="<?php echo htmlspecialchars($eng['eng_idno']); ?>">
        
          <div class="card engagement-card-kanban" style="border-radius:15px; border:1px solid rgb(208,213,219); cursor: grab;">
            <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno']); ?>" class="text-decoration-none text-reset d-block">
            <div class="card-body d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0" style="font-size:18px;"><?php echo htmlspecialchars($eng['eng_name']); ?></h5>
                <span style="font-size:14px; color:#6a7382;"><?php echo htmlspecialchars($eng['eng_idno']); ?></span>
              </div>
              <div class="text-secondary" style="font-size:14px;">
                <span><i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?></span><br>
                <span style="color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?></span>
                <?php if(!empty($eng['eng_audit_type'])): ?>
                  <span class="badge text-bg-secondary" style="background-color: rgba(235,236,237,1); color:#394555; font-weight:500;">
                    <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                  </span>
                <?php endif; ?>
              </div>
            </div>
            </a>
          </div>
        
      </div>
      <?php
          endforeach;
        else:
      ?>
      <div class="text-center text-muted py-4" style="border:1px dashed rgb(208,213,219); border-radius:15px;">
        Drop engagements here
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Row 2: Planning / In Progress / In Review -->
<div class="row align-items-start g-4" style="margin-top:1px; margin-left:200px; margin-right:200px;">

  <!-- Column 1: Planning -->
  <div class="col-md-4">
    <div class="card kanban-column" data-status="planning" style="border-radius:15px; border:2px solid rgb(154,196,254); background-color: rgb(233,241,254) !important;">
      <div class="card-body">
        <span class="badge rounded-pill d-inline-flex align-items-center mb-3" style="font-size:15px; padding:10px 14px; background-color: rgb(68,125,252) !important;">
          Planning
          <?php $planningCount = count(array_filter($engagements, fn($e) => $e['eng_status']==='planning')); ?>
          <span class="badge rounded-pill ms-2" style="color:white !important; background-color: rgb(123,162,249) !important;">
            <?php echo $planningCount; ?>
          </span>
        </span>

        <?php
          $planningEngagements = array_filter($engagements, fn($e) => $e['eng_status']==='planning');
          if(count($planningEngagements) > 0):
            foreach($planningEngagements as $eng):
              $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d, Y', strtotime($eng['eng_fieldwork'])) : '';
        ?>
        <div class="engagement-card-wrapper mb-2" data-eng-id="<?php echo htmlspecialchars($eng['eng_idno']); ?>">
         
            <div class="card engagement-card-kanban" style="background-color: rgb(249,250,251); border:1px solid rgb(208,213,219); border-radius:15px; cursor: grab;">
               <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno']); ?>" class="text-decoration-none text-reset d-block">
              <div class="card-body d-flex flex-column" style="margin-bottom:-15px !important;">
                <div class="d-flex align-items-center justify-content-between" style="margin-top:-5px !important;">
                  <h6 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($eng['eng_name']); ?></h6>
                  <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                </div>
                <div class="text-secondary" style="font-size:14px; margin-top:5px;">
                  <div><?php echo htmlspecialchars($eng['eng_idno']); ?></div>
                  <div><i class="bi bi-people"></i> <?php echo htmlspecialchars($eng['eng_manager']); ?></div>
                  <div style="color: rgb(243,36,57);"><i class="bi bi-calendar2"></i> <?php echo $fieldworkDate; ?></div>
                  <?php if(!empty($eng['eng_audit_type'])): ?>
                  <div class="tags pt-2">
                    <span class="badge text-bg-secondary" style="background-color: rgba(235,236,237,1); color:#394555; font-weight:500;">
                      <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                    </span>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              </a>
            </div>
          
        </div>
        <?php
            endforeach;
          else:
        ?>
        <div class="text-center text-muted py-4" style="border:1px dashed rgb(208,213,219); border-radius:15px;">
          Drop engagements here
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Column 2: In Progress -->
  <div class="col-md-4">
    <div class="card kanban-column" data-status="in-progress" style="border-radius:15px; border:2px solid rgb(247,187,118); background-color: rgb(253,244,229) !important;">
      <div class="card-body">
        <span class="badge rounded-pill d-inline-flex align-items-center mb-3" style="font-size:15px; padding:10px 14px; background-color: rgb(241,115,19) !important;">
          In Progress
          <?php $inProgressCount = count(array_filter($engagements, fn($e) => $e['eng_status']==='in-progress')); ?>
          <span class="badge rounded-pill ms-2" style="color:white !important; background-color: rgb(243,155,89) !important;">
            <?php echo $inProgressCount; ?>
          </span>
        </span>

        <?php
          $inProgressEngagements = array_filter($engagements, fn($e) => $e['eng_status']==='in-progress');
          if(count($inProgressEngagements) > 0):
            foreach($inProgressEngagements as $eng):
              $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d, Y', strtotime($eng['eng_fieldwork'])) : '';
        ?>
        <div class="engagement-card-wrapper mb-2" data-eng-id="<?php echo htmlspecialchars($eng['eng_idno']); ?>">
          <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno']); ?>" class="text-decoration-none text-reset d-block">
            <div class="card engagement-card-kanban" style="background-color: rgb(249,250,251); border:1px solid rgb(208,213,219); border-radius:15px; cursor: grab;">
              <div class="card-body d-flex flex-column" style="margin-bottom:-15px !important;">
                <div class="d-flex align-items-center justify-content-between" style="margin-top:-5px !important;">
                  <h6 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($eng['eng_name']); ?></h6>
                  <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                </div>
                <div class="text-secondary" style="font-size:14px; margin-top:5px;">
                  <div><?php echo htmlspecialchars($eng['eng_idno']); ?></div>
                  <div><i class="bi bi-people"></i> <?php echo htmlspecialchars($eng['eng_manager']); ?></div>
                  <div style="color: rgb(243,36,57);"><i class="bi bi-calendar2"></i> <?php echo $fieldworkDate; ?></div>
                  <?php if(!empty($eng['eng_audit_type'])): ?>
                  <div class="tags pt-2">
                    <span class="badge text-bg-secondary" style="background-color: rgba(235,236,237,1); color:#394555; font-weight:500;">
                      <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                    </span>
                  </div>
                  <?php endif; ?>
                  <?php if(!empty($eng['eng_final_due']) && $eng['eng_final_due'] < date('Y-m-d')): ?>
                    <span class="badge text-bg-danger" style="background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight:500;">
                      Overdue
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </a>
        </div>
        <?php
            endforeach;
          else:
        ?>
        <div class="text-center text-muted py-4" style="border:1px dashed rgb(208,213,219); border-radius:15px;">
          Drop engagements here
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Column 3: In Review -->
  <div class="col-md-4">
    <div class="card kanban-column" data-status="in-review" style="border-radius:15px; border:2px solid rgb(212,179,254); background-color: rgb(247,241,254) !important;">
      <div class="card-body">
        <span class="badge rounded-pill d-inline-flex align-items-center mb-3" style="font-size:15px; padding:10px 14px; background-color: rgb(160,77,253) !important;">
          In Review
          <?php $inReviewCount = count(array_filter($engagements, fn($e) => $e['eng_status']==='in-review')); ?>
          <span class="badge rounded-pill ms-2" style="color:white !important; background-color: rgb(188,129,251) !important;">
            <?php echo $inReviewCount; ?>
          </span>
        </span>

        <?php
          $inReviewEngagements = array_filter($engagements, fn($e) => $e['eng_status']==='in-review');
          if(count($inReviewEngagements) > 0):
            foreach($inReviewEngagements as $eng):
              $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d, Y', strtotime($eng['eng_fieldwork'])) : '';
        ?>
        <div class="engagement-card-wrapper mb-2" data-eng-id="<?php echo htmlspecialchars($eng['eng_idno']); ?>">
          
            <div class="card engagement-card-kanban" style="background-color: rgb(249,250,251); border:1px solid rgb(208,213,219); border-radius:15px; cursor: grab;">
              <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno']); ?>" class="text-decoration-none text-reset d-block">
              <div class="card-body d-flex flex-column" style="margin-bottom:-15px !important;">
                <div class="d-flex align-items-center justify-content-between" style="margin-top:-5px !important;">
                  <h6 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($eng['eng_name']); ?></h6>
                  <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                </div>
                <div class="text-secondary" style="font-size:14px; margin-top:5px;">
                  <div><?php echo htmlspecialchars($eng['eng_idno']); ?></div>
                  <div><i class="bi bi-people"></i> <?php echo htmlspecialchars($eng['eng_manager']); ?></div>
                  <div style="color: rgb(243,36,57);"><i class="bi bi-calendar2"></i> <?php echo $fieldworkDate; ?></div>
                  <?php if(!empty($eng['eng_audit_type'])): ?>
                  <div class="tags pt-2">
                    <span class="badge text-bg-secondary" style="background-color: rgba(235,236,237,1); color:#394555; font-weight:500;">
                      <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                    </span>
                  </div>
                  <?php endif; ?>
                  <?php if(!empty($eng['eng_final_due']) && $eng['eng_final_due'] < date('Y-m-d')): ?>
                    <span class="badge text-bg-danger" style="background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight:500;">
                      Overdue
                    </span>
                  <?php endif; ?>
                </div>
              </div>
              </a>
            </div>
          
        </div>
        <?php
            endforeach;
          else:
        ?>
        <div class="text-center text-muted py-4" style="border:1px dashed rgb(208,213,219); border-radius:15px;">
          Drop engagements here
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- Row 3: Complete -->
<div class="row align-items-center" style="margin-top:20px; margin-left:210px; margin-right:210px;">
  <div class="card kanban-column" data-status="complete" style="border-radius:15px; border:2px solid rgb(153,239,174); background-color: rgb(239,252,242) !important;">
    <div class="card-body">
      <span class="badge rounded-pill d-inline-flex align-items-center mb-3" style="font-size:15px; padding:10px 14px; background-color: rgb(79,198,95) !important;">
        Complete
        <?php $completeCount = count(array_filter($engagements, fn($e) => $e['eng_status']==='complete')); ?>
        <span class="badge rounded-pill ms-2" style="color:white !important; background-color: rgb(130,212,141) !important;">
          <?php echo $completeCount; ?>
        </span>
      </span>

      <?php
        $completeEngagements = array_filter($engagements, fn($e) => $e['eng_status']==='complete');
        if(count($completeEngagements) > 0):
          foreach($completeEngagements as $eng):
            $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d', strtotime($eng['eng_fieldwork'])) : '';
      ?>
      <div class="engagement-card-wrapper mb-2" data-eng-id="<?php echo htmlspecialchars($eng['eng_idno']); ?>">
        
          <div class="card engagement-card-kanban" style="border-radius:15px; border:1px solid rgb(208,213,219); cursor: grab;">
            <a href="engagement-details.php?eng_id=<?php echo urlencode($eng['eng_idno']); ?>" class="text-decoration-none text-reset d-block">
            <div class="card-body d-flex align-items-center justify-content-between">
              <div class="left d-flex align-items-center gap-3">
                <i class="bi bi-grip-horizontal text-secondary"></i>
                <div>
                  <h5 class="mb-0" style="font-size:18px; font-weight:600;"><?php echo htmlspecialchars($eng['eng_name']); ?></h5>
                  <span class="text-muted" style="font-size:14px;"><?php echo htmlspecialchars($eng['eng_idno']); ?></span>
                </div>
              </div>
              <div class="right d-flex align-items-center gap-3 text-secondary">
                <span style="font-size:14px;"><i class="bi bi-people"></i>&nbsp;<?php echo htmlspecialchars($eng['eng_manager']); ?></span>
                <span style="font-size:14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;<?php echo $fieldworkDate; ?></span>
                <?php if(!empty($eng['eng_audit_type'])): ?>
                  <span class="badge text-bg-secondary" style="background-color: rgba(235,236,237,1); color:#394555; font-weight:500;">
                    <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                  </span>
                <?php endif; ?>
                <?php if(!empty($eng['eng_final_due']) && $eng['eng_final_due'] < date('Y-m-d')): ?>
                  <span class="badge text-bg-danger" style="background-color: rgb(255,226,226); color: rgb(201,0,18); font-weight:500;">
                    Overdue
                  </span>
                <?php endif; ?>
              </div>
            </div>
             </a>
          </div>
       
      </div>
      <?php
          endforeach;
        else:
      ?>
      <div class="text-center text-muted py-4" style="border:1px dashed rgb(208,213,219); border-radius:15px;">
        Drop engagements here
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

  
        <!-- end board sections -->


    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
  <!-- <script src="../assets/js/sortable.js"></script> -->

  <script>
document.addEventListener('DOMContentLoaded', () => {
  const columns = document.querySelectorAll('.kanban-column');

  columns.forEach(column => {
    new Sortable(column, {
      group: 'kanban',
      animation: 150,
      ghostClass: 'kanban-ghost',
      handle: '.engagement-card-kanban',
      draggable: '.engagement-card-wrapper',
      onEnd: function(evt) {
        const wrapper = evt.item;
        const engId = wrapper.dataset.engId;
        const newStatus = evt.to.dataset.status;

        if (!engId || !newStatus) return;

        fetch('includes/update-engagement-status.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ eng_id: engId, new_status: newStatus })
        })
        .then(res => res.json())
        .then(data => console.log('Server response:', data))
        .catch(err => console.error('Fetch error:', err));
      }
    });
  });
});

</script>


</body>
</html>