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

    // Collect all fields
    $name                       = $_POST['eng_name'] ?? '';
    $manager                    = $_POST['eng_manager'] ?? '';
    $senior                     = $_POST['eng_senior'] ?? '';
    $staff                      = $_POST['eng_staff'] ?? '';
    $senior_dol                 = $_POST['eng_senior_dol'] ?? '';
    $staff_dol                  = $_POST['eng_staff_dol'] ?? '';
    $poc                        = $_POST['eng_poc'] ?? '';
    $location                   = $_POST['eng_location'] ?? '';
    $repeat                     = $_POST['eng_repeat'] ?? 'N';
    $audit_type                 = $_POST['eng_audit_type'] ?? '';
    $scope                      = $_POST['eng_scope'] ?? '';
    $tsc                        = $_POST['eng_tsc'] ?? '';
    $start_period               = $_POST['eng_start_period'] ?? null;
    $end_period                 = $_POST['eng_end_period'] ?? null;
    $as_of_date                 = $_POST['eng_as_of_date'] ?? null;
    $internal_planning_call     = $_POST['eng_internal_planning_call'] ?? null;
    $completed_internal_planning= $_POST['eng_completed_internal_planning'] ?? 'N';
    $irl_due                    = $_POST['eng_irl_due'] ?? null;
    $irl_sent                   = $_POST['eng_irl_sent'] ?? 'N';
    $client_planning_call       = $_POST['eng_client_planning_call'] ?? null;
    $completed_client_planning  = $_POST['eng_completed_client_planning'] ?? 'N';
    $fieldwork                  = $_POST['eng_fieldwork'] ?? null;
    $fieldwork_complete         = $_POST['eng_fieldwork_complete'] ?? 'N';
    $leadsheet_due              = $_POST['eng_leadsheet_due'] ?? null;
    $leadsheet_complete         = $_POST['eng_leadsheet_complete'] ?? 'N';
    $draft_due                  = $_POST['eng_draft_due'] ?? null;
    $draft_sent                  = $_POST['eng_draft_sent'] ?? 'N';
    $final_due                  = $_POST['eng_final_due'] ?? null;
    $final_sent                  = $_POST['eng_final_sent'] ?? 'N';
    $archive                    = $_POST['eng_archive'] ?? null;
    $section_3_requested        = $_POST['eng_section_3_requested'] ?? 'N';
    $last_communication         = $_POST['eng_last_communication'] ?? null;
    $notes                      = $_POST['eng_notes'] ?? '';
    
    // Handle multi-select status (array)
    $status                     = isset($_POST['eng_status']) ? implode(',', $_POST['eng_status']) : '';

    // Prepare the UPDATE statement
    $stmt = $conn->prepare("UPDATE engagements SET 
        eng_name = ?, eng_manager = ?, eng_senior = ?, eng_staff = ?, 
        eng_senior_dol = ?, eng_staff_dol = ?, eng_poc = ?, eng_location = ?, 
        eng_repeat = ?, eng_audit_type = ?, eng_scope = ?, eng_tsc = ?, 
        eng_start_period = ?, eng_end_period = ?, eng_as_of_date = ?, 
        eng_internal_planning_call = ?, eng_completed_internal_planning = ?, 
        eng_irl_due = ?, eng_irl_sent = ?, eng_client_planning_call = ?, 
        eng_completed_client_planning = ?, eng_fieldwork = ?, eng_fieldwork_complete = ?, 
        eng_leadsheet_due = ?, eng_leadsheet_complete = ?, eng_draft_due = ?, eng_draft_sent = ?, 
        eng_final_due = ?, eng_final_sent = ?, eng_archive = ?, eng_section_3_requested = ?, 
        eng_last_communication = ?, eng_notes = ?, eng_status = ? 
        WHERE eng_idno = ?");

    $stmt->bind_param(
        "sssssssssssssssssssssssssssssssssssss",
        $name, $manager, $senior, $staff,
        $senior_dol, $staff_dol, $poc, $location,
        $repeat, $audit_type, $scope, $tsc,
        $start_period, $end_period, $as_of_date,
        $internal_planning_call, $completed_internal_planning,
        $irl_due, $irl_sent, $client_planning_call,
        $completed_client_planning, $fieldwork, $fieldwork_complete,
        $leadsheet_due, $leadsheet_complete, $draft_due, $draft_sent,
        $final_due, $final_sent, $archive, $section_3_requested,
        $last_communication, $notes, $status, $engId
    );

    if ($stmt->execute()) {
        header("Location: engagement-list.php?message=Engagement updated successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Failed to update engagement: " . htmlspecialchars($stmt->error) . "</div>";
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
<div class="modal fade" id="editModal-<?php echo $eng['eng_idno']; ?>" tabindex="-1" aria-labelledby="editModalLabel-<?php echo $eng['eng_idno']; ?>" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="edit_eng_id" value="<?php echo $eng['eng_idno']; ?>">

        <div class="modal-header">
          <h5 class="modal-title" id="editModalLabel-<?php echo $eng['eng_idno']; ?>">Edit Engagement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>

        <div class="modal-body">
          <div class="row g-3">

            <!-- Engagement Name -->
            <div class="col-md-6">
              <label for="eng_name-<?php echo $eng['eng_idno']; ?>" class="form-label">Engagement Name</label>
              <input type="text" class="form-control" id="eng_name-<?php echo $eng['eng_idno']; ?>" name="eng_name" value="<?php echo htmlspecialchars($eng['eng_name']); ?>" required>
            </div>

            <!-- Manager -->
            <div class="col-md-6">
              <label for="eng_manager-<?php echo $eng['eng_idno']; ?>" class="form-label">Manager</label>
              <input type="text" class="form-control" id="eng_manager-<?php echo $eng['eng_idno']; ?>" name="eng_manager" value="<?php echo htmlspecialchars($eng['eng_manager']); ?>" required>
            </div>

            <!-- Senior -->
            <div class="col-md-6">
              <label for="eng_senior-<?php echo $eng['eng_idno']; ?>" class="form-label">Senior</label>
              <input type="text" class="form-control" id="eng_senior-<?php echo $eng['eng_idno']; ?>" name="eng_senior" value="<?php echo htmlspecialchars($eng['eng_senior']); ?>">
            </div>

            <!-- Staff -->
            <div class="col-md-6">
              <label for="eng_staff-<?php echo $eng['eng_idno']; ?>" class="form-label">Staff</label>
              <input type="text" class="form-control" id="eng_staff-<?php echo $eng['eng_idno']; ?>" name="eng_staff" value="<?php echo htmlspecialchars($eng['eng_staff']); ?>">
            </div>

            <!-- Senior DOL -->
            <div class="col-md-6">
              <label for="eng_senior_dol-<?php echo $eng['eng_idno']; ?>" class="form-label">Senior DOL</label>
              <input type="text" class="form-control" id="eng_senior_dol-<?php echo $eng['eng_idno']; ?>" name="eng_senior_dol" value="<?php echo htmlspecialchars($eng['eng_senior_dol']); ?>">
            </div>

            <!-- Staff DOL -->
            <div class="col-md-6">
              <label for="eng_staff_dol-<?php echo $eng['eng_idno']; ?>" class="form-label">Staff DOL</label>
              <input type="text" class="form-control" id="eng_staff_dol-<?php echo $eng['eng_idno']; ?>" name="eng_staff_dol" value="<?php echo htmlspecialchars($eng['eng_staff_dol']); ?>">
            </div>

            <!-- POC -->
            <div class="col-md-6">
              <label for="eng_poc-<?php echo $eng['eng_idno']; ?>" class="form-label">POC</label>
              <input type="text" class="form-control" id="eng_poc-<?php echo $eng['eng_idno']; ?>" name="eng_poc" value="<?php echo htmlspecialchars($eng['eng_poc']); ?>">
            </div>

            <!-- Location -->
            <div class="col-md-6">
              <label for="eng_location-<?php echo $eng['eng_idno']; ?>" class="form-label">Location</label>
              <input type="text" class="form-control" id="eng_location-<?php echo $eng['eng_idno']; ?>" name="eng_location" value="<?php echo htmlspecialchars($eng['eng_location']); ?>">
            </div>

            <!-- Repeat -->
            <div class="col-md-6">
              <label for="eng_repeat-<?php echo $eng['eng_idno']; ?>" class="form-label">Repeat</label>
              <select class="form-select" id="eng_repeat-<?php echo $eng['eng_idno']; ?>" name="eng_repeat">
                <option value="Y" <?php echo ($eng['eng_repeat'] === 'Y') ? 'selected' : ''; ?>>Yes</option>
                <option value="N" <?php echo ($eng['eng_repeat'] === 'N') ? 'selected' : ''; ?>>No</option>
              </select>
            </div>

            <!-- Audit Type -->
            <div class="col-md-6">
              <label for="eng_audit_type-<?php echo $eng['eng_idno']; ?>" class="form-label">Audit Type</label>
              <input type="text" class="form-control" id="eng_audit_type-<?php echo $eng['eng_idno']; ?>" name="eng_audit_type" value="<?php echo htmlspecialchars($eng['eng_audit_type']); ?>">
            </div>

            <!-- Scope -->
            <div class="col-md-6">
              <label for="eng_scope-<?php echo $eng['eng_idno']; ?>" class="form-label">Scope</label>
              <input type="text" class="form-control" id="eng_scope-<?php echo $eng['eng_idno']; ?>" name="eng_scope" value="<?php echo htmlspecialchars($eng['eng_scope']); ?>">
            </div>

            <!-- TSC -->
            <div class="col-md-6">
              <label for="eng_tsc-<?php echo $eng['eng_idno']; ?>" class="form-label">TSC</label>
              <input type="text" class="form-control" id="eng_tsc-<?php echo $eng['eng_idno']; ?>" name="eng_tsc" value="<?php echo htmlspecialchars($eng['eng_tsc']); ?>">
            </div>

            <!-- Start Period -->
            <div class="col-md-4">
              <label for="eng_start_period-<?php echo $eng['eng_idno']; ?>" class="form-label">Start Period</label>
              <input type="date" class="form-control" id="eng_start_period-<?php echo $eng['eng_idno']; ?>" name="eng_start_period" value="<?php echo $eng['eng_start_period']; ?>">
            </div>

            <!-- End Period -->
            <div class="col-md-4">
              <label for="eng_end_period-<?php echo $eng['eng_idno']; ?>" class="form-label">End Period</label>
              <input type="date" class="form-control" id="eng_end_period-<?php echo $eng['eng_idno']; ?>" name="eng_end_period" value="<?php echo $eng['eng_end_period']; ?>">
            </div>

            <!-- As of Date -->
            <div class="col-md-4">
              <label for="eng_as_of_date-<?php echo $eng['eng_idno']; ?>" class="form-label">As Of Date</label>
              <input type="date" class="form-control" id="eng_as_of_date-<?php echo $eng['eng_idno']; ?>" name="eng_as_of_date" value="<?php echo $eng['eng_as_of_date']; ?>">
            </div>

            <!-- Internal Planning Call -->
            <div class="col-md-4">
              <label for="eng_internal_planning_call-<?php echo $eng['eng_idno']; ?>" class="form-label">Internal Planning Call</label>
              <input type="date" class="form-control" id="eng_internal_planning_call-<?php echo $eng['eng_idno']; ?>" name="eng_internal_planning_call" value="<?php echo $eng['eng_internal_planning_call']; ?>">
            </div>

            <!-- Completed Internal Planning -->
            <div class="col-md-4">
              <label for="eng_completed_internal_planning-<?php echo $eng['eng_idno']; ?>" class="form-label">Completed Internal Planning</label>
              <select class="form-select" id="eng_completed_internal_planning-<?php echo $eng['eng_idno']; ?>" name="eng_completed_internal_planning">
                <option value="Y" <?php echo ($eng['eng_completed_internal_planning'] === 'Y') ? 'selected' : ''; ?>>Yes</option>
                <option value="N" <?php echo ($eng['eng_completed_internal_planning'] === 'N') ? 'selected' : ''; ?>>No</option>
              </select>
            </div>

            <!-- Additional dates and Y/N fields follow the same pattern -->
            <!-- IRL Due, IRL Sent, Client Planning Call, Completed Client Planning, Fieldwork, Fieldwork Complete, etc. -->
            <?php
              $date_fields = [
                'eng_irl_due', 'eng_client_planning_call', 'eng_fieldwork', 
                'eng_leadsheet_due', 'eng_draft_due', 'eng_final_due', 
                'eng_archive', 'eng_last_communication'
              ];
              $yn_fields = [
                'eng_irl_sent', 'eng_completed_client_planning', 'eng_fieldwork_complete',
                'eng_leadsheet_complete', 'eng_draft_sent', 'eng_final_sent', 'eng_section_3_requested'
              ];
            ?>
            <?php foreach($date_fields as $field): ?>
            <div class="col-md-4">
              <label for="<?php echo $field.'-'.$eng['eng_idno']; ?>" class="form-label"><?php echo ucwords(str_replace('_',' ',$field)); ?></label>
              <input type="date" class="form-control" id="<?php echo $field.'-'.$eng['eng_idno']; ?>" name="<?php echo $field; ?>" value="<?php echo $eng[$field]; ?>">
            </div>
            <?php endforeach; ?>

            <?php foreach($yn_fields as $field): ?>
            <div class="col-md-4">
              <label for="<?php echo $field.'-'.$eng['eng_idno']; ?>" class="form-label"><?php echo ucwords(str_replace('_',' ',$field)); ?></label>
              <select class="form-select" id="<?php echo $field.'-'.$eng['eng_idno']; ?>" name="<?php echo $field; ?>">
                <option value="Y" <?php echo ($eng[$field]==='Y')?'selected':''; ?>>Yes</option>
                <option value="N" <?php echo ($eng[$field]==='N')?'selected':''; ?>>No</option>
              </select>
            </div>
            <?php endforeach; ?>

            <!-- Notes -->
            <div class="col-12">
              <label for="eng_notes-<?php echo $eng['eng_idno']; ?>" class="form-label">Notes</label>
              <textarea class="form-control" id="eng_notes-<?php echo $eng['eng_idno']; ?>" name="eng_notes" rows="4"><?php echo htmlspecialchars($eng['eng_notes']); ?></textarea>
            </div>

            <!-- Status (multi-select) -->
            <div class="col-12">
              <label for="eng_status-<?php echo $eng['eng_idno']; ?>" class="form-label">Status</label>
              <select multiple class="form-select" id="eng_status-<?php echo $eng['eng_idno']; ?>" name="eng_status[]">
                <?php
                  $statuses = ['on-hold', 'planning', 'in-progress', 'in-review', 'complete', 'archived'];
                  $current_status = explode(',', $eng['eng_status']); // assuming CSV in DB
                  foreach($statuses as $status):
                ?>
                <option value="<?php echo $status; ?>" <?php echo in_array($status,$current_status)?'selected':''; ?>><?php echo ucwords(str_replace('-',' ',$status)); ?></option>
                <?php endforeach; ?>
              </select>
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


</body>
</html>