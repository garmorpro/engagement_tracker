<?php 
// sessions_start();

require_once '../includes/functions.php';

$engagementsWithMilestones = getAllEngagements($conn); // rename
$overdueEngagements = getOverdueEngagements($conn);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Engagement Tracker</title>
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
            <a class="btn btn-sm me-2 tab-btn" href="dashboard.php">
              <i class="bi bi-grid"></i> Board
            </a>
            <a class="btn btn-sm me-2 tab-btn" href="engagement-list.php">
              <i class="bi bi-list-ul"></i> List
            </a>
            <a class="btn btn-sm me-2 tab-btn" href="engagement-timeline.php">
              <i class="bi bi-calendar2"></i> Timeline
            </a>
            <a class="btn btn-sm tab-btn active" href="engagement-analytics.php">
              <i class="bi bi-graph-up"></i> Analytics
            </a>
          </div>

          <a class="btn archive-btn btn-sm ms-3" href="archive.php"><i class="bi bi-archive"></i>&nbsp;&nbsp;Archive</a>
          <a class="btn tools-btn btn-sm ms-3" href="tools.php"><i class="bi bi-tools"></i>&nbsp;&nbsp;Tools</a>
          <!-- <button class="btn new-btn btn-sm ms-3"><i class="bi bi-plus"></i>&nbsp;&nbsp;New Engagement</button> -->
        </div>
      </div>
    </div>
  <!-- Header -->

  <div class="mt-4"></div>

    <?php include_once '../includes/status_cards.php'; ?>
    <?php include_once '../includes/search_bar.php'; ?>



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
  
                    <?php if (empty($overdueEngagements)): ?>
    <p class="text-secondary text-center small mb-0">No overdue engagements 🎉</p>
<?php else: ?>
    <?php foreach ($overdueEngagements as $eng): ?>
        <div class="card engagement-card-updates mb-2" style="background-color: rgb(249,250,251); border: none;">
            <div class="card-body">

                <!-- Title row -->
                <div class="d-flex align-items-center justify-content-between" style="margin-top: -5px !important;">
                    <h6 class="card-title fw-bold mb-0">
                        <?= htmlspecialchars($eng['eng_name']) ?>
                    </h6>
                    <i class="bi bi-arrow-right text-secondary"></i>
                </div>

                <!-- Subtext -->
                <p class="text-secondary" style="font-size: 14px; margin-bottom: -5px !important;">
                    <span style="color: rgb(106,115,130);">
                        <?= htmlspecialchars($eng['eng_idno']) ?>
                    </span><br>
                    <span style="color: rgb(74,86,101);">
                        Due: <?= date('M j, Y', strtotime($eng['final_due_date'])) ?>
                    </span>
                </p>

            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

  
  
  
  
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

      <!-- analytic charts -->
  
          <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">
  
              <!-- Status Distribution -->
              <div class="col-md-6">
                <div class="card h-100" style="border-color: rgb(229,231,235); border-radius: 15px;">
                  <div class="card-body p-4 d-flex flex-column align-items-center">
                                                    
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-3 w-100">
                      <h6 class="fw-semibold mb-0">Status Distribution</h6>
                    </div>
                                                    
                    <!-- Chart container centered -->
                    <div style="width: 600px; height: 400px; display: flex; justify-content: center; align-items: center;">
                      <canvas id="status_distribution"></canvas>
                    </div>
                                                    
                  </div>
                </div>
              </div>
                                                    
              <!-- Manager Workload -->
              <div class="col-md-6">
                <div class="card h-100" style="border-color: rgb(229,231,235); border-radius: 15px;">
                  <div class="card-body p-4 d-flex flex-column align-items-center">
                                                    
                    <div class="d-flex align-items-center mb-3 w-100">
                      <h6 class="fw-semibold mb-0">Manager Workload</h6>
                    </div>
                                                    
                    <div style="width: 600px; height: 400px; display: flex; justify-content: center; align-items: center;">
                      <canvas id="manager_workload"></canvas>
                    </div>
                                                    
                  </div>
                </div>
              </div>


  
          </div>
      
      <!-- end analytic charts -->

      <!-- audit breakdown -->
  
        <div class="row g-4 mt-2" style="margin-left: 200px; margin-right: 200px;">
          <div class="col-md-12">
            <div class="card h-100" style="border-color: rgb(229,231,235); border-radius: 15px;">
              <div class="card-body p-4">
                <!-- Header -->
                <div class="d-flex align-items-center mb-3 w-100">
                  <h6 class="fw-semibold mb-0">Audit Type Breakdown</h6>
                </div>          

                <!-- 4-column cards -->
                <div class="row mt-4">
                  <!-- Card 1 -->
                  <div class="col-md-3">
                    <div class="card h-100" style="background-color: rgb(247,248,250); border-radius: 12px; border: 1px solid rgb(229,231,235);">
                      <div class="card-body d-flex flex-column align-items-start">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; font-weight: bold;">
                          5
                        </div>
                        <div class="mt-2" style="font-size: 14px;">SOC 2</div>
                      </div>
                    </div>
                  </div>          

                  <!-- Card 2 -->
                  <div class="col-md-3">
                    <div class="card h-100" style="background-color: rgb(247,248,250); border-radius: 12px; border: 1px solid rgb(229,231,235);">
                      <div class="card-body d-flex flex-column align-items-start">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; font-weight: bold;">
                          3
                        </div>
                        <div class="mt-2" style="font-size: 14px;">SOC 1</div>
                      </div>
                    </div>
                  </div>          

                  <!-- Card 3 -->
                  <div class="col-md-3">
                    <div class="card h-100" style="background-color: rgb(247,248,250); border-radius: 12px; border: 1px solid rgb(229,231,235);">
                      <div class="card-body d-flex flex-column align-items-start">
                        <div class="rounded-circle bg-warning text-white d-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; font-weight: bold;">
                          7
                        </div>
                        <div class="mt-2" style="font-size: 14px;">PCI</div>
                      </div>
                    </div>
                  </div>          

                  <!-- Card 4 -->
                  <div class="col-md-3">
                    <div class="card h-100" style="background-color: rgb(247,248,250); border-radius: 12px; border: 1px solid rgb(229,231,235);">
                      <div class="card-body d-flex flex-column align-items-start">
                        <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center mb-2" style="width: 50px; height: 50px; font-weight: bold;">
                          2
                        </div>
                        <div class="mt-2" style="font-size: 14px;">Other</div>
                      </div>
                    </div>
                  </div>
                </div>
                <!-- End 4-column cards -->         

              </div>
            </div>
          </div>
        </div>

      
      <!-- end audit breakdown -->
    
    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


 <script>
const engagements = <?php echo !empty($engagementsWithMilestones) 
    ? json_encode($engagementsWithMilestones, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)
    : '[]'; ?>;

console.log("Loaded engagements:", engagements);

// ----------------- Compute manager workload -----------------
const managerCounts = {};
engagements.forEach(e => {
    if (e.archived) return;
    const managerName = e.eng_manager || 'Unassigned';
    if (!managerCounts[managerName]) managerCounts[managerName] = 0;
    managerCounts[managerName]++;
});
const managerLabels = Object.keys(managerCounts);
const managerData = Object.values(managerCounts);

// ----------------- Compute status counts -----------------
const statusCategories = ['on-hold','planning','in-progress','in-review','complete'];
const statusCounts = statusCategories.reduce((acc, s) => {
    acc[s] = 0;
    return acc;
}, {});

engagements.forEach(e => {
    const status = e.eng_status;
    if (statusCounts.hasOwnProperty(status)) statusCounts[status]++;
});

const statusLabels = ['On-Hold','Planning','In-Progress','In-Review','Complete'];
const statusData = Object.values(statusCounts);

// ----------------- Compute audit type counts -----------------
const auditCounts = {};
engagements.forEach(e => {
    if (!e.audit_types) return;
    const audits = e.audit_types.split(',').map(a => a.trim());
    audits.forEach(a => {
        if (!auditCounts[a]) auditCounts[a] = 0;
        auditCounts[a]++;
    });
});
const auditLabels = Object.keys(auditCounts);
const auditData = Object.values(auditCounts);

// ----------------- Random colors -----------------
function randomColorPairs(count) {
    const fills = [];
    const borders = [];
    for (let i = 0; i < count; i++) {
        const r = Math.floor(Math.random() * 200 + 50);
        const g = Math.floor(Math.random() * 200 + 50);
        const b = Math.floor(Math.random() * 200 + 50);

        fills.push(`rgba(${r},${g},${b},0.2)`);
        borders.push(`rgba(${r},${g},${b},1)`);
    }
    return { fills, borders };
}

const colorPairs = randomColorPairs(managerLabels.length);
const managerColors = colorPairs.fills;
const managerBorders = colorPairs.borders;

// ----------------- Render Charts -----------------
let statusChart, managerChart, auditChart;

function renderStatusChart() {
    const ctx = document.getElementById('status_distribution').getContext('2d');
    if(statusChart) statusChart.destroy();

    console.log('Status Labels:', statusLabels);
    console.log('Status Data:', statusData);

    const total = statusData.reduce((a,b)=>a+b,0);
    if(total === 0){
        console.warn('All status counts are 0, adding dummy slice');
        statusData[0] = 1;
    }

    statusChart = new Chart(ctx, {
        type:'pie',
        data: {
            labels: statusLabels,
            datasets:[{
                data: statusData,
                backgroundColor:[
                    'rgba(107,114,128,0.8)',
                    'rgba(68,125,252,0.8)',
                    'rgba(241,115,19,0.8)',
                    'rgba(160,77,253,0.8)',
                    'rgba(79,198,95,0.8)'
                ],
                borderColor:[
                    'rgba(107,114,128,1)',
                    'rgba(68,125,252,1)',
                    'rgba(241,115,19,1)',
                    'rgba(160,77,253,1)',
                    'rgba(79,198,95,1)'
                ],
                borderWidth:1
            }]
        },
        options: {
            responsive:true,
            plugins: {
                legend:{position:'bottom'},
                tooltip:{enabled:true}
            }
        }
    });
}


function renderManagerChart() {
    const ctx = document.getElementById('manager_workload').getContext('2d');
    if(managerChart) managerChart.destroy();
    const maxValue = Math.max(...managerData);
    managerChart = new Chart(ctx, {
        type:'bar',
        data:{
            labels: managerLabels,
            datasets:[{
                label:'Active Engagements',
                data: managerData,
                backgroundColor: managerColors,
                borderColor: managerBorders,
                borderWidth:1,
                borderRadius:{topLeft:15,topRight:15}
            }]
        },
        options:{
            responsive:true,
            plugins:{legend:{display:false}, tooltip:{enabled:true}},
            scales:{y:{beginAtZero:true, max:maxValue+2, ticks:{stepSize:1}}}
        }
    });
}

// ----------------- Render on load -----------------
document.addEventListener('DOMContentLoaded', () => {
    renderStatusChart();
    renderManagerChart();
});
</script>





</body>
</html>