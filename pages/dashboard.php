<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Engagement Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
    body {
      background-color: #f8f9fa; /* Light background like your screenshot */
      font-family: 'Arial', sans-serif;
    }
    .header-container {
      background-color: #fff;
      padding: 20px 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid #dee2e6;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .header-title {
      font-weight: 600;
      font-size: 1.25rem;
    }
    .header-subtitle {
      font-size: 0.875rem;
      color: #6c757d;
      margin-top: 2px;
    }
    .tab-btn.active {
        background-color: white;
        border: none !important;
    }

    .tab-btn.active:hover {
        background-color: white;
        border: none !important;
    }
    .tab-btn {
        border: none !important;
    }
    .tab-btn:hover {
        background-color: rgb(225,228,232);
        border: none !important;
    }
    .new-btn {
        background-color: rgb(15,62,69);
        color: white;
    }
    .new-btn:hover {
        background-color: rgba(15, 62, 69, 0.9);
        color: white;
    }
  </style>
</head>
<body>

    <div class="header-container">
    <div style="margin-left: 150px;">
      <div class="header-title">Engagement Tracker</div>
      <div class="header-subtitle">Manage your audit engagements</div>
    </div>
    <div class="header-actions d-flex align-items-center" style="margin-right: 150px;">
        <div class="view-options p-1" style="background-color: rgb(241,242,245); border-radius: 10px;">
            <button type="button" class="btn btn-sm me-2 tab-btn active"><i class="bi bi-grid"></i>&nbsp;&nbsp;Board</button>
            <button type="button" class="btn btn-sm me-2 tab-btn"><i class="bi bi-list-ul"></i>&nbsp;&nbsp;List</button>
            <button type="button" class="btn btn-sm me-2 tab-btn"><i class="bi bi-calendar2"></i>&nbsp;&nbsp;Timeline</button>
            <button type="button" class="btn btn-sm tab-btn"><i class="bi bi-graph-up"></i>&nbsp;&nbsp;Analytics</button>
        </div>
        <button type="button" class="btn new-btn btn-sm ms-3">+ New Engagement</button>
    </div>
  </div>

  <div class="mt-5"></div>


  <div class="row row-cols-1 row-cols-md-5 g-4" style="margin-left: 150px; margin-right: 150px;">


        <div class="col">
        
            <div class="card position-relative" style="background-color: rgb(225,238,253); border-color: rgb(225,228,232); overflow: hidden;">
                <div class="card-body">

                    <!-- Small icon -->
                    <div class="mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(30,117,255); color: white; font-size: 1.5rem; border-radius: 15px;">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>

                    <!-- Title -->
                    <h6 class="card-title mb-2" style="color: rgb(104,115,128);">Revenue</h6>

                    <!-- Big Number -->
                    <h2 class="fw-bold" style="color: rgb(30,117,225);">1,250</h2>

                    <!-- Decorative Icon behind -->
                    <i class="bi bi-graph-up-arrow position-absolute" style="font-size: 5rem; top: 50px; right: -30px; color: rgba(30,117,255,0.15); z-index: 0;"></i>
                </div>
            </div>
        </div>





      <div class="col">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Card 2</h5>
            <p class="card-text">Some example text for card 2.</p>
            <a href="#" class="btn btn-primary">Go somewhere</a>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Card 3</h5>
            <p class="card-text">Some example text for card 3.</p>
            <a href="#" class="btn btn-primary">Go somewhere</a>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Card 4</h5>
            <p class="card-text">Some example text for card 4.</p>
            <a href="#" class="btn btn-primary">Go somewhere</a>
          </div>
        </div>
      </div>
      <div class="col">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title">Card 5</h5>
            <p class="card-text">Some example text for card 5.</p>
            <a href="#" class="btn btn-primary">Go somewhere</a>
          </div>
        </div>
      </div>
    </div>


  



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>