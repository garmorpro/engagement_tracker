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
<body>

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





    
</body>
</html>