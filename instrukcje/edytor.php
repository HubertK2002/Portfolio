<?php
require_once __DIR__ . '/../lib/nodes.php';
require_once __DIR__ . '/../lib/auth.php';
kb_require_login();
$csrf = kb_csrf();

$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$node   = $editId ? node_get($editId) : null;
$parentPre = isset($_GET['parent']) ? (int)$_GET['parent'] : ($node['parent_id'] ?? 0);

$tree = node_tree();
$opts = node_options($tree, $editId ?: null);

$title   = $node['title']   ?? '';
$slug    = $node['slug']    ?? '';
$content = $node['content'] ?? '';
$heading = $editId ? 'Edytuj instrukcję' : 'Nowa instrukcja';
?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($heading) ?></title>
<link rel="stylesheet" href="../style.css?v=<?= @filemtime(__DIR__ . '/../style.css') ?>">
</head>
<body>
<nav class="nav"><div class="inner">
  <a href="../index.html" class="brand">Hubert<span>.</span>Kwiecień</a>
  <div class="links">
    <a href="index.php<?= $editId ? '?node=' . $editId : '' ?>">← Instrukcje</a>
    <a href="logout.php">Wyloguj</a>
  </div>
</div></nav>

<form class="ed-toolbar" method="post" action="zapisz.php" id="ed-form">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
  <?php if ($editId): ?><input type="hidden" name="id" value="<?= $editId ?>"><?php endif; ?>
  <div class="ed-field">
    <label>Tytuł</label>
    <input type="text" name="title" id="ed-title" value="<?= htmlspecialchars($title) ?>" placeholder="np. Konfiguracja Nginx" required>
  </div>
  <div class="ed-field">
    <label>Rodzic (kategoria)</label>
    <select name="parent_id" id="ed-parent">
      <option value="">— główny poziom —</option>
      <?php foreach ($opts as $o): ?>
        <option value="<?= $o['id'] ?>" <?= ((int)$parentPre === $o['id']) ? 'selected' : '' ?>><?= htmlspecialchars($o['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="ed-field">
    <label>Slug (URL)</label>
    <input type="text" name="slug" id="ed-file" value="<?= htmlspecialchars($slug) ?>" placeholder="auto z tytułu">
  </div>
  <button type="submit" class="btn btn-primary"><?= $editId ? 'Zapisz zmiany' : 'Utwórz' ?></button>
</form>
<p class="ed-hint">Wskazówka: węzeł bez treści działa jak czysta kategoria; węzeł z treścią to instrukcja. Kategoria z treścią = kategoria z opisem. W polu treści <b>Tab</b> wstawia tabulację, <b>Shift+Tab</b> ją usuwa (Esc, potem Tab — wyjście z pola).</p>

<div class="ed-panes">
  <div class="ed-editor">
    <div class="ed-pane-head">Treść (Markdown) — opcjonalna</div>
    <textarea name="content" id="ed-content" form="ed-form" placeholder="Pisz treść w Markdown (bez powtarzania tytułu)…"><?= htmlspecialchars($content) ?></textarea>
  </div>
  <div class="ed-preview">
    <div class="ed-pane-head">Podgląd na żywo</div>
    <article class="kb-doc" id="ed-prev"></article>
  </div>
</div>

<script>
const csrf = <?= json_encode($csrf) ?>;
const $ = s => document.querySelector(s);
const title=$('#ed-title'), fileIn=$('#ed-file'), content=$('#ed-content'), prev=$('#ed-prev');
let fileTouched = <?= $editId ? 'true' : 'false' ?>;
fileIn.addEventListener('input', () => fileTouched = true);
function slug(s){const m={'ą':'a','ć':'c','ę':'e','ł':'l','ń':'n','ó':'o','ś':'s','ż':'z','ź':'z'};
  return s.toLowerCase().replace(/[ąćęłńóśżź]/g,c=>m[c]).replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');}
title.addEventListener('input', () => { if(!fileTouched) fileIn.value = slug(title.value); });
// ——— Tab w polu Markdown: wcięcie zamiast przeskoku do następnego pola ———
let tabEscaped = false;   // Esc → następny Tab znów wychodzi z pola (dostępność)

function insertText(text){
  // execCommand zachowuje historię cofania (Ctrl+Z) i sam odpala zdarzenie input
  if (!document.execCommand || !document.execCommand('insertText', false, text)) {
    const s = content.selectionStart, e = content.selectionEnd, v = content.value;
    content.value = v.slice(0, s) + text + v.slice(e);
    content.selectionStart = content.selectionEnd = s + text.length;
    content.dispatchEvent(new Event('input', {bubbles:true}));
  }
}

content.addEventListener('keydown', e => {
  if (e.key === 'Escape') { tabEscaped = true; return; }
  if (e.key !== 'Tab' || e.ctrlKey || e.altKey || e.metaKey) return;
  if (tabEscaped) { tabEscaped = false; return; }   // pozwól wyjść z pola
  e.preventDefault();

  const start = content.selectionStart, end = content.selectionEnd, val = content.value;
  const multiline = val.slice(start, end).includes('\n');

  if (!multiline && !e.shiftKey) {          // zwykły Tab → wstaw tabulację
    insertText('\t');
    return;
  }

  // wcięcie / usunięcie wcięcia całych linii
  const lineStart = val.lastIndexOf('\n', start - 1) + 1;
  let lineEnd = val.indexOf('\n', end);
  if (lineEnd === -1) lineEnd = val.length;

  const lines = val.slice(lineStart, lineEnd).split('\n');
  const out = e.shiftKey
    ? lines.map(l => l.replace(/^(\t| {1,4})/, ''))
    : lines.map(l => '\t' + l);
  const block = out.join('\n');

  content.setSelectionRange(lineStart, lineEnd);
  insertText(block);
  content.setSelectionRange(lineStart, lineStart + block.length);
});

let t=null;
function render(){
  const body = content.value;
  const md = (title.value && !/^\s*#\s+/.test(body)) ? ('# '+title.value+'\n\n'+body) : body;
  fetch('podglad.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf='+encodeURIComponent(csrf)+'&md='+encodeURIComponent(md)})
    .then(r=>r.ok?r.text():Promise.reject()).then(h=>prev.innerHTML=h||'').catch(()=>prev.innerHTML='<p style="color:#c00">Błąd podglądu</p>');
}
content.addEventListener('input', ()=>{clearTimeout(t);t=setTimeout(render,300);});
title.addEventListener('input', ()=>{clearTimeout(t);t=setTimeout(render,300);});
render();
</script>
</body>
</html>
