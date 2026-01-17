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
  <div class="d-flex justify-content-between">

    <!-- Started Card -->
    <div class="card flex-fill me-2 p-3 text-start">
      <div class="mb-2">
        <i class="bi bi-play-circle-fill fs-3 text-primary"></i>
      </div>
      <div class="fs-5 fw-bold mb-1">24</div>
      <div class="text-muted">Started</div>
    </div>

    <!-- Nearly Done Card -->
    <div class="card flex-fill mx-1 p-3 text-start">
      <div class="mb-2">
        <i class="bi bi-hourglass-split fs-3 text-warning"></i>
      </div>
      <div class="fs-5 fw-bold mb-1">15</div>
      <div class="text-muted">Nearly Done</div>
    </div>

    <!-- Needs Attention Card -->
    <div class="card flex-fill ms-2 p-3 text-start">
      <div class="mb-2">
        <i class="bi bi-exclamation-triangle-fill fs-3 text-danger"></i>
      </div>
      <div class="fs-5 fw-bold mb-1">5</div>
      <div class="text-muted">Needs Attention</div>
    </div>

  </div>
</div>



    
</body>
</html>