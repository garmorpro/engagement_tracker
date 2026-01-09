// Select all Kanban columns
const columns = document.querySelectorAll('.kanban-column .card-body');

columns.forEach(column => {
  new Sortable(column, {
    group: 'kanban',           // allows dragging between columns
    animation: 150,
    ghostClass: 'kanban-ghost',

    // Event fired when drag ends
    onEnd: function(evt) {
      const card = evt.item; // dragged element
      const newStatus = evt.to.closest('.kanban-column')?.dataset?.status;
      const engId = card.dataset.engId;

      // --- DEBUG LOGGING ---
      console.log('Dragged card:', card);
      console.log('Engagement ID (from data-eng-id):', engId);
      console.log('New Status (from column data-status):', newStatus);

      if (!engId || !newStatus) {
        console.warn('Missing eng_id or new_status — request will not be sent.');
        return; // stop execution if data is missing
      }

      // --- AJAX POST to update status ---
      fetch('update-engagement-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ eng_id: engId, new_status: newStatus })
      })
      .then(res => res.json())
      .then(data => {
        console.log('Server response:', data);

        if (data.success) {
          console.log(`✅ Engagement ${engId} successfully moved to ${newStatus}`);
        } else {
          console.error('❌ Failed to update status:', data.error || 'Unknown error');
          alert('Failed to update status. Check console for details.');
        }
      })
      .catch(err => {
        console.error('Fetch error:', err);
        alert('Network or server error. Check console for details.');
      });
    }
  });
});
