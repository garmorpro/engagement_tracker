// Select all column bodies
const columns = document.querySelectorAll('.kanban-column .card-body');

columns.forEach(column => {
  new Sortable(column, {
    group: 'kanban',       // allow dragging between columns
    animation: 150,
    ghostClass: 'kanban-ghost',
    
    // Make the whole card container draggable
    draggable: '.engagement-card-kanban-container', 

    // prevent inner elements from being separately draggable
    handle: null,          // allows dragging by clicking anywhere
    filter: '.no-drag',    // optional: can prevent some elements if needed
    preventOnFilter: false,

    onEnd: function(evt) {
      const cardContainer = evt.item; // top-level container
      const newStatus = evt.to.closest('.kanban-column')?.dataset?.status;
      const engId = cardContainer.dataset.engId;

      console.log('Dragged card container:', cardContainer);
      console.log('Engagement ID:', engId);
      console.log('New Status:', newStatus);

      if (!engId || !newStatus) return;

      // Send AJAX update to server
      fetch('update-engagement-status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ eng_id: engId, new_status: newStatus })
      })
      .then(res => res.json())
      .then(data => {
        console.log('Server response:', data);
        if (!data.success) alert('Failed to update status.');
      })
      .catch(err => console.error('Fetch error:', err));
    }
  });
});
