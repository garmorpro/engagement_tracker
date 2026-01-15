<?php 
// sessions_start();

require_once '../includes/functions.php';

$engagements = getAllEngagements($conn);
$milestones = getEngagementMilestones($conn);

// Merge milestones into engagements array
$engagementsWithMilestones = array_map(function($eng) use ($milestones) {
    $id = (int)$eng['eng_id'];
    return [
        'id' => $id,
        'name' => $eng['eng_name'],
        'planningCall' => $milestones[$id]['planningCall'] ?? null,
        'fieldworkStart' => $milestones[$id]['fieldworkStart'] ?? null,
        'draftDue' => $milestones[$id]['draftDue'] ?? null,
        'finalDue' => $milestones[$id]['finalDue'] ?? null
    ];
}, $engagements);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timeline - Engagement Tracker</title>
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
            <a class="btn btn-sm me-2 tab-btn active" href="engagement-timeline.php">
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

    <?php include_once '../includes/status_cards.php'; ?>
    <?php include_once '../includes/search_bar.php'; ?>

    <!-- Calendar -->
        <div class="calendar mt-4" style="margin-left: 210px; margin-right: 210px;">

          <div class="calendar-header">
            <h5 id="calendar-title"></h5>
            <div class="calendar-nav">
              <button id="prevMonth" class="nav-btn">
                <i class="bi bi-chevron-left"></i>
              </button>
              <button id="nextMonth" class="nav-btn">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>
          </div>

          <!-- Weekdays -->
          <div class="calendar-weekdays">
            <div>Sun</div>
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
          </div>

          <!-- Days -->
          <div id="calendar-days" class="calendar-days"></div>

          <hr>

          <div class="table-key" style="display: flex; gap: 20px; align-items: center;">
            <div class="final_due" style="display: flex; align-items: center; gap: 5px;">
              <span style="display:inline-block; width:10px; height:10px; background-color: rgba(55, 182, 38, 1); border-radius: 3px;"></span>
              Final Due
            </div>
            <div class="draft_due" style="display: flex; align-items: center; gap: 5px;">
              <span style="display:inline-block; width:10px; height:10px; background-color: rgba(195, 119, 38, 1); border-radius: 3px;"></span>
              Draft Due
            </div>
            <div class="fieldwork_start" style="display: flex; align-items: center; gap: 5px;">
              <span style="display:inline-block; width:10px; height:10px; background-color: rgba(41, 133, 193, 1); border-radius: 3px;"></span>
              Fieldwork Start
            </div>
            <div class="planning_call" style="display: flex; align-items: center; gap: 5px;">
              <span style="display:inline-block; width:10px; height:10px; background-color: rgba(131, 38, 193, 1); border-radius: 3px;"></span>
              Planning Call
            </div>
          </div>
        </div>
    <!-- end calendar -->



    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>


<script>
const engagements = <?php echo json_encode($engagementsWithMilestones, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

console.log("Engagements loaded:", engagements);

let currentDate = new Date();

function renderCalendar() {
    const daysContainer = document.getElementById("calendar-days");
    if (!daysContainer) return;

    daysContainer.innerHTML = ""; // clear previous calendar

    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    // Set header
    document.getElementById("calendar-title").textContent =
        currentDate.toLocaleString("default", { month: "long", year: "numeric" });

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const todayStr = new Date().toISOString().split("T")[0];

    // Empty placeholders for first week
    for (let i = 0; i < firstDay; i++) {
        const emptyEl = document.createElement("div");
        emptyEl.className = "calendar-day empty-day";
        daysContainer.appendChild(emptyEl);
    }

    // Generate days
    for (let day = 1; day <= daysInMonth; day++) {
        const dayEl = document.createElement("div");
        dayEl.className = "calendar-day";

        const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(day).padStart(2, "0")}`;

        if (dateStr === todayStr) dayEl.classList.add("today");

        dayEl.innerHTML = `<div class="calendar-day-number">${day}</div>`;

        // Add events
        engagements.forEach(e => {
            const eventMap = [
                { date: e.planningCall, type: "Planning Call" },
                { date: e.fieldworkStart, type: "Fieldwork Start" },
                { date: e.draftDue, type: "Draft Due" },
                { date: e.finalDue, type: "Final Due" }
            ];

            eventMap.forEach(ev => {
                if (!ev.date) return; // skip empty/null
                if (ev.date === dateStr) {
                    const eventEl = document.createElement("div");
                    const typeClassMap = {
                        "Planning Call": "planning_call",
                        "Fieldwork Start": "fieldwork_start",
                        "Draft Due": "draft_due",
                        "Final Due": "final_due"
                    };
                    eventEl.className = `event ${typeClassMap[ev.type]}`;
                    eventEl.textContent = `${e.name} (${ev.type})`;
                    dayEl.appendChild(eventEl);
                }
            });
        });

        daysContainer.appendChild(dayEl);
    }
}

// Month navigation
document.getElementById("prevMonth").onclick = () => {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
};
document.getElementById("nextMonth").onclick = () => {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
};

// Initial render
renderCalendar();
</script>






</body>
</html>