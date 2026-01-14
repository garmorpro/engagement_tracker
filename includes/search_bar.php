<!-- Search bar -->
          <div class="row align-items-center mt-4" style="margin-left: 210px; margin-right: 210px;">
  <div class="p-3 border d-flex align-items-center" style="box-shadow: 1px 1px 4px rgba(0,0,0,0.15); border-radius: 15px; position: relative;">
    <div class="input-group flex-grow-1 me-3">
      <span class="input-group-text border-end-0" style="background-color: rgb(248,249,251); color: rgb(142,151,164);">
          <i class="bi bi-search"></i>
      </span>
      <input type="text" class="form-control border-start-0" style="background-color: rgb(248,249,251);" placeholder="Search..." id="engagementSearch" autocomplete="off">
    </div>

    <!-- Dropdown results -->
    <div id="searchResults" class="dropdown-menu w-100" style="display: none; max-height: 300px; overflow-y: auto; position: absolute; top: 100%; left: 0; z-index: 1000;"></div>
  </div>
</div>


<script>
const searchInput = document.getElementById('engagementSearch');
const resultsContainer = document.getElementById('searchResults');
let timeout = null;

searchInput.addEventListener('input', () => {
  const query = searchInput.value.trim();

  if (query.length < 3) {
    resultsContainer.style.display = 'none';
    resultsContainer.innerHTML = '';
    return;
  }

  clearTimeout(timeout);
  timeout = setTimeout(() => {
    fetch(`../includes/search_engagements.php?q=${encodeURIComponent(query)}`)
      .then(res => res.json())
      .then(data => {
        if (data.length === 0) {
          resultsContainer.innerHTML = '<div class="dropdown-item text-muted">No results found</div>';
          resultsContainer.style.display = 'block';
          return;
        }

        let html = '';
        data.forEach(row => {
          html += `<div class="dropdown-item" style="cursor:pointer;" data-eng-id="${row.eng_id}">
                      ${row.eng_name} (${row.eng_idno})
                   </div>`;
        });
        resultsContainer.innerHTML = html;
        resultsContainer.style.display = 'block';

        // Redirect to engagement_details.php on click
        const items = resultsContainer.querySelectorAll('.dropdown-item');
        items.forEach(item => {
          item.addEventListener('click', () => {
            const engId = item.dataset.engId;
            if (engId) {
              window.location.href = `engagement-details.php?eng_id=${engId}`;
            }
          });
        });
      });
  }, 300);
});

// Hide dropdown if clicked outside
document.addEventListener('click', (e) => {
  if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
    resultsContainer.style.display = 'none';
  }
});


</script>