<?php 
// sessions_start();

require_once '../includes/functions.php';

// DELETE engagement if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['delete_eng_id'])) {
    $engId = $_POST['delete_eng_id'];

    $stmt = $conn->prepare("DELETE FROM engagements WHERE eng_idno = ?");
    $stmt->bind_param("s", $engId);

    if ($stmt->execute()) {
        header("Location: dashboard.php?message=Engagement deleted successfully");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Failed to delete engagement.</div>";
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

<div class="mt-4"></div>

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
                  <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                      <form method="POST">
                        <div class="modal-header">
                          <h5 class="modal-title" id="editModalLabel-<?php echo $eng['eng_idno']; ?>">Edit Engagement</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                          <input type="hidden" name="edit_eng_id" value="<?php echo $eng['eng_idno']; ?>">
                          <div class="mb-3">
                            <label for="eng_name-<?php echo $eng['eng_idno']; ?>" class="form-label">Engagement Name</label>
                            <input type="text" class="form-control" id="eng_name-<?php echo $eng['eng_idno']; ?>" name="eng_name" value="<?php echo htmlspecialchars($eng['eng_name']); ?>" required>
                          </div>
                          <div class="mb-3">
                            <label for="eng_manager-<?php echo $eng['eng_idno']; ?>" class="form-label">Manager</label>
                            <input type="text" class="form-control" id="eng_manager-<?php echo $eng['eng_idno']; ?>" name="eng_manager" value="<?php echo htmlspecialchars($eng['eng_manager']); ?>" required>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
