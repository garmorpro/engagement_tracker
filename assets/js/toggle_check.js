function toggleYN(el) {
  const hidden = el.nextElementSibling;
  el.classList.toggle('active');

  if (el.classList.contains('active')) {
    el.innerHTML = '<i class="bi bi-check"></i> Y';
    hidden.value = 'Y';
  } else {
    el.innerHTML = 'N';
    hidden.value = 'N';
  }
}