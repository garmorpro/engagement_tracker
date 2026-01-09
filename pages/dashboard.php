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
    /* .page-content {
  padding-top: 80px; 
} */

    
    .header-container {
  position: sticky;
 
  top: 0;
  left: 0;
  width: 100%;
  height: 80px;               /* set explicit height */
  background: #fff;
  z-index: 1000;
  border-bottom: 1px solid #e5e7eb;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.header-inner {
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 200px;
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
        border-radius: 10px !important;
    }
    .menu-btn, .filter-btn {
      background-color: rgb(241,242,245) !important;
      border-radius: 10px !important;
      padding: 8px 10px !important;
      border: none !important;
    }
    .menu-btn:hover, .filter-btn:hover {
      background-color: rgb(225,228,232) !important;
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

.icon-square {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1rem;
}

.engagement-card-updates i {
  transition: transform 0.25s ease, color 0.25s ease;
}

.engagement-card-updates:hover {
    background-color: rgb(243,244,246) !important;
}

.engagement-card-updates:hover i {
  transform: translateX(6px);
  color: rgb(74,86,101);
}
.engagement-card-kanban:hover {
  box-shadow: 2px 2px 5px rgba(0,0,0,0.15);
}

.engagement-card-kanban .card-actions {
  opacity: 0;
  visibility: hidden;
  transition: opacity 0.15s ease-in-out;
}

.engagement-card-kanban:hover .card-actions {
  opacity: 1;
  visibility: visible;
}

.table-wrapper {
  border: 1px solid #dee2e6;
  border-radius: 12px;
  overflow: hidden;   /* CRITICAL for rounded corners */
  background: #fff;
}

.table-wrapper thead th {
  background-color: rgb(236,236,240);
  color: rgb(113,113,129);
  font-weight: 400 !important;
  border-bottom: 1px solid #dee2e6;
  padding: 15px 15px;
}

.table-wrapper tbody td {
  padding: 15px 15px;
}

  </style>
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
            <button class="btn btn-sm me-2 tab-btn active" id="tab-board" data-bs-toggle="tab" data-bs-target="#content-board" type="button" role="tab">
              <i class="bi bi-grid"></i> Board
            </button>
            <button class="btn btn-sm me-2 tab-btn" id="tab-list" data-bs-toggle="tab" data-bs-target="#content-list" type="button" role="tab">
              <i class="bi bi-list-ul"></i> List
            </button>
            <button class="btn btn-sm me-2 tab-btn" id="tab-timeline" data-bs-toggle="tab" data-bs-target="#content-timeline" type="button" role="tab">
              <i class="bi bi-calendar2"></i> Timeline
            </button>
            <button class="btn btn-sm tab-btn" id="tab-analytics" data-bs-toggle="tab" data-bs-target="#content-analytics" type="button" role="tab">
              <i class="bi bi-graph-up"></i> Analytics
            </button>
          </div>

          <button class="btn menu-btn btn-sm ms-3"><i class="bi bi-archive"></i>&nbsp;&nbsp;Archive</button>
          <button class="btn menu-btn btn-sm ms-3"><i class="bi bi-tools"></i>&nbsp;&nbsp;Tools</button>
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

  <div class="tab-content mt-2">
    <div class="tab-pane fade show active" id="content-board" role="tabpanel">
  
        <!-- Board sections -->
  
          <!-- Row 1 -->
            <div class="row align-items-center" style="margin-top: 20px; margin-left: 210px; margin-right: 210px;">
                <div class="card" style="border-radius: 15px; border: 2px solid rgb(208,213,219); background-color: rgb(247,248,250) !important;">
                    <div class="card-body">
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(105,114,129) !important;">
                          On Hold
                          <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(149,156,166) !important;">1</span>
                        </span>
    
    
                        <div class="card engagement-card-kanban mb-2" style="border-radius: 15px; border: 1px solid rgb(208,213,219); cursor: move;">
                          <div class="card-body d-flex align-items-center justify-content-between">
    
                            <!-- LEFT -->
                            <div class="left d-flex align-items-center gap-3">
                              <i class="bi bi-grip-horizontal text-secondary"></i>
    
                              
                                <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">Retain Chain Assessment</h5>
                                <span class="text-muted" style="font-size: 14px;">ENG-2025-006</span>
                             
                            </div>
    
                            <!-- RIGHT -->
                            <div class="right d-flex align-items-center gap-3 text-secondary">
                              <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;Jane Brown</span>
                              <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;Apr 30</span>
                              <span class="badge text-bg-secondary" style="background-color: rgba(235, 236, 237, 1) !important; color: rgb(57,69,85) !important;     font-weight: 500 !important;">SOC 2 Type 2</span>
                              <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important;font-weight:    500   !important;">Overdue</span>
                            </div>
    
                          </div>
                        </div>
    
    
                        <div class="card engagement-card-kanban" style="border-radius: 15px; border: 1px solid rgb(208,213,219); cursor: move;">
                          <div class="card-body d-flex align-items-center justify-content-between">
    
                            <!-- LEFT -->
                            <div class="left d-flex align-items-center gap-3">
                              <i class="bi bi-grip-horizontal text-secondary"></i>
    
                              
                                <h5 class="mb-0" style="font-size: 18px; font-weight: 600;">Retain Chain Assessment</h5>
                                <span class="text-muted" style="font-size: 14px;">ENG-2025-006</span>
                             
                            </div>
    
                            <!-- RIGHT -->
                            <div class="right d-flex align-items-center gap-3 text-secondary">
                              <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;Jane Brown</span>
                              <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;Apr 30</span>
                              <span class="badge text-bg-secondary" style="background-color: rgba(235, 236, 237, 1) !important; color: rgb(57,69,85) !important;     font-weight: 500 !important;">SOC 2 Type 2</span>
                              <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important;font-weight:    500   !important;">Overdue</span>
                            </div>
    
                          </div>
                        </div>
    
    
    
                        
                    </div>
                </div>
            </div>
          <!-- end row 1 -->
  
          <!-- Row 2 -->
            <div class="row align-items-start g-4" style="margin-top: 1px; margin-top: px; margin-left: 200px; margin-right: 200px;">
  
              <!-- Column 1 -->
                <div class="col-md-4">
  
                  <div class="card" style="border-radius: 15px; border: 2px solid rgb(154,196,254); background-color: rgb(233,241,254) !important;">
                    <div class="card-body">
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(68, 125,252) !important;">
                          Planning
                          <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(123,162,249) !important;">1</span>
                        </span>
  
  
                        <div class="card engagement-card-kanban mb-2" style="background-color: rgb(249,250,251); border: 1px solid rgb(208,213,219); border-radius:    15px;   cursor: move;">
                        <div class="card-body" style="margin-bottom: -15px !important;">
  
                          <!-- Title row -->
                          <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                            <h6 class="card-title fw-bold mb-0" >
                              Acme Corportation Audit
                            </h6>
                            <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                          </div>
  
                          <!-- Subtext -->
                          <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                            <span style="color: rgb(106,115,130); font-size: 14px !important;">ENG-2024-001</span><br><div class="pb-2"></div>
                            <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;Jane Brown</span><br>
                            <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;Apr 30, 2025</span><br>
                            <div class="tags pt-2">
                              <span class="badge text-bg-secondary" style="background-color: rgba(235, 236, 237, 1) !important; color: rgb(57,69,85) !important;     font-weight: 500 !important;">SOC 2 Type 2</span>
                              <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important;font-weight:    500   !important;">Overdue</span>
                            </div>
  
                          </p>
  
                        </div>
                      </div>
  
  
  
                    </div>
                  </div>
                </div> 
              <!-- end col 1 -->
  
              <!-- Column 2 -->
                <div class="col-md-4">
  
                  <div class="card" style="border-radius: 15px; border: 2px solid rgb(247,187,118); background-color: rgb(253,244,229) !important;">
                    <div class="card-body">
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(241,115,19) !important;">
                          In Progress
                          <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(243,155,89) !important;">1</span>
                        </span>
  
  
                        <div class="card engagement-card-kanban mb-2" style="background-color: rgb(249,250,251); border: 1px solid rgb(208,213,219); border-radius:    15px;   cursor: move;">
                        <div class="card-body" style="margin-bottom: -15px !important;">
  
                          <!-- Title row -->
                          <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                            <h6 class="card-title fw-bold mb-0" >
                              Acme Corportation Audit
                            </h6>
                            <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                          </div>
  
                          <!-- Subtext -->
                          <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                            <span style="color: rgb(106,115,130); font-size: 14px !important;">ENG-2024-001</span><br><div class="pb-2"></div>
                            <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;Jane Brown</span><br>
                            <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;Apr 30, 2025</span><br>
                            <div class="tags pt-2">
                              <span class="badge text-bg-secondary" style="background-color: rgba(235, 236, 237, 1) !important; color: rgb(57,69,85) !important;     font-weight: 500 !important;">SOC 2 Type 2</span>
                              <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important;font-weight:    500   !important;">Overdue</span>
                            </div>
  
                          </p>
  
                        </div>
                      </div>
  
  
  
                    </div>
                  </div>
                </div> 
              <!-- end col 2 -->
  
              <!-- Column 3 -->
                <div class="col-md-4">
  
                  <div class="card" style="border-radius: 15px; border: 2px solid rgb(212,179,254); background-color: rgb(247,241,254) !important;">
                    <div class="card-body">
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(160,77,253) !important;">
                          In Review
                          <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(188,129,251) !important;">1</span>
                        </span>
  
  
                        <div class="card engagement-card-kanban mb-2" style="background-color: rgb(249,250,251); border: 1px solid rgb(208,213,219); border-radius:    15px;   cursor: move;">
                        <div class="card-body" style="margin-bottom: -15px !important;">
  
                          <!-- Title row -->
                          <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                            <h6 class="card-title fw-bold mb-0" >
                              Acme Corportation Audit
                            </h6>
                            <i class="bi bi-three-dots-vertical text-secondary card-actions"></i>
                          </div>
  
                          <!-- Subtext -->
                          <p class="text-secondary" style="font-size: 16px; margin-bottom: -5px !important;">
                            <span style="color: rgb(106,115,130); font-size: 14px !important;">ENG-2024-001</span><br><div class="pb-2"></div>
                            <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;Jane Brown</span><br>
                            <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;Apr 30, 2025</span><br>
                            <div class="tags pt-2">
                              <span class="badge text-bg-secondary" style="background-color: rgba(235, 236, 237, 1) !important; color: rgb(57,69,85) !important;     font-weight: 500 !important;">SOC 2 Type 2</span>
                              <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important;font-weight:    500   !important;">Overdue</span>
                            </div>
  
                          </p>
  
                        </div>
                      </div>
  
  
  
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
                        <!-- <h5 class="card-title">Card title</h5> -->
                        <span class="badge rounded-pill d-inline-flex align-items-center mb-3"
                              style="font-size: 15px; padding: 10px 14px; background-color: rgb(79,198,95) !important;">
                          Complete
                          <span class="badge rounded-pill ms-2" style="color: white !important; background-color: rgb(130,212,141) !important;">1</span>
                        </span>
  
  
                        <div class="card engagement-card-kanban" style="border-radius: 15px; border: 1px solid rgb(208,213,219); cursor: move;">
                          <div class="card-body d-flex align-items-center justify-content-between">
  
                            <!-- LEFT -->
                            <div class="left d-flex align-items-center gap-3">
                              <i class="bi bi-grip-horizontal text-secondary"></i>
  
                              
                                <h6 class="mb-0" style="font-size: 18px; font-weight: 600;">Retain Chain Assessment</h6>
                                <span class="text-muted" style="font-size: 14px;">ENG-2025-006</span>
                             
                            </div>
  
                            <!-- RIGHT -->
                            <div class="right d-flex align-items-center gap-3 text-secondary">
                              <span style="font-size: 14px;"><i class="bi bi-people"></i>&nbsp;Jane Brown</span>
                              <span style="font-size: 14px; color: rgb(243,36,57);"><i class="bi bi-calendar2"></i>&nbsp;Apr 30</span>
                              <span class="badge text-bg-secondary" style="background-color: rgba(235, 236, 237, 1) !important; color: rgb(57,69,85) !important;   font-weight: 500 !important;">SOC 2 Type 2</span>
                              <span class="badge text-bg-danger" style="background-color: rgb(255,226,226) !important; color: rgb(201,0,18) !important;font-weight: 500    !important;">Overdue</span>
                            </div>
  
                          </div>
                        </div>
  
  
                        
                    </div>
                </div>
            </div>
          <!-- end row 3 -->
  
        <!-- end board sections -->

    </div>

    <div class="tab-pane fade" id="content-list" role="tabpanel">
      <div class="mt-4" style="margin-left: 210px; margin-right: 210px;">
        Showing 6 of 6 engagements

        <div class="table-wrapper mt-3">
          <table class="table align-middle mb-0">
            <thead>
              <tr style="background-color: rgb(236,236,240) !important;">
                <th class="text-uppercase" style="font-size: 14px;" scope="col">ID</th>
                <th class="text-uppercase" style="font-size: 14px;" scope="col">Engagement Name</th>
                <th class="text-uppercase" style="font-size: 14px;" scope="col">Manager</th>
                <th class="text-uppercase" style="font-size: 14px;" scope="col">Status</th>
                <th class="text-uppercase" style="font-size: 14px;" scope="col">Period</th>
                <th class="text-uppercase" style="font-size: 14px;" scope="col">Draft Due</th>
                <th class="text-uppercase" style="font-size: 14px;" scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>ENG-2024-001</td>
                <td><strong>Acme Corporation Audit</strong><br><span class="text-secondary" style="font-size: 12px;">SOC 2 Type 2</span></td>
                <td>John Smith</td>
                <td>In Progress</td>
                <td>FY 2024</td>
                <td>2025-02-01</td>
                <td><i class="bi bi-trash"></i></td>
              </tr>
              <tr>
                <td>ENG-2024-001</td>
                <td><strong>Acme Corporation Audit</strong><br><span class="text-secondary" style="font-size: 12px;">SOC 2 Type 2</span></td>
                <td>John Smith</td>
                <td>In Progress</td>
                <td>FY 2024</td>
                <td>2025-02-01</td>
                <td><i class="bi bi-trash"></i></td>
              </tr>
              <tr>
                <td>ENG-2024-001</td>
                <td><strong>Acme Corporation Audit</strong><br><span class="text-secondary" style="font-size: 12px;">SOC 2 Type 2</span></td>
                <td>John Smith</td>
                <td>In Progress</td>
                <td>FY 2024</td>
                <td>2025-02-01</td>
                <td><i class="bi bi-trash"></i></td>
              </tr>
            </tbody>
          </table>
        </div>



      </div>
      

    </div>

    <div class="tab-pane fade" id="content-timeline" role="tabpanel">
      <!-- Timeline content here -->
      <p>Timeline view of engagements...</p>
    </div>

    <div class="tab-pane fade" id="content-analytics" role="tabpanel">

      <!-- status updates -->
  
          <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">
  
              <!-- Overdue -->
              <div class="col-md-3">
                <div class="card h-100" style="border-color: rgb(255,201,202); border-radius: 15px;">
                  <div class="card-body p-4">
  
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-3">
                      <div class="icon-square me-2" style="background-color: rgb(255,241,242);">
                        <i class="bi bi-exclamation-circle" style="color: rgb(241,0,24);"></i>
                      </div>
                      <h6 class="fw-semibold mb-0">Overdue</h6>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
  
  
  
                  </div>
                </div>
              </div>
  
              <!-- Due This Week -->
              <div class="col-md-3">
                <div class="card h-100" style="border-color: rgb(255,214,171);  border-radius: 15px;">
                  <div class="card-body">
  
                    <div class="d-flex align-items-center mb-3">
                      <div class="icon-square me-2" style="background-color: rgb(255,247,238);">
                        <i class="bi bi-calendar-week" style="color: rgb(255,71,0);"></i>
                      </div>
                      <h6 class="fw-semibold mb-0">Due This Week</h6>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                  </div>
                </div>
              </div>
  
              <!-- High Priority -->
              <div class="col-md-3">
                <div class="card h-100" style="border-color: rgb(236,213,254);  border-radius: 15px;">
                  <div class="card-body">
  
                    <div class="d-flex align-items-center mb-3">
                      <div class="icon-square me-2" style="background-color: rgb(251,245,254);">
                        <i class="bi bi-flag-fill" style="color: rgb(162,27,244);"></i>
                      </div>
                      <h6 class="fw-semibold mb-0">High Priority</h6>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                  </div>
                </div>
              </div>
  
              <!-- Recent Updates -->
              <div class="col-md-3">
                <div class="card h-100" style="border-color: rgb(187,219,253);  border-radius: 15px;">
                  <div class="card-body">
  
                    <div class="d-flex align-items-center mb-3">
                      <div class="icon-square me-2" style="background-color: rgb(238,246,254);">
                        <i class="bi bi-clock-history" style="color: rgb(20,95,245);"></i>
                      </div>
                      <h6 class="fw-semibold mb-0">Recent Updates</h6>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                    <div class="card engagement-card-updates" style="background-color: rgb(249,250,251); border: none;">
                      <div class="card-body">
  
                        <!-- Title row -->
                        <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                          <h6 class="card-title fw-bold mb-0" >
                            Acme Corportation Audit
                          </h6>
                          <i class="bi bi-arrow-right text-secondary"></i>
                        </div>
  
                        <!-- Subtext -->
                        <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                          <span style="color: rgb(106,115,130);">ENG-2024-001</span><br>
                          <span style="color: rgb(74,86,101);">Due: Feb 15, 2025</span>
                        </p>
  
                      </div>
                    </div>
  
                  </div>
                </div>
              </div>
  
          </div>
      
      <!-- end status updates -->
    
  </div>
</div>




    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>