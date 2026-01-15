<!-- Search bar -->
<div class="row align-items-center mt-4" style="margin-left: 210px; margin-right: 210px;">
  <div class="p-3 border d-flex align-items-center"
       style="box-shadow: 1px 1px 4px rgba(0,0,0,0.15); border-radius: 15px; position: relative;">

    <div class="input-group flex-grow-1 me-3">
      <span class="input-group-text border-end-0"
            style="background-color: rgb(248,249,251); color: rgb(142,151,164);">
        <i class="bi bi-search"></i>
      </span>
      <input type="text"
             class="form-control border-start-0"
             style="background-color: rgb(248,249,251);"
             placeholder="Search engagements..."
             id="engagementSearch"
             autocomplete="off">
    </div>

    <!-- Dropdown results -->
    <div id="searchResults"
         class="dropdown-menu w-100"
         style="display:none; max-height:300px; overflow-y:auto;
                position:absolute; top:100%; left:0; z-index:1000;">
    </div>

  </div>
</div>



<script>
const searchInput = document.getElementById('engagementSearch');
const resultsContainer = document.getElementById('searchResults');
let timeout = null;

searchInput.addEventListener('input', () => {
  const query = searchInput.value.trim();

  if (query.length < 2) {
    resultsContainer.style.display = 'none';
    resultsContainer.innerHTML = '';
    return;
  }

  clearTimeout(timeout);
  timeout = setTimeout(() => {
    fetch(`../includes/search_engagements.php?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        let html = '';

        if (data.length > 0) {
          data.forEach(row => {
            html += `
              <div class="dropdown-item search-result"
                   style="cursor:pointer;"
                   data-eng-id="${row.eng_id}">
                <strong>${row.eng_name}</strong>
                <small class="text-muted ms-2">(${row.eng_idno})</small>
              </div>
            `;
          });
        } else {
          // CREATE NEW OPTION
          html += `
            <div class="dropdown-item text-primary create-engagement"
                 style="cursor:pointer;"
                 data-name="${query}">
              <i class="bi bi-plus-circle me-2"></i>
              Create new engagement: <strong>"${query}"</strong>
            </div>
          `;
        }

        resultsContainer.innerHTML = html;
        resultsContainer.style.display = 'block';

        // Existing engagement click
        resultsContainer.querySelectorAll('.search-result').forEach(item => {
          item.addEventListener('click', () => {
            const engId = item.dataset.engId;
            if (engId) {
              window.location.href = `engagement-details.php?eng_id=${engId}`;
            }
          });
        });

        // Create new engagement click
        const createItem = resultsContainer.querySelector('.create-engagement');
        if (createItem) {
          createItem.addEventListener('click', () => {
            const name = createItem.dataset.name;

            // Open modal
            const modal = new bootstrap.Modal(document.getElementById('addModal'));
            modal.show();

            // Prefill name (CHANGE ID IF NEEDED)
            setTimeout(() => {
              const nameInput = document.getElementById('eng_name');
              if (nameInput) {
                nameInput.value = name;
                nameInput.focus();
              }
            }, 300);

            resultsContainer.style.display = 'none';
          });
        }
      });
  }, 300);
});

// Hide dropdown when clicking outside
document.addEventListener('click', (e) => {
  if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
    resultsContainer.style.display = 'none';
  }
});
</script>
