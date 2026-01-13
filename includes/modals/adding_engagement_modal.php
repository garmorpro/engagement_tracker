<div class="modal fade" id="addModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
    <div class="modal-content">

<form method="POST">
<input type="hidden" name="action" value="add">

<div class="modal-header">
  <h5 class="modal-title">Add New Engagement</h5>
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
  <label class="form-label fw-semibold" style="font-size:12px;">Engagement ID<sup>*</sup></label>
  <input type="text"
         class="form-control"
         style="background-color:#f3f3f5;"
         name="eng_idno"
         value="<?php echo $nextEngId; ?>"
         readonly>
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size:12px;">Engagement Name<sup>*</sup></label>
  <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_name">
</div>

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size:12px;">Audit Type</label>
  <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_audit_type">
</div>

<!-- =====================
     STATUS
===================== -->

<div class="col-12 mb-3 engagement-status-container">
  <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
  <div class="d-flex gap-2 flex-wrap">

<?php foreach ($statuses as $key => $s): ?>
  <div class="status-card text-center p-2 flex-fill"
       data-status="<?php echo $key; ?>"
       style="
         cursor:pointer;
         border:2px solid rgb(229,231,235);
         background:#fff;
         color:rgb(76,85,100);
         border-radius:1rem;
       ">
    <i class="bi <?php echo $s['icon']; ?>"></i>
    <div style="font-size:12px;"><?php echo $s['label']; ?></div>
  </div>
<?php endforeach; ?>

  </div>
  <input type="hidden" name="eng_status" class="eng_status_input" value="">
</div>

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size:12px;">TSC</label>
  <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_tsc">
</div>

<!-- =====================
     DATE ONLY
===================== -->

<?php
$dateFields = [
  'eng_start_period' => 'Start Period',
  'eng_end_period'   => 'End Period',
  'eng_as_of_date'   => 'As Of Date'
];
foreach ($dateFields as $field => $label):
?>
<div class="col-md-4">
  <label class="form-label fw-semibold" style="font-size:12px;"><?php echo $label; ?></label>
  <input type="date" class="form-control" style="background-color:#f3f3f5;" name="<?php echo $field; ?>">
</div>
<?php endforeach; ?>

<!-- =====================
     TEAM MEMBERS
===================== -->

<h6 class="fw-semibold mt-5">Team Members</h6>
<hr>

<!-- <div class="col-md-6"><input class="form-control" name="eng_manager" placeholder="Manager"></div>
<div class="col-md-6"><input class="form-control" name="eng_senior" placeholder="Senior(s)"></div>
<div class="col-md-12"><input class="form-control" name="eng_staff" placeholder="Staff"></div>
<div class="col-md-6"><input class="form-control" name="eng_senior_dol" placeholder="Senior DOL"></div>
<div class="col-md-6"><input class="form-control" name="eng_staff_dol" placeholder="Staff DOL"></div> -->

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Manager</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_manager"
         value="">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Senior(s)</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_senior"
         value="">
</div>

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Staff</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_staff"
         value="">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Senior DOL</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_senior_dol"
         value="">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Staff DOL</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_staff_dol"
         value="">
</div>

<!-- =====================
     CLIENT INFO
===================== -->

<h6 class="fw-semibold mt-5">Client Information</h6>
<hr>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">POC</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_poc"
         value="">
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Location</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_location"
         value="">
</div>


<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size: 12px; color: rgb(10,10,10);">Scope</label>
  <input type="text" class="form-control" style="background-color: rgb(243,243,245); font-size: 14px;" name="eng_scope"
         value="">
</div>

<!-- =====================
     IMPORTANT DATES & MILESTONES
===================== -->

<h6 class="fw-semibold mt-5">Important Dates & Milestones</h6>
<hr>

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
           value="">

    <div class="yn-toggle <?php echo $checked ? 'active' : ''; ?>"
         onclick="toggleYN(this)">
      <?php echo $checked ? '✓ Y' : 'N'; ?>
    </div>

    <!-- ALWAYS POST -->
    <input type="date" name="<?php echo $yn; ?>" value="<?php echo $checked ? 'Y' : 'N'; ?>">

  </div>
</div>
<?php endforeach; ?>

<!-- <?php //foreach ($pairs as $date => $yn): ?>
<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size:12px;"><?php echo ucwords(str_replace('_',' ',$date)); ?></label>
  <div class="d-flex gap-2 align-items-center">
    <input type="date" class="form-control" name="<?php echo $date; ?>">
    <div class="yn-toggle" onclick="toggleYN(this)">N</div>
    <input type="hidden" name="<?php echo $yn; ?>" value="N">
  </div>
</div>
<?php //endforeach; ?> -->

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
  <label class="form-label fw-semibold" style="font-size:12px;"><?php echo $label; ?></label>
  <input type="date" class="form-control" style="background-color:#f3f3f5;" name="<?php echo $field; ?>">
</div>
<?php endforeach; ?>

<!-- =====================
     Y / N SELECTS
===================== -->

<div class="col-md-3">
  <label class="form-label fw-semibold" style="font-size:12px;">Repeat</label>
  <select class="form-select" style="background-color:#f3f3f5;" name="eng_repeat">
    <option value="N" selected>No</option>
    <option value="Y">Yes</option>
  </select>
</div>

<div class="col-md-3">
  <label class="form-label fw-semibold" style="font-size:12px;">Section 3 Requested</label>
  <select class="form-select" style="background-color:#f3f3f5;" name="eng_section_3_requested">
    <option value="N" selected>No</option>
    <option value="Y">Yes</option>
  </select>
</div>

<!-- =====================
     NOTES
===================== -->

<div class="col-12">
  <label class="form-label fw-semibold" style="font-size:12px;">Notes</label>
  <textarea class="form-control" name="eng_notes" rows="4"></textarea>
</div>

</div>
</div>

<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
  <button type="submit" class="btn btn-primary">Add Engagement</button>
</div>

</form>
</div>
</div>
</div>
