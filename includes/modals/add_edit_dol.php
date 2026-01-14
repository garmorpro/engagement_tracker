<!-- Add DOL Modal -->
<div class="modal fade" id="addDOLModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <form id="addDOLForm" method="post">

        <!-- HEADER -->
        <div class="modal-header border-0">
          <div class="d-flex align-items-center gap-3">
            <div class="dol-header-icon">
              <i class="bi bi-clipboard-check"></i>
            </div>
            <div>
              <h4 class="mb-0 fw-semibold">Division of Labor</h4>
              <small class="text-muted">TechStart Inc Review</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body pt-2">

          <!-- AUDIT TYPE -->
          <div class="dol-card dol-blue mb-4">
            <div class="fw-semibold text-primary">Audit Type</div>
            <div class="fs-5 fw-bold text-primary">SOC 1</div>
            <small class="text-primary">
              Use CO prefix for SOC 1 (e.g., CO1, CO2, CO3)
            </small>
          </div>

          <!-- SENIOR -->
          <div class="dol-card dol-purple mb-4">
            <div class="fw-bold text-purple mb-3">
              SENIOR 1: ROBERT LEE
            </div>

            <label class="fw-semibold text-purple">
              SOC 1 Division of Labor (e.g., CO1, CO2, CO3)
            </label>
            <input type="text" class="form-control"
                   name="add[seniors][1][soc1]"
                   placeholder="CO1, CO2, CO3">
          </div>

          <!-- STAFF -->
          <div class="dol-card dol-green">
            <div class="fw-bold text-success mb-3">
              STAFF 1: EMILY CHEN
            </div>

            <label class="fw-semibold text-success">
              SOC 1 Division of Labor (e.g., CO1, CO2, CO3)
            </label>
            <input type="text" class="form-control"
                   name="add[staff][1][soc1]"
                   placeholder="CO4, CO5">
          </div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-1"></i> Save DOL Assignments
          </button>
        </div>

      </form>
    </div>
  </div>
</div>



<!-- Edit DOL Modal -->
<div class="modal fade" id="editDOLModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">

      <form id="editDOLForm" method="post">

        <!-- HEADER -->
        <div class="modal-header border-0">
          <div class="d-flex align-items-center gap-3">
            <div class="dol-header-icon">
              <i class="bi bi-clipboard-check"></i>
            </div>
            <div>
              <h4 class="mb-0 fw-semibold">Division of Labor</h4>
              <small class="text-muted">TechStart Inc Review</small>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <!-- BODY -->
        <div class="modal-body pt-2">

          <!-- AUDIT TYPE -->
          <div class="dol-card dol-blue mb-4">
            <div class="fw-semibold text-primary">Audit Type</div>
            <div class="fs-5 fw-bold text-primary">SOC 1</div>
            <small class="text-primary">
              Use CO prefix for SOC 1 (e.g., CO1, CO2, CO3)
            </small>
          </div>

          <!-- SENIOR -->
          <div class="dol-card dol-purple mb-4">
            <div class="fw-bold text-purple mb-3">
              SENIOR 1: ROBERT LEE
            </div>

            <label class="fw-semibold text-purple">
              SOC 1 Division of Labor (e.g., CO1, CO2, CO3)
            </label>
            <input type="text" class="form-control"
                   name="edit[seniors][1][soc1]"
                   value="CO1, CO2, CO3">
          </div>

          <!-- STAFF -->
          <div class="dol-card dol-green">
            <div class="fw-bold text-success mb-3">
              STAFF 1: EMILY CHEN
            </div>

            <label class="fw-semibold text-success">
              SOC 1 Division of Labor (e.g., CO1, CO2, CO3)
            </label>
            <input type="text" class="form-control"
                   name="edit[staff][1][soc1]"
                   value="CO4, CO5">
          </div>

        </div>

        <!-- FOOTER -->
        <div class="modal-footer border-0">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">
            Cancel
          </button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bi bi-save me-1"></i> Save DOL Assignments
          </button>
        </div>

      </form>
    </div>
  </div>
</div>
