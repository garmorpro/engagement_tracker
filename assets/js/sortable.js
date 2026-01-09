const columns = document.querySelectorAll('.kanban-column .card-body');

columns.forEach(column => {
  new Sortable(column, {
    group: 'kanban',               // allows moving between columns
    animation: 150,
    ghostClass: 'kanban-ghost',
    handle: undefined,             // drag anywhere on the card
    draggable: '.engagement-card-wrapper', // only move wrapper

    onEnd: function(evt) {
      const wrapper = evt.item;
      const engId = wrapper.dataset.engId;
      const newStatus = evt.to.closest('.kanban-column')?.dataset?.status;

      console.log('Dragged wrapper:', wrapper);
      console.log('Engagement ID:', engId);
      console.log('New Status:', newStatus);

      if (!engId || !newStatus) return;

      fetch('includes/update-engagement-status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({eng_id: engId, new_status: newStatus})
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
