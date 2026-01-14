document.addEventListener('DOMContentLoaded', () => {

  // ----- AUDIT TYPE MULTI-SELECT -----
  const auditCards = document.querySelectorAll('#addModal .audit-card');
  const auditInput = document.querySelector('#addModal .eng_audit_input');

  auditCards.forEach(card => {
    card.addEventListener('click', () => {
      card.classList.toggle('selected');
      card.style.background = card.classList.contains('selected') ? '#e0e9ff' : '#fff';
      card.style.borderColor = card.classList.contains('selected') ? '#c2d5ff' : '#e5e7eb';
      auditInput.value = [...auditCards].filter(c => c.classList.contains('selected')).map(c => c.dataset.audit).join(',');
    });
  });

  // ----- STATUS SINGLE-SELECT -----
  const statusCards = document.querySelectorAll('#addModal .status-card');
  const statusInput = document.querySelector('#addModal .eng_status_input');

  const defaultBorder = '229,231,235';
  const defaultBg = '255,255,255';
  const defaultText = '76,85,100';
  const statusColors = {
    'planning':  {border:'68,125,252', bg:'240,246,254', text:'35,70,221'},
    'fieldwork': {border:'241,115,19', bg:'254,247,238', text:'186,66,13'},
    'review':    {border:'160,77,253', bg:'249,245,254', text:'119,17,210'},
    'issued':    {border:'79,198,95', bg:'242,253,245', text:'51,128,63'},
    'archived':  {border:'107,114,129', bg:'249,250,251', text:'56,65,82'}
  };

  const applyColors = (card, status, isSelected) => {
    const colors = statusColors[status] || {border:defaultBorder,bg:defaultBg,text:defaultText};
    if(isSelected){
      card.style.border=`2px solid rgb(${colors.border})`;
      card.style.backgroundColor=`rgb(${colors.bg})`;
      card.style.color=`rgb(${colors.text})`;
    } else {
      card.style.border=`2px solid rgb(${defaultBorder})`;
      card.style.backgroundColor=`rgb(${defaultBg})`;
      card.style.color=`rgb(${defaultText})`;
    }
  };

  statusCards.forEach(card => {
    const status = card.dataset.status;
    applyColors(card, status, false);
    card.addEventListener('click', () => {
      statusInput.value = status;
      statusCards.forEach(c => applyColors(c, c.dataset.status, c.dataset.status === status));
    });
  });

});