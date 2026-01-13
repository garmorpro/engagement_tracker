<?php
      // Map engagement status to background, border, and pill colors
      $statusColors = [
          'on-hold' => [
              'bg' => 'rgb(249,250,251)',
              'border' => 'rgb(229,231,235)',
              'pill' => 'rgb(105,114,129)',
          ],
          'planning' => [
              'bg' => 'rgb(238,246,254)',
              'border' => 'rgb(187,219,253)',
              'pill' => 'rgb(33,128,255)',
          ],
          'in-progress' => [
              'bg' => 'rgb(255,247,238)',
              'border' => 'rgb(255,214,171)',
              'pill' => 'rgb(255,103,0)',
          ],
          'in-review' => [
              'bg' => 'rgb(251,245,254)',
              'border' => 'rgb(236,213,254)',
              'pill' => 'rgb(181,72,255)',
          ],
          'complete' => [
              'bg' => 'rgb(239,253,245)',
              'border' => 'rgb(176,248,209)',
              'pill' => 'rgb(0,201,92)',
          ],
          'archived' => [
              'bg' => 'rgb(249,250,251)',
              'border' => 'rgb(229,231,235)',
              'pill' => 'rgb(105,114,129)',
          ],
      ];

      // Fallback in case status is unexpected
      $status = $eng['eng_status'];
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
     TEXT FIELDS
===================== -->

<h6 class="fw-semibold mt-4">
  Basic Information
</h6>
<hr>

<?php
$checked = (($eng['eng_repeat'] ?? 'N') === 'Y');
?>
<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">
    Engagement ID<sup>*</sup>
  </label>
  <div class="d-flex align-items-center gap-2">

    <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;"
           name="<?php echo $eng['eng_idno'] ?? ''; ?>"
           value="<?php echo $eng['eng_idno'] ?? ''; ?>">

    <div class="yn-toggle <?php echo $checked ? 'active' : ''; ?>"
         onclick="toggleYN(this)">
      <?php echo $checked ? '<i class="bi bi-check"></i> Y' : 'N'; ?>
      <!-- <br><br>  -->
    </div>

    <!-- ALWAYS POST -->
    <input type="hidden" name="repeat" value="<?php echo $checked ? 'Y' : 'N'; ?>">

  </div>
</div>



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

<div class="col-12 mb-3 engagement-status-container">
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
        ?>
        <?php foreach ($statuses as $key => $s): 
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
    <!-- Use class instead of ID -->
    <input type="hidden" name="eng_status" class="eng_status_input" value="<?php echo htmlspecialchars($eng['eng_status'] ?? ''); ?>">
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Loop over each container separately
    document.querySelectorAll('.engagement-status-container').forEach(container => {
        const cards = container.querySelectorAll('.status-card');
        const hiddenInput = container.querySelector('.eng_status_input');

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

        cards.forEach(card => {
            const status = card.dataset.status;
            const isSelected = hiddenInput.value === status;
            applyColors(card, status, isSelected);

            card.addEventListener('click', () => {
                hiddenInput.value = status;
                // Reset all cards in this container
                cards.forEach(c => applyColors(c, c.dataset.status, c.dataset.status === status));
            });

            card.addEventListener('mouseenter', () => applyColors(card, status, true));
            card.addEventListener('mouseleave', () => applyColors(card, status, hiddenInput.value === status));
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


<?php
// Get selected audits from DB (comma-separated)
$selectedAudits = explode(',', $eng['eng_audit_type'] ?? []);
$selectedAudits = array_map('trim', $selectedAudits);

// Helper function to get DOL value from DB
function getDOL($eng, $audit, $role, $index) {
    $soc = '';
    if(strpos($audit, 'SOC 1') !== false) $soc = 'soc1';
    elseif(strpos($audit, 'SOC 2') !== false) $soc = 'soc2';
    $field = "eng_{$soc}_" . strtolower($role) . $index . "_dol";
    return $eng[$field] ?? '';
}
?>

<h6 class="fw-semibold mt-5">Team Members</h6>
<hr>

<div class="row g-3">

  <!-- Manager -->
  <div class="col-md-12">
    <label class="form-label fw-semibold" style="font-size: 12px;">Manager</label>
    <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_manager"
           value="<?php echo htmlspecialchars($eng['eng_manager'] ?? '', ENT_QUOTES); ?>">
  </div>

  <!-- Senior 1 -->
  <div class="col-md-6">
    <label class="form-label fw-semibold" style="font-size: 12px;">Senior 1</label>
    <input type="text" class="form-control team-input senior-input" style="background-color:#f3f3f5;" data-role="Senior" data-index="1" name="eng_senior1"
           value="<?php echo htmlspecialchars($eng['eng_senior1'] ?? '', ENT_QUOTES); ?>">
    <div class="dol-container mt-2" id="dol-senior-1">
      <?php foreach($selectedAudits as $audit):
        if(!str_contains($audit, 'SOC 1') && !str_contains($audit, 'SOC 2')) continue;
        $val = getDOL($eng, $audit, 'Senior', 1);
      ?>
        <label class="form-label fw-semibold mb-1" style="font-size:12px;"><?php echo $audit; ?> DOL</label>
        <input type="text" class="form-control mb-2" style="font-size:14px; background-color: rgb(243,243,245);"
               name="eng_<?php echo strtolower(str_replace(' ', '', strstr(strtolower($audit),'soc'))); ?>_senior1_dol"
               value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>">
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Senior 2 -->
  <div class="col-md-6">
    <label class="form-label fw-semibold" style="font-size: 12px;">Senior 2</label>
    <input type="text" class="form-control team-input senior-input" style="background-color:#f3f3f5;" data-role="Senior" data-index="2" name="eng_senior2"
           value="<?php echo htmlspecialchars($eng['eng_senior2'] ?? '', ENT_QUOTES); ?>">
    <div class="dol-container mt-2" id="dol-senior-2">
      <?php foreach($selectedAudits as $audit):
        if(!str_contains($audit, 'SOC 1') && !str_contains($audit, 'SOC 2')) continue;
        $val = getDOL($eng, $audit, 'Senior', 2);
      ?>
        <label class="form-label fw-semibold mb-1" style="font-size:12px;"><?php echo $audit; ?> DOL</label>
        <input type="text" class="form-control mb-2" style="font-size:14px; background-color: rgb(243,243,245);"
               name="eng_<?php echo strtolower(str_replace(' ', '', strstr(strtolower($audit),'soc'))); ?>_senior2_dol"
               value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>">
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Staff 1 -->
  <div class="col-md-6">
    <label class="form-label fw-semibold" style="font-size: 12px;">Staff 1</label>
    <input type="text" class="form-control team-input staff-input" style="background-color:#f3f3f5;" data-role="Staff" data-index="1" name="eng_staff1"
           value="<?php echo htmlspecialchars($eng['eng_staff1'] ?? '', ENT_QUOTES); ?>">
    <div class="dol-container mt-2" id="dol-staff-1">
      <?php foreach($selectedAudits as $audit):
        if(!str_contains($audit, 'SOC 1') && !str_contains($audit, 'SOC 2')) continue;
        $val = getDOL($eng, $audit, 'Staff', 1);
      ?>
        <label class="form-label fw-semibold mb-1" style="font-size:12px;"><?php echo $audit; ?> DOL</label>
        <input type="text" class="form-control mb-2" style="font-size:14px; background-color: rgb(243,243,245);"
               name="eng_<?php echo strtolower(str_replace(' ', '', strstr(strtolower($audit),'soc'))); ?>_staff1_dol"
               value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>">
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Staff 2 -->
  <div class="col-md-6">
    <label class="form-label fw-semibold" style="font-size: 12px;">Staff 2</label>
    <input type="text" class="form-control team-input staff-input" style="background-color:#f3f3f5;" data-role="Staff" data-index="2" name="eng_staff2"
           value="<?php echo htmlspecialchars($eng['eng_staff2'] ?? '', ENT_QUOTES); ?>">
    <div class="dol-container mt-2" id="dol-staff-2">
      <?php foreach($selectedAudits as $audit):
        if(!str_contains($audit, 'SOC 1') && !str_contains($audit, 'SOC 2')) continue;
        $val = getDOL($eng, $audit, 'Staff', 2);
      ?>
        <label class="form-label fw-semibold mb-1" style="font-size:12px;"><?php echo $audit; ?> DOL</label>
        <input type="text" class="form-control mb-2" style="font-size:14px; background-color: rgb(243,243,245);"
               name="eng_<?php echo strtolower(str_replace(' ', '', strstr(strtolower($audit),'soc'))); ?>_staff2_dol"
               value="<?php echo htmlspecialchars($val, ENT_QUOTES); ?>">
      <?php endforeach; ?>
    </div>
  </div>

</div>

<script>
  document.addEventListener('DOMContentLoaded', () => {

  const getSelectedAudits = () => {
    const audits = document.querySelector('.eng_audit_input').value.split(',').map(a => a.trim());
    return audits.filter(a => a.includes('SOC 1') || a.includes('SOC 2'));
  };

  const teamInputs = document.querySelectorAll('.team-input');

  teamInputs.forEach(input => {
    input.addEventListener('input', () => {
      const containerId = `dol-${input.dataset.role.toLowerCase()}-${input.dataset.index}`;
      const container = document.getElementById(containerId);

      if(input.value.trim() === '') return;

      const audits = getSelectedAudits();
      if(audits.length === 0) return;

      // Build a map of existing DOL inputs so we don't duplicate
      const existing = {};
      container.querySelectorAll('input').forEach(inp => {
        existing[inp.name] = inp.value;
      });

      audits.forEach(audit => {
        let socName = audit.toLowerCase();
        if (socName.includes('soc 1')) socName = 'soc1';
        else if (socName.includes('soc 2')) socName = 'soc2';
        const dolName = `eng_${socName}_${input.dataset.role.toLowerCase()}${input.dataset.index}_dol`;

        if(!existing[dolName]) {
          const label = document.createElement('label');
          label.classList.add('form-label', 'fw-semibold', 'mb-1');
          label.style.fontSize = '12px';
          label.textContent = `${audit} DOL`;

          const inputEl = document.createElement('input');
          inputEl.type = 'text';
          inputEl.name = dolName;
          inputEl.classList.add('form-control', 'mb-2');
          inputEl.style.fontSize = '14px';
          inputEl.style.backgroundColor = 'rgb(243,243,245)';

          container.appendChild(label);
          container.appendChild(inputEl);
        }
      });
    });
  });

});

</script>


<!-- <h6 class="fw-semibold mt-5">Team Members</h6>
<hr>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Manager</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_manager"
         value="<?php echo htmlspecialchars($eng['eng_manager'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Senior 1</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_senior"
         value="<?php echo htmlspecialchars($eng['eng_senior'] ?? '', ENT_QUOTES); ?>">
</div>

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Staff 1</label>
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
</div> -->


<h6 class="fw-semibold mt-5">Client Information</h6>
<hr>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Point of Contact</label>
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

<div class="col-md-6">
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
  <label class="form-label fw-semibold"  style="font-size: 12px; color: rgb(10,10,10);">Notes</label>
  <textarea class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_notes" rows="4"><?php
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