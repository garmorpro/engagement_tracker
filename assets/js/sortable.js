const columns = document.querySelectorAll('.kanban-column .card-body');

columns.forEach(column => {
  new Sortable(column, {
    group: 'kanban',
    animation: 150,
    ghostClass: 'kanban-ghost',

    // Drag anywhere on card
    handle: undefined,

    onEnd: function(evt) {
      const card = evt.item;
      // The card container has the actual engagement ID
      const container = card.closest('.engagement-card-kanban-container');
      const engId = container?.dataset?.engId;
      const newStatus = evt.to.closest('.kanban-column')?.dataset?.status;

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
