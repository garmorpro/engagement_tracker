<?php
// Map engagement status to background, border, and pill colors
$statusColors = [
    'on-hold' => ['bg' => 'rgb(249,250,251)', 'border' => 'rgb(229,231,235)', 'pill' => 'rgb(105,114,129)'],
    'planning' => ['bg' => 'rgb(238,246,254)', 'border' => 'rgb(187,219,253)', 'pill' => 'rgb(33,128,255)'],
    'in-progress' => ['bg' => 'rgb(255,247,238)', 'border' => 'rgb(255,214,171)', 'pill' => 'rgb(255,103,0)'],
    'in-review' => ['bg' => 'rgb(251,245,254)', 'border' => 'rgb(236,213,254)', 'pill' => 'rgb(181,72,255)'],
    'complete' => ['bg' => 'rgb(239,253,245)', 'border' => 'rgb(176,248,209)', 'pill' => 'rgb(0,201,92)'],
    'archived' => ['bg' => 'rgb(249,250,251)', 'border' => 'rgb(229,231,235)', 'pill' => 'rgb(105,114,129)'],
];

$status = $eng['eng_status'] ?? '';
$bgColor = $statusColors[$status]['bg'] ?? '#fff';
$borderColor = $statusColors[$status]['border'] ?? '#ccc';
$pillColor = $statusColors[$status]['pill'] ?? '#000';
?>

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
                 BASIC INFORMATION
            ===================== -->
            <h6 class="fw-semibold mt-4">Basic Information</h6>
            <hr>

            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px;">Engagement ID<sup>*</sup></label>
              <input type="text" class="form-control" style="background-color: rgb(243,243,245);" name="eng_idno"
                     value="<?php echo $eng['eng_idno'] ?? ''; ?>" readonly>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold" style="font-size: 12px;">Engagement Name<sup>*</sup></label>
              <input type="text" class="form-control" style="background-color: rgb(243,243,245);" name="eng_name"
                     value="<?php echo htmlspecialchars($eng['eng_name'] ?? '', ENT_QUOTES); ?>">
            </div>

            <!-- Audit Types (readonly for edit) -->
            <div class="col-md-12">
              <label class="form-label fw-semibold" style="font-size:12px;">Audit Type</label>
              <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_audit_type"
                     value="<?php echo htmlspecialchars($eng['eng_audit_type'] ?? '', ENT_QUOTES); ?>" readonly>
            </div>

            <!-- =====================
                 STATUS
            ===================== -->
            <div class="col-12 mb-3 engagement-status-container">
              <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
              <div class="d-flex gap-2 flex-wrap">
                <?php
                $statuses = [
                  'on-hold' => ['label'=>'On Hold','icon'=>'bi-pause-circle'],
                  'planning' => ['label'=>'Planning','icon'=>'bi-clock'],
                  'in-progress' => ['label'=>'In Progress','icon'=>'bi-play-circle'],
                  'in-review' => ['label'=>'In Review','icon'=>'bi-eye'],
                  'complete' => ['label'=>'Completed','icon'=>'bi-check2-circle'],
                  'archived' => ['label'=>'Archived','icon'=>'bi-archive'],
                ];
                $selectedStatus = $eng['eng_status'] ?? '';
                ?>
                <?php foreach($statuses as $key => $s): ?>
                  <div class="status-card text-center p-2 flex-fill"
                       data-status="<?php echo $key; ?>"
                       style="
                         cursor:pointer;
                         border:2px solid <?php echo ($selectedStatus === $key)?'rgb(68,125,252)':'rgb(229,231,235)'; ?>;
                         background-color: <?php echo ($selectedStatus === $key)?'rgb(238,246,254)':'#fff'; ?>;
                         color: <?php echo ($selectedStatus === $key)?'rgb(33,128,255)':'rgb(76,85,100)'; ?>;
                         border-radius:1rem;
                       ">
                    <i class="bi <?php echo $s['icon']; ?>"></i>
                    <div style="font-size:12px;"><?php echo $s['label']; ?></div>
                  </div>
                <?php endforeach; ?>
              </div>
              <input type="hidden" name="eng_status" class="eng_status_input" value="<?php echo $selectedStatus; ?>">
            </div>

            <div class="col-md-12">
              <label class="form-label fw-semibold" style="font-size:12px;">TSC</label>
              <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_tsc"
                     value="<?php echo htmlspecialchars($eng['eng_tsc'] ?? '', ENT_QUOTES); ?>">
            </div>

            <!-- =====================
                 DATE FIELDS
            ===================== -->
            <?php
            $dateFields = [
              'eng_start_period'=>'Start Period',
              'eng_end_period'=>'End Period',
              'eng_as_of_date'=>'As Of Date',
              'eng_archive'=>'Archive Date',
              'eng_last_communication'=>'Last Communication'
            ];
            foreach($dateFields as $field => $label):
            ?>
            <div class="col-md-4">
              <label class="form-label fw-semibold" style="font-size:12px;"><?php echo $label; ?></label>
              <input type="date" class="form-control" style="background-color:#f3f3f5;" name="<?php echo $field; ?>"
                     value="<?php echo $eng[$field] ?? ''; ?>">
            </div>
            <?php endforeach; ?>

            <!-- =====================
                 TEAM MEMBERS WITH DOLS
            ===================== -->
            <h6 class="fw-semibold mt-5">Team Members</h6>
            <hr>

            <!-- Manager -->
            <div class="col-md-12 mb-2">
              <label class="form-label fw-semibold" style="font-size:12px;">Manager</label>
              <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_manager"
                     value="<?php echo htmlspecialchars($eng['eng_manager'] ?? '', ENT_QUOTES); ?>">
            </div>

            <?php
            // Loop Seniors and Staff 1 & 2
            $roles = ['senior','staff'];
            foreach($roles as $role):
                for($i=1;$i<=2;$i++):
                    $person = $eng["eng_{$role}{$i}"] ?? '';
                    if(!$person) continue;
            ?>
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold" style="font-size:12px;"><?php echo ucfirst($role) . " {$i}"; ?></label>
              <input type="text" class="form-control team-input" style="background-color:#f3f3f5;"
                     name="eng_<?php echo $role.$i; ?>" value="<?php echo htmlspecialchars($person, ENT_QUOTES); ?>">

              <?php
              // Display SOC DOLs dynamically
              $soc1 = $eng["eng_soc1_{$role}{$i}_dol"] ?? '';
              $soc2 = $eng["eng_soc2_{$role}{$i}_dol"] ?? '';
              if($soc1 || $soc2):
              ?>
              <div class="dol-container mt-1">
                  <?php if($soc1): ?>
                  <p style="font-size:12px;"><strong>SOC 1 DOL:</strong> <?php echo htmlspecialchars($soc1); ?></p>
                  <?php endif; ?>
                  <?php if($soc2): ?>
                  <p style="font-size:12px;"><strong>SOC 2 DOL:</strong> <?php echo htmlspecialchars($soc2); ?></p>
                  <?php endif; ?>
              </div>
              <?php endif; ?>
            </div>
            <?php
                endfor;
            endforeach;
            ?>

            <!-- =====================
                 CLIENT INFORMATION
            ===================== -->
            <h6 class="fw-semibold mt-5">Client Information</h6>
            <hr>

            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold" style="font-size:12px;">POC</label>
              <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_poc"
                     value="<?php echo htmlspecialchars($eng['eng_poc'] ?? '', ENT_QUOTES); ?>">
            </div>

            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold" style="font-size:12px;">Location</label>
              <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_location"
                     value="<?php echo htmlspecialchars($eng['eng_location'] ?? '', ENT_QUOTES); ?>">
            </div>

            <div class="col-md-12 mb-2">
              <label class="form-label fw-semibold" style="font-size:12px;">Scope</label>
              <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_scope"
                     value="<?php echo htmlspecialchars($eng['eng_scope'] ?? '', ENT_QUOTES); ?>">
            </div>

            <!-- =====================
                 DATE + TOGGLE PAIRS
            ===================== -->
            <?php
            $pairs = [
              'eng_internal_planning_call'=>'eng_completed_internal_planning',
              'eng_irl_due'=>'eng_irl_sent',
              'eng_client_planning_call'=>'eng_completed_client_planning',
              'eng_fieldwork'=>'eng_fieldwork_complete',
              'eng_leadsheet_due'=>'eng_leadsheet_complete',
              'eng_draft_due'=>'eng_draft_sent',
              'eng_final_due'=>'eng_final_sent'
            ];
            foreach($pairs as $date => $yn):
            $checked = (($eng[$yn] ?? 'N')==='Y');
            ?>
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold" style="font-size:12px;"><?php echo ucwords(str_replace('_',' ',$date)); ?></label>
              <div class="d-flex align-items-center gap-2">
                <input type="date" class="form-control" style="background-color:#f3f3f5;" name="<?php echo $date; ?>"
                       value="<?php echo $eng[$date] ?? ''; ?>">
                <div class="yn-toggle <?php echo $checked?'active':''; ?>" onclick="toggleYN(this)">
                  <?php echo $checked?'✓ Y':'N'; ?>
                </div>
                <input type="hidden" name="<?php echo $yn; ?>" value="<?php echo $checked?'Y':'N'; ?>">
              </div>
            </div>
            <?php endforeach; ?>

            <!-- =====================
                 SECTION 3 REQUESTED
            ===================== -->
            <div class="col-md-6 mb-2">
              <label class="form-label fw-semibold" style="font-size:12px;">Section 3 Requested</label>
              <select class="form-select" style="background-color:#f3f3f5;" name="eng_section_3_requested">
                <option value="Y" <?php echo (($eng['eng_section_3_requested'] ?? 'N')==='Y')?'selected':''; ?>>Yes</option>
                <option value="N" <?php echo (($eng['eng_section_3_requested'] ?? 'N')==='N')?'selected':''; ?>>No</option>
              </select>
            </div>

            <!-- =====================
                 NOTES
            ===================== -->
            <div class="col-12 mb-2">
              <label class="form-label fw-semibold" style="font-size:12px;">Notes</label>
              <textarea class="form-control" style="background-color:#f3f3f5;" name="eng_notes" rows="4"><?php echo htmlspecialchars($eng['eng_notes'] ?? '', ENT_QUOTES); ?></textarea>
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
