document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('#addModal .engagement-status-container').forEach(container => {
    const cards = container.querySelectorAll('.status-card');
    const hiddenInput = container.querySelector('.eng_status_input');
    const defaultBorder = '229,231,235';
    const defaultBg = '255,255,255';
    const defaultText = '76,85,100';
    const statusColors = {
      'on-hold': {border:'107,114,129',bg:'249,250,251',text:'56,65,82'},
      'planning': {border:'68,125,252',bg:'240,246,254',text:'35,70,221'},
      'in-progress': {border:'241,115,19',bg:'254,247,238',text:'186,66,13'},
      'in-review': {border:'160,77,253',bg:'249,245,254',text:'119,17,210'},
      'complete': {border:'79,198,95',bg:'242,253,245',text:'51,128,63'},
    };
    const applyColors = (card,status,isSelected)=>{
      if(isSelected){
        const colors=statusColors[status];
        card.style.border=`2px solid rgb(${colors.border})`;
        card.style.backgroundColor=`rgb(${colors.bg})`;
        card.style.color=`rgb(${colors.text})`;
      } else {
        card.style.border=`2px solid rgb(${defaultBorder})`;
        card.style.backgroundColor=`rgb(${defaultBg})`;
        card.style.color=`rgb(${defaultText})`;
      }
    };
    cards.forEach(card=>{
      const status=card.dataset.status;
      applyColors(card,status,false);
      card.addEventListener('click',()=>{
        hiddenInput.value=status;
        cards.forEach(c=>applyColors(c,c.dataset.status,c.dataset.status===status));
      });
    });
  });
});