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

// ---- filtrowanie drzewa instrukcji w locie ----
(function () {
  const tree  = document.querySelector('.kb-tree');
  const input = document.querySelector('.kb-search input[name="q"]');
  if (!tree || !input) return;

  // porównujemy bez wielkości liter i bez ogonków: "swiat" znajdzie "Świat"
  const norm = s => s.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');

  // Indeks budowany RAZ przy starcie. Dzięki temu przy każdym znaku nie czytamy
  // DOM-u (textContent / querySelector) — lecimy tylko po gotowej strukturze.
  function index(container) {
    const items = [];
    for (const el of container.children) {
      if (el.matches('details.kb-branch')) {
        const link = el.querySelector(':scope > summary > .kb-node');
        const box  = el.querySelector(':scope > .kb-children');
        const item = { el, branch: true, open: el.open, title: norm(link ? link.textContent : ''), children: [] };
        item.children = box ? index(box) : [];
        items.push(item);
      } else if (el.matches('a.kb-node')) {
        items.push({ el, branch: false, title: norm(el.textContent), children: [] });
      }
    }
    return items;
  }
  const model = index(tree);
  if (!model.length) return;

  const noRes = document.createElement('p');
  noRes.className = 'kb-empty kb-nores';
  noRes.hidden = true;
  noRes.textContent = 'Brak pasujących instrukcji.';
  tree.after(noRes);

  // force = rodzic pasuje, więc całe poddrzewo pokazujemy bez dalszego sprawdzania
  function apply(items, q, force) {
    let any = false;
    for (const it of items) {
      const self = force || it.title.includes(q);
      const kids = it.children.length ? apply(it.children, q, self) : false;
      const show = self || kids;
      it.el.style.display = show ? '' : 'none';
      if (it.branch && show) it.el.open = true;     // rozwiń gałąź z trafieniem
      any = any || show;
    }
    return any;
  }

  function reset(items) {
    for (const it of items) {
      it.el.style.display = '';
      if (it.branch) it.el.open = it.open;          // przywróć pierwotne rozwinięcie
      if (it.children.length) reset(it.children);
    }
  }

  let last = null;
  function filter() {
    const q = norm(input.value.trim());
    if (q === last) return;        // strzałki, Ctrl itp. nic nie zmieniają — nie ruszamy DOM-u
    last = q;

    tree.style.display = 'none';   // 1. chowamy całe drzewo
    const any = q ? apply(model, q, false) : (reset(model), true);   // 2. zmiany na ukrytym
    tree.style.display = '';       // 3. pokazujemy — jeden układ zamiast wielu
    noRes.hidden = any;
  }

  input.addEventListener('keyup', filter);
  input.addEventListener('search', filter);  // „x" w polu type=search
  input.addEventListener('input', filter);   // wklejenie myszą / autouzupełnianie (guard chroni przed dublem)
  if (input.value.trim()) filter();          // filtruj od razu, gdy pole ma wartość
})();
