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
      /* background-color: #f8f9fa;  */
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


/* Card shadow on hover */
.custom-card {
  transition: all 0.3s ease;
}

.custom-card:hover {
  box-shadow: 8px 8px 20px rgba(0,0,0,0.15);
}

/* Small icon box */
.icon-box {
  transition: all 0.3s ease;
}

/* Enlarge icon box on hover without affecting card */
.custom-card:hover .icon-box {
  transform: scale(1.1); /* grows the box smoothly */
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

    <!-- Status Cards -->
        <div class="row row-cols-1 row-cols-md-5 g-4" style="margin-left: 150px; margin-right: 150px;">
            <div class="col">
                <div class="card custom-card position-relative" style="background-color: rgb(225,238,253); border-color: rgb(225,228,232); overflow: hidden;">
                    <div class="card-body">

                        <!-- Small icon -->
                        <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(30,117, 255); color: white; font-size: 1.5rem; border-radius: 15px;">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>

                        <!-- Title -->
                        <h6 class="card-title mb-2" style="color: rgb(104,115,128);">Total Engagements</h6>

                        <!-- Big Number -->
                        <h2 class="fw-bold" style="color: rgb(30,117,225);">1,250</h2>

                        <!-- Decorative Icon behind -->
                        <i class="bi bi-graph-up-arrow position-absolute" style="font-size: 5rem; top: 100px; right: -10px; color: rgba(30,117,255,0.15); z-index:  0;"></i>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card custom-card position-relative" style="background-color: rgb(255,241,224); border-color: rgb(225,228,232); overflow: hidden;">
                    <div class="card-body">

                        <!-- Small icon -->
                        <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(255,92, 0); color: white; font-size: 1.5rem; border-radius: 15px;">
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
                <div class="card custom-card position-relative" style="background-color: rgb(226,253,237); border-color: rgb(225,228,232); overflow: hidden;">
                    <div class="card-body">

                        <!-- Small icon -->
                        <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(0,194,  81); color: white; font-size: 1.5rem; border-radius: 15px;">
                            <i class="bi bi-check2-circle"></i>
                        </div>

                        <!-- Title -->
                        <h6 class="card-title mb-2" style="color: rgb(104,115,128);">Completed</h6>

                        <!-- Big Number -->
                        <h2 class="fw-bold" style="color: rgb(0,194,81);">1,250</h2>

                        <!-- Decorative Icon behind -->
                        <i class="bi bi-check2-circle position-absolute" style="font-size: 5rem; top: 100px; right: -10px; color: rgba(0,194,81,0.15); z-index: 0;  "></i>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card custom-card position-relative" style="background-color: rgb(255,231,231); border-color: rgb(225,228,232); overflow: hidden;">
                    <div class="card-body">

                        <!-- Small icon -->
                        <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(255,35, 52); color: white; font-size: 1.5rem; border-radius: 15px;">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>

                        <!-- Title -->
                        <h6 class="card-title mb-2" style="color: rgb(104,115,128);">Overdue</h6>

                        <!-- Big Number -->
                        <h2 class="fw-bold" style="color: rgb(252,35,52);">1,250</h2>

                        <!-- Decorative Icon behind -->
                        <i class="bi bi-exclamation-triangle position-absolute" style="font-size: 5rem; top: 100px; right: -10px; color: rgba(255,35,52,0.15);  z-index: 0;"></i>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card custom-card position-relative" style="background-color: rgb(247,236,254); border-color: rgb(225,228,232); overflow: hidden;">
                  <div class="card-body position-relative">

                    <div class="icon-box mb-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px; background-color: rgb(172,63, 255); color: white; font-size: 1.5rem; border-radius: 15px;">
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
        <div class="row align-items-center " style="margin-left: 150px; margin-right: 150px; ">
            <div class="p-3 border rounded d-flex align-items-center" style="box-shadow: 1px 1px 4px rgba(0,0,0,0.15);">
                <div class="input-group flex-grow-1 me-3">
                    <span class="input-group-text border-end-0" style="background-color: rgb(248,249,251); color: rgb(142,151,164);">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" style="background-color: rgb(248,249,251);;" placeholder="Search...">
                </div>
                <!-- Filter button -->
                <button class="btn btn-outline-primary">
                  <i class="bi bi-funnel-fill me-1"></i> Filter
                </button>
            </div>
        </div>
    <!-- end search bar -->

    <!-- status updates -->

 
  <div class="row g-4" style="margin-left: 150px; margin-right: 150px; ">

    <!-- Overdue -->
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="fw-semibold text-danger mb-3">Overdue</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-2">Client A – Audit Review</li>
            <li class="mb-2">Client B – Risk Assessment</li>
            <li>Client C – Policy Update</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Due This Week -->
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="fw-semibold text-primary mb-3">Due This Week</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-2">Client D – PCI Scan</li>
            <li class="mb-2">Client E – Vulnerability Test</li>
            <li>Client F – Documentation</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- High Priority -->
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="fw-semibold text-warning mb-3">High Priority</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-2">Client G – Incident Response</li>
            <li class="mb-2">Client H – Critical Fix</li>
            <li>Client I – Compliance Review</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Recent Updates -->
    <div class="col-md-3">
      <div class="card h-100">
        <div class="card-body">
          <h6 class="fw-semibold text-success mb-3">Recent Updates</h6>
          <ul class="list-unstyled mb-0">
            <li class="mb-2">Client J – Status Updated</li>
            <li class="mb-2">Client K – Notes Added</li>
            <li>Client L – Files Uploaded</li>
          </ul>
        </div>
      </div>
    </div>

  </div>



    <!-- end status updates -->




  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>