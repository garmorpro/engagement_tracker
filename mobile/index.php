<?php

require_once '../includes/functions.php';
$engagements = getAllActiveEngagementsMobile($conn);


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">


<!-- Bootstrap JS bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>


    <link rel="stylesheet" href="assets/css/mobile.css?v=<?php echo time(); ?>">

    <title>Document</title>
</head>
<body style="background-color: rgb(248,249,250);">

<?php include 'includes/mobile_header.php'; ?>

<div class="container my-3">
  <div class="input-group">
    <input
      type="text"
      class="form-control"
      placeholder="Search by name, manager, or ID..."
      aria-label="Search"
      aria-describedby="search-icon"
      id="search-input"
    />
  </div>
</div>

<!-- Filter Button -->
<div class="container my-3">
  <button class="btn btn-outline-primary w-100" data-bs-toggle="modal" data-bs-target="#statusModal">
    Filter Status
  </button>
</div>

<!-- Fullscreen Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Select Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex flex-column justify-content-center align-items-stretch">
        <button class="btn btn-lg btn-outline-primary mb-2 status-btn" data-status="all">All</button>
        <button class="btn btn-lg btn-outline-primary mb-2 status-btn" data-status="planning">Planning</button>
        <button class="btn btn-lg btn-outline-primary mb-2 status-btn" data-status="in-progress">In Progress</button>
        <button class="btn btn-lg btn-outline-primary mb-2 status-btn" data-status="review">Review</button>
        <button class="btn btn-lg btn-outline-primary status-btn" data-status="complete">Complete</button>
      </div>
    </div>
  </div>
</div>

<script>
    const statusButtons = document.querySelectorAll('.status-btn');
const cards = document.querySelectorAll('.card');

statusButtons.forEach(btn => {
  btn.addEventListener('click', () => {
    const status = btn.dataset.status;

    // Filter cards
    cards.forEach(card => {
      if (status === 'all') {
        card.style.display = '';
      } else {
        const cardStatus = card.dataset.status;
        card.style.display = cardStatus === status ? '' : 'none';
      }
    });

    // Close the modal
    const modalEl = document.getElementById('statusModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    modal.hide();
  });
});

</script>

<!-- Needs attention -->
<div class="container my-3">
  <div class="d-flex border-start border-4 p-3" style="border-color: rgb(214,115,19) !important; background-color: #fff; border-radius: 8px;">
    
    <!-- Icon Circle -->
    <div class="d-flex align-items-center justify-content-center flex-shrink-0 me-3" 
         style="width: 40px; height: 40px; border-radius: 50%; background-color: rgb(252,237,215);">
      <i class="bi bi-exclamation-circle" style="color: rgb(228,96,19); font-size: 1.2rem;"></i>
    </div>

    <!-- Text -->
    <div class="d-flex flex-column justify-content-center">
      <div style="color: rgb(18,24,39); font-weight: 600;">6 Engagements Need Attention</div>
      <div style="color: rgb(133,139,149); font-size: 0.875rem;">6 overdue • Review and prioritize critical items</div>
    </div>

  </div>
</div>
<!-- ends Needs attention -->


<div class="container">
    <?php foreach($engagements as $eng): ?>
        <a href="engagement.php?eng_id=<?= $eng['eng_id'] ?>" class="text-decoration-none position-relative">
          <div class="card mb-3 p-3 my-3 mx-3 shadow-sm" style="border-color: rgb(229,231,235); position: relative;">
            
            <!-- Right Chevron -->
            <i class="bi bi-chevron-right position-absolute" 
               style="top: 12px; right: 12px; font-size: .875rem; color: rgb(107,114,129);"></i>

            <!-- Status Badge -->
            <div class="mb-2">
              <span class="badge rounded-pill bg-info text-dark"><?= htmlspecialchars($eng['eng_status']) ?></span>
            </div>

            <!-- Engagement Name -->
            <div class="fs-5 fw-semibold text-truncate"><?= htmlspecialchars($eng['eng_name']) ?></div>
            
            <!-- Engagement ID -->
            <div class="text-muted mb-3" style="font-size: 0.75rem;"><?= htmlspecialchars($eng['eng_idno']) ?></div>

            <!-- Audit Type Row -->
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="d-flex align-items-center" style="color: rgb(107,114,129); font-size: 0.875rem;">
                <i class="bi bi-file-earmark-text me-1"></i>&nbsp;Audit Type
              </div>
              <div class="fw-semibold" style="color: rgb(18,24,39); font-size: 0.875rem;"><?= htmlspecialchars($eng['eng_audit_type']) ?></div>
            </div>

            <!-- Manager Row -->
            <div class="d-flex justify-content-between align-items-center mb-1">
              <div class="d-flex align-items-center" style="color: rgb(107,114,129); font-size: 0.875rem;">
                <i class="bi bi-person me-1"></i>&nbsp;Manager
              </div>
              <div class="fw-semibold" style="color: rgb(18,24,39); font-size: 0.875rem;"><?= htmlspecialchars($eng['manager_name']) ?></div>
            </div>

            <!-- Audit Team Row -->
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="d-flex align-items-center" style="color: rgb(107,114,129); font-size: 0.875rem;">
                <i class="bi bi-people me-1"></i>&nbsp;Team
              </div>
              <div class="fw-semibold" style="color: rgb(18,24,39); font-size: 0.875rem;"><?= $eng['team_count'] ?> members</div>
            </div>

            <!-- Next Milestone -->
<?php if(!empty($eng['next_milestone'])): 
      $milestoneType = $eng['next_milestone']['milestone_type'] ?? 'Upcoming';
      $dueDateStr = $eng['next_milestone']['due_date'] ?? null;

      if($dueDateStr) {
          $dueTimestamp = strtotime($dueDateStr);
          $due = date('M d', $dueTimestamp);
          $daysLeft = (int)(($dueTimestamp - time()) / 86400);
      } else {
          $due = 'TBD';
          $daysLeft = '';
      }
?>
<div class="d-flex flex-column mb-3 p-2 rounded shadow-sm" style="border: 1px solid rgb(196,218,252); background-color: rgb(240,246,254); font-size: 0.875rem;">
  
  <!-- First Row: Next: milestone type -->
  <div class="fw-semibold mb-1" style="color: rgb(35,70,221);">
    Next: <?= htmlspecialchars($milestoneType) ?>
  </div>
  
  <!-- Second Row: Calendar icon, date, days left -->
  <div class="d-flex align-items-center text-muted">
    <i class="bi bi-calendar me-2" style="color: rgb(63,106,243);"></i>
    <span class="fw-semibold me-2" style="color: rgb(35,56,137);"><?= $due ?></span>
    <?php if($daysLeft !== ''): ?>
      <span style="color: rgb(35,70,221);">(<?= $daysLeft ?>d left)</span>
    <?php endif; ?>
  </div>

</div>
<?php endif; ?>



            <!-- Team Members -->
            <div class="d-flex align-items-center">
              <div class="me-2 fw-semibold">Team:</div>
              <div class="d-flex">
                <?php 
                  $z = 1;
                  foreach($eng['team_members'] as $member): 
                      $initials = implode('', array_map(function($n){ return strtoupper($n[0]); }, explode(' ', $member)));
                ?>
                  <div class="rounded-circle border border-white text-white d-flex align-items-center justify-content-center" 
                       style="width:32px; height:32px; font-size:0.75rem; margin-left:<?= $z>1 ? '-8px' : '0' ?>; z-index:<?= $z ?>; background-color: rgb(160,77,253);">
                    <?= $initials ?>
                  </div>
                <?php $z++; endforeach; ?>
              </div>
            </div>

          </div>
        </a>
    <?php endforeach; ?>
</div>








    
</body>
</html>