<?php 
// sessions_start();

require_once '../includes/functions.php';
require_once '../includes/init.php';
logoutUser($conn);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_eng_id'])) {
    $engId = (int)$_POST['delete_eng_id'];

    // Temporarily disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    // 1️⃣ Delete the engagement itself first
    $stmtEng = $conn->prepare("DELETE FROM engagements WHERE eng_id = ?");
    if ($stmtEng) {
        $stmtEng->bind_param("i", $engId);
        $stmtEng->execute();
        if ($stmtEng->error) {
            echo "<div class='alert alert-danger'>Failed to delete engagement: " . htmlspecialchars($stmtEng->error) . "</div>";
        }
        $stmtEng->close();
    }

    // 2️⃣ Delete team members
    $stmtTeam = $conn->prepare("DELETE FROM engagement_team WHERE eng_id = ?");
    if ($stmtTeam) {
        $stmtTeam->bind_param("i", $engId);
        $stmtTeam->execute();
        if ($stmtTeam->error) {
            echo "<div class='alert alert-danger'>Failed to delete team members: " . htmlspecialchars($stmtTeam->error) . "</div>";
        }
        $stmtTeam->close();
    }

    // 3️⃣ Delete milestones
    $stmtMilestones = $conn->prepare("DELETE FROM engagement_milestones WHERE eng_id = ?");
    if ($stmtMilestones) {
        $stmtMilestones->bind_param("i", $engId);
        $stmtMilestones->execute();
        if ($stmtMilestones->error) {
            echo "<div class='alert alert-danger'>Failed to delete milestones: " . htmlspecialchars($stmtMilestones->error) . "</div>";
        }
        $stmtMilestones->close();
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");

    // Redirect after deletion
    header("Location: dashboard.php?message=Engagement deleted successfully");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['unarchive_eng_id'])) {
    $engId = (int)$_POST['unarchive_eng_id'];

    // Temporarily disable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=0");

    // 1️⃣ Delete the engagement itself first
    $stmtEng = $conn->prepare("UPDATE engagements SET eng_status = 'complete' WHERE eng_id = ?");
    if ($stmtEng) {
        $stmtEng->bind_param("i", $engId);
        $stmtEng->execute();
        if ($stmtEng->error) {
            echo "<div class='alert alert-danger'>Failed to unarchive engagement: " . htmlspecialchars($stmtEng->error) . "</div>";
        }
        $stmtEng->close();
    }

    // Re-enable foreign key checks
    $conn->query("SET FOREIGN_KEY_CHECKS=1");

    // Redirect after deletion
    header("Location: archive.php?message=Engagement unarchived successfully");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archive - Engagement Tracker</title>
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
            <a class="btn btn-sm tab-btn" href="engagement-analytics.php">
              <i class="bi bi-graph-up"></i> Analytics
            </a>
          </div>

          <a class="btn archive-btn btn-sm ms-3 active" href="archive.php"><i class="bi bi-archive"></i>&nbsp;&nbsp;Archive</a>
          <a class="btn tools-btn btn-sm ms-3" href="tools.php"><i class="bi bi-tools"></i>&nbsp;&nbsp;Tools</a>
          <form method="POST" action="<?php echo BASE_URL . '/auth/logout.php'; ?>" class="d-inline">
                <button type="submit" class="btn logout-btn btn-sm ms-3">
                    <i class="bi bi-box-arrow-left"></i>&nbsp;&nbsp;Logout
                </button>
            </form>
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
        <?php
        $engagements = getAllEngagements($conn);

        // keep only archived
        $archivedEngagements = array_filter($engagements, function ($eng) {
            return $eng['eng_status'] === 'archived';
        });

        $totalArchived = count($archivedEngagements);
        ?>
          
          <div class="mt-4" style="margin-left: 210px; margin-right: 210px;">

          <?php if ($totalArchived > 0): ?>
          
              Showing <?php echo $totalArchived; ?> of <?php echo $totalArchived; ?> engagements
          
              <div class="table-wrapper mt-3">
                  <table class="table align-middle mb-0">
                      <thead>
                          <tr style="background-color: rgb(236,236,240) !important;">
                              <th>ID</th>
                              <th>Engagement Name</th>
                              <th>Manager</th>
                              <th>Status</th>
                              <th>Period</th>
                              <th>Archive Date</th>
                              <th>Actions</th>
                          </tr>
                      </thead>
                      <tbody>
                          <?php foreach ($archivedEngagements as $eng): ?>
                              <tr>
                                  <td><?php echo htmlspecialchars($eng['eng_idno']); ?></td>
                                  <td>
                                      <strong><?php echo htmlspecialchars($eng['eng_name']); ?></strong><br>
                                      <?php if (!empty($eng['eng_audit_type'])): ?>
                                          <span class="text-secondary" style="font-size: 12px;">
                                              <?php echo htmlspecialchars($eng['eng_audit_type']); ?>
                                          </span>
                                      <?php endif; ?>
                                  </td>
                                  <td><?php echo htmlspecialchars($eng['eng_manager']); ?></td>
                                  <td>
                                    <div class="badge" style="background-color: rgb(249,250,251); border: 1px solid rgb(229,231,235); border-radius: 12px; padding: 4px 8px; text-align: center; color: rgb(105,114,129); font-weight: 500;">
                                      <?php echo ucfirst(htmlspecialchars($eng['eng_status'])); ?>
                                    </div>
                                  </td>
                                  <?php
                                    $periodText = '—';

                                    // Case 1: Start + End exist
                                    if (!empty($eng['eng_start_period']) && !empty($eng['eng_end_period'])) {
                                        $start = date('M j, Y', strtotime($eng['eng_start_period']));
                                        $end   = date('M j, Y', strtotime($eng['eng_end_period']));
                                        $periodText = "{$start} – {$end}";

                                    // Case 2: No range, but As Of date exists
                                    } elseif (!empty($eng['eng_as_of_date'])) {
                                        $asOf = date('M j, Y', strtotime($eng['eng_as_of_date']));
                                        $periodText = "As of {$asOf}";
                                    }
                                    ?>

                                  <td><?php echo htmlspecialchars($periodText); ?></td>
                                  <td>
                                      <?php
                                      if (!empty($eng['eng_archive'])) {
                                          echo date('M j, Y', strtotime($eng['eng_archive']));
                                      }
                                      ?>
                                  </td>
                                  <td>
                                    <div class="d-flex gap-1">
                                        <!-- VIEW -->
                                        <a href="engagement-details.php?eng_id=<?php echo $eng['eng_id'] ?? ''; ?>" 
                                           class="btn btn-sm btn-outline-primary action-btn view-btn" 
                                           title="View">
                                          <i class="bi bi-eye"></i>
                                        </a>

                                        <!-- EDIT -->
                                        <form method="POST" style="display:inline-block;" 
                                              onsubmit="return confirm('Are you sure you want to unarchive this engagement?');">
                                            <input type="hidden" name="unarchive_eng_id" value="<?php echo $eng['eng_id'] ?? ''; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-warning action-btn edit-btn" title="unarchive">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>

                                        <!-- DELETE -->
                                        <form method="POST" style="display:inline-block;" 
                                              onsubmit="return confirm('Are you sure you want to delete this engagement?');">
                                            <input type="hidden" name="delete_eng_id" value="<?php echo $eng['eng_id'] ?? ''; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger action-btn delete-btn" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                  </td>
                              </tr>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
              </div>
                                    
          <?php else: ?>
          
              <!-- Empty State -->
              <div class="d-flex flex-column align-items-center justify-content-center text-center p-5"
                   style="border: 2px dashed rgb(208,213,219); border-radius: 16px; background-color: #fff;">
          
                  <div class="d-flex align-items-center justify-content-center mb-3"
                       style="width: 64px; height: 64px; border-radius: 50%; background-color: rgb(243,244,246);">
                      <i class="bi bi-archive" style="font-size: 28px; color: rgb(107,114,128);"></i>
                  </div>
          
                  <h5 class="mb-1">No Archived Engagements</h5>
                  <p class="text-secondary mb-0" style="max-width: 420px;">
                      When you archive completed engagements, they'll appear here for future reference.
                  </p>
              </div>
          
          <?php endif; ?>
          
          </div>

    <!-- end table -->


    <div class="mt-5"></div>



  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>