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
  <label class="form-label fw-semibold" style="font-size:12px;">Engagement ID</label>
  <input type="text" class="form-control" style="background-color:#f3f3f5;"
         name="eng_idno" value="<?php echo $nextEngId; ?>" readonly>
</div>

<div class="col-md-6">
  <label class="form-label fw-semibold" style="font-size:12px;">Engagement Name</label>
  <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_name">
</div>

<!-- =====================
     AUDIT TYPE (MULTI)
===================== -->

<div class="col-12 mb-3">
  <label class="form-label fw-semibold" style="font-size:12px;">Audit Type</label>
  <div class="d-flex gap-2 flex-wrap">

<?php
$auditTypes = [
  'SOC 1 Type 1' => 'bi-check-circle',
  'SOC 1 Type 2' => 'bi-check-circle',
  'SOC 2 Type 1' => 'bi-shield-lock',
  'SOC 2 Type 2' => 'bi-shield-lock',
  'PCI'          => 'bi-credit-card'
];
foreach ($auditTypes as $label => $icon):
?>
    <div class="audit-card text-center p-2 flex-fill"
         data-value="<?php echo $label; ?>"
         style="cursor:pointer;border:2px solid #e5e7eb;border-radius:1rem;">
      <i class="bi <?php echo $icon; ?>"></i>
      <div style="font-size:12px;"><?php echo $label; ?></div>
    </div>
<?php endforeach; ?>

  </div>
  <input type="hidden" name="eng_audit_type" id="auditInput">
</div>

<!-- =====================
     STATUS (SINGLE)
===================== -->

<?php
$statuses = [
  'planning'  => ['label'=>'Planning','icon'=>'bi-calendar-event'],
  'fieldwork' => ['label'=>'Fieldwork','icon'=>'bi-clipboard-data'],
  'review'    => ['label'=>'Review','icon'=>'bi-search'],
  'issued'    => ['label'=>'Issued','icon'=>'bi-check-circle'],
  'archived'  => ['label'=>'Archived','icon'=>'bi-archive']
];
?>

<div class="col-12 mb-3">
  <label class="form-label fw-semibold" style="font-size:12px;">Status</label>
  <div class="d-flex gap-2 flex-wrap">

<?php foreach ($statuses as $key => $s): ?>
    <div class="status-card text-center p-2 flex-fill"
         data-value="<?php echo $key; ?>"
         style="cursor:pointer;border:2px solid #e5e7eb;border-radius:1rem;">
      <i class="bi <?php echo $s['icon']; ?>"></i>
      <div style="font-size:12px;"><?php echo $s['label']; ?></div>
    </div>
<?php endforeach; ?>

  </div>
  <input type="hidden" name="eng_status" id="statusInput">
</div>

<!-- =====================
     TSC
===================== -->

<div class="col-md-12">
  <label class="form-label fw-semibold" style="font-size:12px;">TSC</label>
  <input type="text" class="form-control" style="background-color:#f3f3f5;" name="eng_tsc">
</div>

<!-- =====================
     DATES
===================== -->

<?php
$dates = [
  'eng_start_period' => 'Start Period',
  'eng_end_period'   => 'End Period',
  'eng_as_of_date'   => 'As Of Date'
];
foreach ($dates as $f => $l):
?>
<div class="col-md-4">
  <label class="form-label fw-semibold" style="font-size:12px;"><?php echo $l; ?></label>
  <input type="date" class="form-control" style="background-color:#f3f3f5;" name="<?php echo $f; ?>">
</div>
<?php endforeach; ?>

<!-- =====================
     NOTES
===================== -->

<div class="col-12">
  <label class="form-label fw-semibold" style="font-size:12px;">Notes</label>
  <textarea class="form-control" style="background-color:#f3f3f5;" name="eng_notes" rows="4"></textarea>
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

<!-- =====================
     JAVASCRIPT
===================== -->

<script>
document.addEventListener('DOMContentLoaded', () => {

  /* AUDIT TYPE (MULTI) */
  const auditCards = document.querySelectorAll('.audit-card');
  const auditInput = document.getElementById('auditInput');

  auditCards.forEach(card => {
    card.addEventListener('click', () => {
      card.classList.toggle('selected');
      card.style.background = card.classList.contains('selected') ? '#e0e9ff' : '#fff';
      card.style.borderColor = card.classList.contains('selected') ? '#c2d5ff' : '#e5e7eb';

      auditInput.value = [...auditCards]
        .filter(c => c.classList.contains('selected'))
        .map(c => c.dataset.value)
        .join(',');
    });
  });

  /* STATUS (SINGLE) */
  const statusCards = document.querySelectorAll('.status-card');
  const statusInput = document.getElementById('statusInput');

  statusCards.forEach(card => {
    card.addEventListener('click', () => {
      statusCards.forEach(c => {
        c.classList.remove('selected');
        c.style.background = '#fff';
        c.style.borderColor = '#e5e7eb';
      });

      card.classList.add('selected');
      card.style.background = '#e0e9ff';
      card.style.borderColor = '#c2d5ff';
      statusInput.value = card.dataset.value;
    });
  });

});
</script>
