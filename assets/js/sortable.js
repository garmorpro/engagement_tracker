  const columns = document.querySelectorAll('.kanban-column .card-body');

  columns.forEach(column => {
    new Sortable(column, {
      group: 'kanban',  // allows dragging between columns
      animation: 150,
      ghostClass: 'kanban-ghost',
      onEnd: function (evt) {
        const card = evt.item; // dragged element
        const newStatus = evt.to.closest('.kanban-column').dataset.status;
        const engId = card.dataset.engId;

        // Send update to server via AJAX
        fetch('update-engagement-status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ eng_id: engId, new_status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
          if(data.success){
            console.log(`Engagement ${engId} moved to ${newStatus}`);
          } else {
            alert('Failed to update status.');
          }
        });
      }
    });
  });

