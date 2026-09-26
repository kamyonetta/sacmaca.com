(() => {
  'use strict';
  const button = document.querySelector('.salon-lights');
  if (!button) return;
  button.hidden = false;
  let dimmed = false;
  try { dimmed = localStorage.getItem('sacmaca-lights') === 'dim'; } catch (_) { /* Storage is optional. */ }
  function render() {
    document.body.classList.toggle('salon-dim', dimmed);
    button.setAttribute('aria-pressed', String(dimmed));
    button.replaceChildren(document.createTextNode(dimmed ? 'Bring back the glow ◑' : 'Dim the lights ◐'));
  }
  render();
  button.addEventListener('click', () => {
    dimmed = !dimmed;
    render();
    try { localStorage.setItem('sacmaca-lights', dimmed ? 'dim' : 'glow'); } catch (_) { /* Keep the control usable. */ }
  });
})();
