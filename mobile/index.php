<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tools - Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

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

<div class="container my-3">
  <ul class="nav nav-pills flex-row overflow-auto" id="status-filter" role="tablist">
    <li class="nav-item me-2" role="presentation">
      <button class="nav-link active" data-status="all" type="button">All</button>
    </li>
    <li class="nav-item me-2" role="presentation">
      <button class="nav-link" data-status="planning" type="button">Planning</button>
    </li>
    <li class="nav-item me-2" role="presentation">
      <button class="nav-link" data-status="in-progress" type="button">In Progress</button>
    </li>
    <li class="nav-item me-2" role="presentation">
      <button class="nav-link" data-status="review" type="button">Review</button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link" data-status="complete" type="button">Complete</button>
    </li>
  </ul>
</div>





    
</body>
</html>