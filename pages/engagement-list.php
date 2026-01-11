<?php 
// sessions_start();

require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_eng_id'])) {
    $engId = $_POST['delete_eng_id'];

    $stmt = $conn->prepare("DELETE FROM engagements WHERE eng_idno = ?");
    $stmt->bind_param("s", $engId);

    if ($stmt->execute()) {
        // Redirect to avoid resubmission and show success message
        header("Location: dashboard.php?message=Engagement deleted successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Failed to delete engagement.</div>";
    }
}

// Handle editing engagement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['edit_eng_id'])) {

    $engId = $_POST['edit_eng_id'];

    /* ============================
       TEXT FIELDS
    ============================ */
    $name        = $_POST['eng_name'] ?? '';
    $manager     = $_POST['eng_manager'] ?? '';
    $senior      = $_POST['eng_senior'] ?? '';
    $staff       = $_POST['eng_staff'] ?? '';
    $senior_dol  = $_POST['eng_senior_dol'] ?? '';
    $staff_dol   = $_POST['eng_staff_dol'] ?? '';
    $poc         = $_POST['eng_poc'] ?? '';
    $location    = $_POST['eng_location'] ?? '';
    $audit_type  = $_POST['eng_audit_type'] ?? '';
    $scope       = $_POST['eng_scope'] ?? '';
    $tsc         = $_POST['eng_tsc'] ?? '';
    $notes       = $_POST['eng_notes'] ?? '';

    /* ============================
       Y / N FIELDS
    ============================ */
    $repeat                      = $_POST['eng_repeat'] ?? 'N';
    $completed_internal_planning = $_POST['eng_completed_internal_planning'] ?? 'N';
    $irl_sent                    = $_POST['eng_irl_sent'] ?? 'N';
    $completed_client_planning   = $_POST['eng_completed_client_planning'] ?? 'N';
    $fieldwork_complete          = $_POST['eng_fieldwork_complete'] ?? 'N';
    $leadsheet_complete          = $_POST['eng_leadsheet_complete'] ?? 'N';
    $draft_sent                  = $_POST['eng_draft_sent'] ?? 'N';
    $final_sent                  = $_POST['eng_final_sent'] ?? 'N';
    $section_3_requested         = $_POST['eng_section_3_requested'] ?? 'N';

    /* ============================
       DATE FIELDS (EMPTY → NULL)
    ============================ */
    function nullDate($key) {
        return !empty($_POST[$key]) ? $_POST[$key] : null;
    }

    $start_period              = nullDate('eng_start_period');
    $end_period                = nullDate('eng_end_period');
    $as_of_date                = nullDate('eng_as_of_date');
    $internal_planning_call    = nullDate('eng_internal_planning_call');
    $irl_due                   = nullDate('eng_irl_due');
    $client_planning_call      = nullDate('eng_client_planning_call');
    $fieldwork                 = nullDate('eng_fieldwork');
    $leadsheet_due             = nullDate('eng_leadsheet_due');
    $draft_due                 = nullDate('eng_draft_due');
    $final_due                 = nullDate('eng_final_due');
    $archive                   = nullDate('eng_archive');
    $last_communication        = nullDate('eng_last_communication');

    /* ============================
       STATUS (SINGLE SELECT)
    ============================ */
    $status = $_POST['eng_status'] ?? '';

    /* ============================
       PREPARE UPDATE
    ============================ */
    $stmt = $conn->prepare("
        UPDATE engagements SET
            eng_name = ?, 
            eng_manager = ?, 
            eng_senior = ?, 
            eng_staff = ?,
            eng_senior_dol = ?, 
            eng_staff_dol = ?, 
            eng_poc = ?, 
            eng_location = ?,
            eng_repeat = ?, 
            eng_audit_type = ?, 
            eng_scope = ?, 
            eng_tsc = ?,
            eng_start_period = ?, 
            eng_end_period = ?, 
            eng_as_of_date = ?,
            eng_internal_planning_call = ?, 
            eng_completed_internal_planning = ?,
            eng_irl_due = ?, 
            eng_irl_sent = ?, 
            eng_client_planning_call = ?,
            eng_completed_client_planning = ?, 
            eng_fieldwork = ?, 
            eng_fieldwork_complete = ?,
            eng_leadsheet_due = ?, 
            eng_leadsheet_complete = ?, 
            eng_draft_due = ?, 
            eng_draft_sent = ?,
            eng_final_due = ?, 
            eng_final_sent = ?, 
            eng_archive = ?, 
            eng_section_3_requested = ?,
            eng_last_communication = ?, 
            eng_notes = ?, 
            eng_status = ?
        WHERE eng_idno = ?
    ");

    /* ============================
       BIND (35 params + ID)
    ============================ */
    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssssss",
        $name,
        $manager,
        $senior,
        $staff,
        $senior_dol,
        $staff_dol,
        $poc,
        $location,
        $repeat,
        $audit_type,
        $scope,
        $tsc,
        $start_period,
        $end_period,
        $as_of_date,
        $internal_planning_call,
        $completed_internal_planning,
        $irl_due,
        $irl_sent,
        $client_planning_call,
        $completed_client_planning,
        $fieldwork,
        $fieldwork_complete,
        $leadsheet_due,
        $leadsheet_complete,
        $draft_due,
        $draft_sent,
        $final_due,
        $final_sent,
        $archive,
        $section_3_requested,
        $last_communication,
        $notes,
        $status,
        $engId
    );

    /* ============================
       EXECUTE
    ============================ */
    if ($stmt->execute()) {
        header("Location: engagement-list.php?message=Engagement updated successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>
            Failed to update engagement: " . htmlspecialchars($stmt->error) . "
        </div>";
    }
}






$engagements = getAllEngagements($conn);
$totalEngagements = count($engagements);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List - Engagement Tracker</title>
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
            <a class="btn btn-sm me-2 tab-btn active" href="engagement-list.php">
              <i class="bi bi-list-ul"></i> List
            </a>
            <a class="btn btn-sm me-2 tab-btn" href="engagement-timeline.php">
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


    <!-- table -->
<div class="mt-4" style="margin-left: 210px; margin-right: 210px;">
    Showing <?php echo $totalEngagements; ?> of <?php echo $totalEngagements; ?> engagements
    <div class="table-wrapper mt-3">
        <table class="table align-middle mb-0">
            <thead>
                <tr style="background-color: rgb(236,236,240) !important;">
                    <th>ID</th>
                    <th>Engagement Name</th>
                    <th>Manager</th>
                    <th>Status</th>
                    <th>Period</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($engagements as $eng): ?>
                <tr>
                    <td><?php echo htmlspecialchars($eng['eng_idno']); ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($eng['eng_name']); ?></strong><br>
                        <?php if (!empty($eng['eng_audit_type'])): ?>
                            <span class="text-secondary" style="font-size: 12px;"><?php echo htmlspecialchars($eng['eng_audit_type']); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($eng['eng_manager']); ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($eng['eng_status'])); ?></td>
                    <?php
                        $periodText = '—';
                        if (!empty($eng['eng_start_period']) && !empty($eng['eng_end_period'])) {
                            $start = date('M j, Y', strtotime($eng['eng_start_period']));
                            $end   = date('M j, Y', strtotime($eng['eng_end_period']));
                            $periodText = "{$start} – {$end}";
                        } elseif (!empty($eng['eng_as_of_date'])) {
                            $asOf = date('M j, Y', strtotime($eng['eng_as_of_date']));
                            $periodText = "As of {$asOf}";
                        }
                    ?>
                    <td><?php echo htmlspecialchars($periodText); ?></td>

                    <td>
                        <div class="d-flex gap-1">
                            <!-- VIEW -->
                            <a href="engagement-details.php?eng_id=<?php echo $eng['eng_idno']; ?>" 
                               class="btn btn-sm btn-outline-primary action-btn view-btn" 
                               title="View">
                              <i class="bi bi-eye"></i>
                            </a>

                            <!-- EDIT -->
                            <button class="btn btn-sm btn-outline-warning action-btn edit-btn" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal-<?php echo $eng['eng_idno']; ?>" 
                                    title="Edit">
                              <i class="bi bi-pencil-square"></i>
                            </button>

                            <!-- DELETE -->
                            <form method="POST" style="display:inline-block;" 
                                  onsubmit="return confirm('Are you sure you want to delete this engagement?');">
                                <input type="hidden" name="delete_eng_id" value="<?php echo $eng['eng_idno']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger action-btn delete-btn" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>

                <!-- Modal for this engagement -->
<div class="modal fade" id="editModal-<?php echo $eng['eng_idno']; ?>" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">

<form method="POST">
<input type="hidden" name="edit_eng_id" value="<?php echo $eng['eng_idno']; ?>">

<div class="modal-header">
  <h5 class="modal-title">Edit Engagement</h5>
  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
<div class="row g-3">

<!-- =====================
     TEXT FIELDS
===================== -->

<h6 class="fw-semibold mt-4">
  Basic Information
</h6>
<hr>

<?php
// Define your toggle fields: db_column => label
$ynFields = [
    'eng_repeat' => 'Repeat'
    // Add more Y/N fields if needed, e.g. 'eng_completed_internal_planning' => 'Completed Internal Planning'
];

foreach ($ynFields as $col => $label):
    $checked = (($eng[$col] ?? 'N') === 'Y');
?>
<div class="col-md-6 mb-2">
    <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">
        <?php echo $label; ?>
    </label>
    <div class="d-flex align-items-center gap-2">
        <div class="yn-toggle <?php echo $checked ? 'active' : ''; ?>"
             data-hidden-name="<?php echo $col; ?>"
             onclick="toggleYN(this)">
            <?php echo $checked ? '✓ Y' : 'N'; ?>
        </div>
        <!-- Hidden input always posts -->
        <input type="hidden" name="<?php echo $col; ?>" value="<?php echo $checked ? 'Y' : 'N'; ?>">
    </div>
</div>
<?php endforeach; ?>

<script>
function toggleYN(el) {
    const hiddenName = el.getAttribute('data-hidden-name');
    const hidden = document.querySelector(`input[name="${hiddenName}"]`);
    
    if(el.classList.contains('active')) {
        el.classList.remove('active');
        el.textContent = 'N';
        hidden.value = 'N';
    } else {
        el.classList.add('active');
        el.textContent = '✓ Y';
        hidden.value = 'Y';
    }
}
</script>

<style>
.yn-toggle {
    cursor: pointer;
    padding: 0 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 14px;
    user-select: none;
}
.yn-toggle.active {
    background-color: #1577f2;
    color: white;
    font-weight: bold;
}
</style>




<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Engagement Name<sup>*</sup></label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_name"
         value="<?php echo htmlspecialchars($eng['eng_name'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Audit Type</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_audit_type"
         value="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? '', ENT_QUOTES); ?>">
</div>

<!-- =====================
     STATUS
===================== -->

<div class="col-12 mb-3">
    <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Status</label>
    <div class="d-flex gap-2 flex-wrap">
        <?php
        $statuses = [
            'on-hold' => ['label' => 'On Hold', 'border' => '107,114,129', 'bg' => '249,250,251', 'text' => '56,65,82', 'icon' => 'bi-pause-circle'],
            'planning' => ['label' => 'Planning', 'border' => '68,125,252', 'bg' => '240,246,254', 'text' => '35,70,221', 'icon' => 'bi-clock'],
            'in-progress' => ['label' => 'In Progress', 'border' => '241,115,19', 'bg' => '254,247,238', 'text' => '186,66,13', 'icon' => 'bi-play-circle'],
            'in-review' => ['label' => 'In Review', 'border' => '160,77,253', 'bg' => '249,245,254', 'text' => '119,17,210', 'icon' => 'bi-eye'],
            'complete' => ['label' => 'Completed', 'border' => '79,198,95', 'bg' => '242,253,245', 'text' => '51,128,63', 'icon' => 'bi-check2-circle'],
        ];
        $defaultBorder = '229,231,235';
        $defaultBg = '255,255,255';
        $defaultText = '76,85,100';
        foreach ($statuses as $key => $s):
            $selected = (($eng['eng_status'] ?? '') === $key);
        ?>
        <div class="status-card text-center p-2 flex-fill"
             data-status="<?php echo $key; ?>"
             style="
                cursor: pointer;
                border: 2px solid rgb(<?php echo $selected ? $s['border'] : $defaultBorder; ?>);
                background-color: rgb(<?php echo $selected ? $s['bg'] : $defaultBg; ?>);
                color: rgb(<?php echo $selected ? $s['text'] : $defaultText; ?>);
                font-weight: 500;
                border-radius: 1rem;
             ">
            <i class="bi <?php echo $s['icon']; ?>" style="font-size: 1.1rem;"></i>
            <div style="margin-top: 0.25rem; font-size: 12px;"><?php echo $s['label']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
    <input type="hidden" name="eng_status" id="eng_status_input" value="<?php echo htmlspecialchars($eng['eng_status'] ?? ''); ?>">
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.status-card');
    const hiddenInput = document.getElementById('eng_status_input');

    const defaultBorder = '229,231,235';
    const defaultBg = '255,255,255';
    const defaultText = '76,85,100';

    const statusColors = {
        'on-hold': {border: '107,114,129', bg: '249,250,251', text: '56,65,82'},
        'planning': {border: '68,125,252', bg: '240,246,254', text: '35,70,221'},
        'in-progress': {border: '241,115,19', bg: '254,247,238', text: '186,66,13'},
        'in-review': {border: '160,77,253', bg: '249,245,254', text: '119,17,210'},
        'complete': {border: '79,198,95', bg: '242,253,245', text: '51,128,63'},
    };

    const applyColors = (card, status, isSelected) => {
        if (isSelected) {
            const colors = statusColors[status];
            card.style.border = `2px solid rgb(${colors.border})`;
            card.style.backgroundColor = `rgb(${colors.bg})`;
            card.style.color = `rgb(${colors.text})`;
        } else {
            card.style.border = `2px solid rgb(${defaultBorder})`;
            card.style.backgroundColor = `rgb(${defaultBg})`;
            card.style.color = `rgb(${defaultText})`;
        }
    };

    // Initialize all cards
    cards.forEach(card => {
        const status = card.dataset.status;
        const isSelected = hiddenInput.value === status;
        applyColors(card, status, isSelected);

        // Click handler
        card.addEventListener('click', () => {
            hiddenInput.value = status;
            // Reset all cards to default except the selected
            cards.forEach(c => applyColors(c, c.dataset.status, c.dataset.status === status));
        });

        // Hover handler
        card.addEventListener('mouseenter', () => {
            applyColors(card, status, true); // temporary highlight
        });
        card.addEventListener('mouseleave', () => {
            const isSelected = hiddenInput.value === status;
            applyColors(card, status, isSelected); // revert to selected or default
        });
    });
});

</script>


<script>
document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.status-card');
    const hiddenInput = document.getElementById('eng_status_input');

    cards.forEach(card => {
        card.addEventListener('click', () => {
            // Remove selection from all
            cards.forEach(c => c.style.boxShadow = 'none');

            // Add selection to clicked
            card.style.boxShadow = '0 0 0 2px rgba(0,0,0,0.15)';
            hiddenInput.value = card.dataset.status;
        });

        card.addEventListener('mouseover', () => {
            card.style.opacity = 0.85;
        });
        card.addEventListener('mouseout', () => {
            card.style.opacity = 1;
        });
    });
});
</script>

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">TSC</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_tsc"
         value="<?php echo htmlspecialchars($eng['eng_tsc'] ?? '', ENT_QUOTES); ?>">
</div>

<!-- =====================
     DATE ONLY
===================== -->

<?php
$dateFields = [
  'eng_start_period'       => 'Start Period',
  'eng_end_period'         => 'End Period',
  'eng_as_of_date'         => 'As Of Date'
];
foreach ($dateFields as $field => $label):
?>
<div class="col-md-4">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);"><?php echo $label; ?></label>
  <input type="date" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;"
         name="<?php echo $field; ?>"
         value="<?php echo $eng[$field] ?? ''; ?>">
</div>
<?php endforeach; ?>


<h6 class="fw-semibold mt-5">Team Members</h6>
<hr>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Manager</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_manager"
         value="<?php echo htmlspecialchars($eng['eng_manager'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Senior(s)</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_senior"
         value="<?php echo htmlspecialchars($eng['eng_senior'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Staff</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_staff"
         value="<?php echo htmlspecialchars($eng['eng_staff'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Senior DOL</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_senior_dol"
         value="<?php echo htmlspecialchars($eng['eng_senior_dol'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Staff DOL</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_staff_dol"
         value="<?php echo htmlspecialchars($eng['eng_staff_dol'] ?? '', ENT_QUOTES); ?>">
</div>


<h6 class="fw-semibold mt-5">Client Information</h6>
<hr>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">POC</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_poc"
         value="<?php echo htmlspecialchars($eng['eng_poc'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Location</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_location"
         value="<?php echo htmlspecialchars($eng['eng_location'] ?? '', ENT_QUOTES); ?>">
</div>


<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Scope</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_scope"
         value="<?php echo htmlspecialchars($eng['eng_scope'] ?? '', ENT_QUOTES); ?>">
</div>



<h6 class="fw-semibold mt-5">Important Dates & Milestones</h6>
<hr>

<!-- =====================
     DATE + TOGGLE PAIRS
===================== -->

<?php
$pairs = [
  'eng_internal_planning_call' => 'eng_completed_internal_planning',
  'eng_irl_due'                => 'eng_irl_sent',
  'eng_client_planning_call'   => 'eng_completed_client_planning',
  'eng_fieldwork'              => 'eng_fieldwork_complete',
  'eng_leadsheet_due'          => 'eng_leadsheet_complete',
  'eng_draft_due'              => 'eng_draft_sent',
  'eng_final_due'              => 'eng_final_sent'
];
foreach ($pairs as $date => $yn):
$checked = (($eng[$yn] ?? 'N') === 'Y');
?>
<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);"><?php echo ucwords(str_replace('_',' ',$date)); ?></label>
  <div class="d-flex align-items-center gap-2">

    <input type="date" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;"
           name="<?php echo $date; ?>"
           value="<?php echo $eng[$date] ?? ''; ?>">

    <div class="yn-toggle <?php echo $checked ? 'active' : ''; ?>"
         onclick="toggleYN(this)">
      <?php echo $checked ? '✓ Y' : 'N'; ?>
    </div>

    <!-- ALWAYS POST -->
    <input type="hidden" name="<?php echo $yn; ?>" value="<?php echo $checked ? 'Y' : 'N'; ?>">

  </div>
</div>
<?php endforeach; ?>

<!-- =====================
     DATE ONLY
===================== -->

<?php
$dateFields = [
  'eng_archive'            => 'Archive Date',
  'eng_last_communication' => 'Last Communication'
];
foreach ($dateFields as $field => $label):
?>
<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);"><?php echo $label; ?></label>
  <input type="date" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;"
         name="<?php echo $field; ?>"
         value="<?php echo $eng[$field] ?? ''; ?>">
</div>
<?php endforeach; ?>

<!-- =====================
     Y / N SELECTS
===================== -->

<div class="col-md-3">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Repeat</label>
  <select class="form-select" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_repeat">
    <option value="Y" <?php echo (($eng['eng_repeat'] ?? 'N') === 'Y') ? 'selected' : ''; ?>>Yes</option>
    <option value="N" <?php echo (($eng['eng_repeat'] ?? 'N') === 'N') ? 'selected' : ''; ?>>No</option>
  </select>
</div>

<div class="col-md-3">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Section 3 Requested</label>
  <select class="form-select" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_section_3_requested">
    <option value="Y" <?php echo (($eng['eng_section_3_requested'] ?? 'N') === 'Y') ? 'selected' : ''; ?>>Yes</option>
    <option value="N" <?php echo (($eng['eng_section_3_requested'] ?? 'N') === 'N') ? 'selected' : ''; ?>>No</option>
  </select>
</div>

<!-- =====================
     NOTES
===================== -->

<div class="col-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Notes</label>
  <textarea class="form-control" name="eng_notes" rows="4"><?php
    echo htmlspecialchars($eng['eng_notes'] ?? '', ENT_QUOTES);
  ?></textarea>
</div>

</div>
</div>

<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
  <button type="submit" class="btn btn-primary">Save Changes</button>
</div>

</form>
</div>
</div>
</div>



                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleYN(el) {
  const hidden = el.nextElementSibling;
  el.classList.toggle('active');

  if (el.classList.contains('active')) {
    el.innerHTML = '✓ Y';
    hidden.value = 'Y';
  } else {
    el.innerHTML = 'N';
    hidden.value = 'N';
  }
}
</script>


</body>
</html>