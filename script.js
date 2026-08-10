// ---- language toggle (PL / EN) ----
(function () {
  const root = document.documentElement;
  const saved = localStorage.getItem('lang');
  const lang = saved === 'en' || saved === 'pl' ? saved : 'pl';
  root.setAttribute('data-lang', lang);

  function updateBtn() {
    const cur = root.getAttribute('data-lang');
    document.querySelectorAll('.btn-lang').forEach(b => {
      b.textContent = cur === 'pl' ? 'EN' : 'PL';
      b.setAttribute('aria-label', cur === 'pl' ? 'Switch to English' : 'Przełącz na polski');
    });
  }
  function toggle() {
    const next = root.getAttribute('data-lang') === 'pl' ? 'en' : 'pl';
    root.setAttribute('data-lang', next);
    localStorage.setItem('lang', next);
    updateBtn();
  }
  document.addEventListener('click', e => {
    if (e.target.closest('.btn-lang')) toggle();
  });
  updateBtn();

  // ---- mobile nav ----
  const toggleBtn = document.querySelector('.nav-toggle');
  const links = document.querySelector('.nav .links');
  if (toggleBtn && links) {
    toggleBtn.addEventListener('click', () => links.classList.toggle('open'));
    links.addEventListener('click', e => { if (e.target.tagName === 'A') links.classList.remove('open'); });
  }
})();
