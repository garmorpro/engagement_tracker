const columns = document.querySelectorAll('.kanban-column .card-body');

columns.forEach(column => {
  new Sortable(column, {
    group: 'kanban',           // allows dragging between columns
    animation: 150,
    ghostClass: 'kanban-ghost',

    // ONLY allow dragging by the grip icon (or any header)
    handle: '.drag-handle',

    // prevent inner cards from becoming draggable
    filter: '.no-drag',
    preventOnFilter: false,

    onEnd: function(evt) {
      const card = evt.item;
      const newStatus = evt.to.closest('.kanban-column')?.dataset?.status;
      const engId = card.dataset.engId;

      console.log('Dragged card:', card);
      console.log('Engagement ID:', engId);
      console.log('New Status:', newStatus);

      if (!engId || !newStatus) return;

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
