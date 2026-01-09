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
  
          <!-- Row 1 -->
<div class="row align-items-center" style="margin-top: 20px; margin-left: 210px; margin-right: 210px;">
  <div class="card" style="border-radius:15px;border:2px solid rgb(208,213,219);background-color:rgb(247,248,250)!important;">
    <div class="card-body">

      <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
            style="font-size:15px;padding:10px 14px;background-color:rgb(105,114,129)!important;">
        On Hold
        <?php
          $engagements = getAllEngagements($conn);
          $onHoldCount = count(array_filter($engagements, fn($e)=>$e['eng_status']==='on-hold'));
        ?>
        <span class="badge rounded-pill ms-2" style="background-color:rgb(149,156,166);color:white;">
          <?= $onHoldCount ?>
        </span>
      </span>

      <div class="kanban-column" data-status="on-hold">
        <?php foreach(array_filter($engagements,fn($e)=>$e['eng_status']==='on-hold') as $eng):
          $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d',strtotime($eng['eng_fieldwork'])) : '';
        ?>
        <div class="card engagement-card-kanban mb-2"
             draggable="true"
             data-id="<?= $eng['eng_idno'] ?>"
             style="border-radius:15px;border:1px solid rgb(208,213,219);cursor:move;">
          <a href="engagement-details.php?eng_id=<?= urlencode($eng['eng_idno']) ?>"
             class="text-decoration-none text-reset d-block">
            <div class="card-body d-flex justify-content-between align-items-center">

              <div class="d-flex gap-3 align-items-center">
                <i class="bi bi-grip-horizontal text-secondary"></i>
                <div>
                  <h5 class="mb-0" style="font-size:18px;font-weight:600;">
                    <?= htmlspecialchars($eng['eng_name']) ?>
                  </h5>
                  <span class="text-muted" style="font-size:14px;">
                    <?= htmlspecialchars($eng['eng_idno']) ?>
                  </span>
                </div>
              </div>

              <div class="d-flex gap-3 text-secondary">
                <span style="font-size:14px;"><i class="bi bi-people"></i>&nbsp;<?= htmlspecialchars($eng['eng_manager']) ?></span>
                <span style="font-size:14px;color:rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;<?= $fieldworkDate ?></span>
              </div>

            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<!-- end row 1 -->

  
          <!-- Row 2 -->
            <div class="row align-items-start g-4" style="margin-top: 1px; margin-top: px; margin-left: 200px; margin-right: 200px;">
  
              <!-- Column 1 -->
<div class="col-md-4">
  <div class="card" style="border-radius:15px;border:2px solid rgb(154,196,254);background-color:rgb(233,241,254)!important;">
    <div class="card-body">

      <span class="badge rounded-pill mb-3" style="background-color:rgb(68,125,252);padding:10px 14px;">
        Planning
      </span>

      <div class="kanban-column" data-status="planning">
        <?php foreach(array_filter($engagements,fn($e)=>$e['eng_status']==='planning') as $eng):
          $fieldworkDate = !empty($eng['eng_fieldwork']) ? date('M d, Y',strtotime($eng['eng_fieldwork'])) : '';
        ?>
        <div class="card engagement-card-kanban mb-2"
             draggable="true"
             data-id="<?= $eng['eng_idno'] ?>"
             style="background-color:rgb(249,250,251);border:1px solid rgb(208,213,219);border-radius:15px;cursor:move;">
          <a href="engagement-details.php?eng_id=<?= urlencode($eng['eng_idno']) ?>" class="text-reset text-decoration-none d-block">
            <div class="card-body">
              <h6 class="fw-bold mb-0"><?= htmlspecialchars($eng['eng_name']) ?></h6>
              <span class="text-muted"><?= htmlspecialchars($eng['eng_idno']) ?></span>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<!-- end col 1 -->

  
              <!-- Column 2 -->
                <div class="col-md-4">
  <div class="card" style="border-radius:15px;border:2px solid rgb(247,187,118);background-color:rgb(253,244,229)!important;">
    <div class="card-body">

      <span class="badge rounded-pill mb-3" style="background-color:rgb(241,115,19);padding:10px 14px;">
        In Progress
      </span>

      <div class="kanban-column" data-status="in-progress">
        <?php foreach(array_filter($engagements,fn($e)=>$e['eng_status']==='in-progress') as $eng): ?>
        <div class="card engagement-card-kanban mb-2"
             draggable="true"
             data-id="<?= $eng['eng_idno'] ?>"
             style="background-color:rgb(249,250,251);border:1px solid rgb(208,213,219);border-radius:15px;cursor:move;">
          <a href="engagement-details.php?eng_id=<?= urlencode($eng['eng_idno']) ?>" class="text-reset text-decoration-none d-block">
            <div class="card-body">
              <h6 class="fw-bold mb-0"><?= htmlspecialchars($eng['eng_name']) ?></h6>
              <span class="text-muted"><?= htmlspecialchars($eng['eng_idno']) ?></span>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

              <!-- end col 2 -->
  
              <!-- Column 3 -->
               <div class="col-md-4">
  <div class="card" style="border-radius:15px;border:2px solid rgb(212,179,254);background-color:rgb(247,241,254)!important;">
    <div class="card-body">

      <span class="badge rounded-pill mb-3" style="background-color:rgb(160,77,253);padding:10px 14px;">
        In Review
      </span>

      <div class="kanban-column" data-status="in-review">
        <?php foreach(array_filter($engagements,fn($e)=>$e['eng_status']==='in-review') as $eng): ?>
        <div class="card engagement-card-kanban mb-2"
             draggable="true"
             data-id="<?= $eng['eng_idno'] ?>"
             style="background-color:rgb(249,250,251);border:1px solid rgb(208,213,219);border-radius:15px;cursor:move;">
          <a href="engagement-details.php?eng_id=<?= urlencode($eng['eng_idno']) ?>" class="text-reset text-decoration-none d-block">
            <div class="card-body">
              <h6 class="fw-bold mb-0"><?= htmlspecialchars($eng['eng_name']) ?></h6>
              <span class="text-muted"><?= htmlspecialchars($eng['eng_idno']) ?></span>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>
</div>

              <!-- end col 3 -->
  
            </div>
          <!-- end row 2 -->
  
          <!-- Row 3 -->
            <div class="row align-items-center" style="margin-top:20px;margin-left:210px;margin-right:210px;">
  <div class="card" style="border-radius:15px;border:2px solid rgb(153,239,174);background-color:rgb(239,252,242)!important;">
    <div class="card-body">

      <span class="badge rounded-pill mb-3" style="background-color:rgb(79,198,95);padding:10px 14px;">
        Complete
      </span>

      <div class="kanban-column" data-status="complete">
        <?php foreach(array_filter($engagements,fn($e)=>$e['eng_status']==='complete') as $eng): ?>
        <div class="card engagement-card-kanban mb-2"
             draggable="true"
             data-id="<?= $eng['eng_idno'] ?>"
             style="border-radius:15px;border:1px solid rgb(208,213,219);cursor:move;">
          <a href="engagement-details.php?eng_id=<?= urlencode($eng['eng_idno']) ?>" class="text-reset text-decoration-none d-block">
            <div class="card-body d-flex justify-content-between align-items-center">
              <strong><?= htmlspecialchars($eng['eng_name']) ?></strong>
              <span class="text-muted"><?= htmlspecialchars($eng['eng_idno']) ?></span>
            </div>
          </a>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

          <!-- end row 3 -->
  
        <!-- end board sections -->


    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
document.addEventListener('DOMContentLoaded', () => {
    let draggedCard = null;

    // DRAG START / END
    document.addEventListener('dragstart', e => {
        const card = e.target.closest('.engagement-card-kanban');
        if (!card) return;

        draggedCard = card;
        e.dataTransfer.effectAllowed = 'move';
        card.classList.add('dragging');
    });

    document.addEventListener('dragend', () => {
        if (draggedCard) {
            draggedCard.classList.remove('dragging');
            draggedCard = null;
        }
    });

    // PREVENT CARDS FROM BEING DROP TARGETS
    document.addEventListener('dragover', e => {
        if (e.target.closest('.engagement-card-kanban')) {
            e.preventDefault();
            return;
        }
    });

    // HANDLE COLUMN DROPS
    document.querySelectorAll('.kanban-column').forEach(column => {
        column.addEventListener('dragover', e => {
            e.preventDefault();
            column.classList.add('drag-over');
        });

        column.addEventListener('dragleave', () => {
            column.classList.remove('drag-over');
        });

        column.addEventListener('drop', e => {
            e.preventDefault();
            column.classList.remove('drag-over');

            if (!draggedCard) return;

            // ✅ Move card permanently in DOM
            column.appendChild(draggedCard);

            const engId = draggedCard.dataset.id;
            const newStatus = column.dataset.status;

            fetch('../includes/update-engagement-status.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    eng_id: engId,
                    status: newStatus
                })
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    console.error('DB update failed:', data.message);
                }
            })
            .catch(err => console.error('Update error:', err));
        });
    });
});
</script>


</body>
</html>