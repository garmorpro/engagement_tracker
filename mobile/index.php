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


<!-- Engagement Card Template -->
<a href="engagement.php?eng_id=123" class="text-decoration-none">
  <div class="card mb-3 p-3 my-3 shadow-sm">
    
    <!-- Status Badge -->
    <div class="mb-2">
      <span class="badge rounded-pill bg-info text-dark">In Progress</span>
    </div>

    <!-- Engagement Name -->
    <div class="fs-5 fw-semibold text-truncate">ACME Corp</div>
    
    <!-- Engagement ID -->
    <div class="text-muted mb-3" style="font-size: 0.75rem;">ENG-2026-001</div>

    <!-- Manager Row -->
    <div class="d-flex justify-content-between align-items-center mb-1">
      <div class="d-flex align-items-center" style="color: rgb(107,114,129); font-size: 0.875rem;">
        <i class="bi bi-person me-1"></i>&nbsp;Manager
      </div>
      <div class="fw-semibold" style="color: rgb(18,24,39); font-size: 0.875rem;">John Smith</div>
    </div>

    <!-- Audit Team Row -->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center" style="color: rgb(107,114,129); font-size: 0.875rem;">
        <i class="bi bi-people me-1"></i>&nbsp;Audit Team
      </div>
      <div class="fw-semibold" style="color: rgb(18,24,39); font-size: 0.875rem;">5 members</div>
    </div>

    <div class="card p-2 text-center bg-light flex-1 d-flex flex-column mb-2" style="flex: 1 1 0;">
        <div class="text-muted small">Audit Type</div>
        <div class="fw-semibold">SOC 2 Type 2, SOC 1 Type 2, PCI</div>
    </div>
    <div class="card p-2 text-center bg-light flex-1 d-flex flex-column mb-3" style="flex: 1 1 0;">
        <div class="text-muted small">TSC</div>
        <div class="fw-semibold">Security, A, P</div>
    </div>



    <!-- Next Milestone -->
    <div class="d-flex align-items-center mb-3 p-2 border rounded bg-white shadow-sm">
      <div class="fw-semibold me-2">Next:</div>
      <div class="me-auto">Draft Due</div>
      <div class="d-flex align-items-center text-muted">
        <i class="bi bi-calendar me-1"></i> Feb 19 (34d left)
      </div>
    </div>

    <!-- Team Members Row -->
    <div class="d-flex align-items-center">
      <div class="me-2 fw-semibold">Team:</div>
      <div class="d-flex">
        <div class="rounded-circle border border-white bg-secondary text-white d-flex align-items-center justify-content-center me-1" style="width:32px; height:32px; font-size:0.75rem;">SJ</div>
        <div class="rounded-circle border border-white bg-secondary text-white d-flex align-items-center justify-content-center me-1" style="width:32px; height:32px; font-size:0.75rem;">DM</div>
        <div class="rounded-circle border border-white bg-secondary text-white d-flex align-items-center justify-content-center me-1" style="width:32px; height:32px; font-size:0.75rem;">MD</div>
        <div class="rounded-circle border border-white bg-secondary text-white d-flex align-items-center justify-content-center" style="width:32px; height:32px; font-size:0.75rem;">JW</div>
      </div>
    </div>

  </div>
</a>







    
</body>
</html>