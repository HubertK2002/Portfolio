<?php
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/kb.php';
kb_require_login();
$csrf = kb_csrf();

$instr = realpath(CONTENT_ROOT . '/instrukcje');
$cats = [];
if ($instr) {
    foreach (scandir($instr) as $e) {
        if ($e[0] !== '.' && is_dir($instr . DIRECTORY_SEPARATOR . $e)) $cats[] = $e;
    }
    natcasesort($cats);
}
?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Nowa instrukcja — Edytor</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="nav"><div class="inner">
  <a href="../index.html" class="brand">Hubert<span>.</span>Kwiecień</a>
  <div class="links">
    <a href="index.php">← Instrukcje</a>
    <a href="logout.php">Wyloguj</a>
  </div>
</div></nav>

<form class="ed-toolbar" method="post" action="zapisz.php" id="ed-form">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
  <div class="ed-field">
    <label>Tytuł</label>
    <input type="text" name="title" id="ed-title" placeholder="np. Konfiguracja Nginx" required>
  </div>
  <div class="ed-field">
    <label>Kategoria</label>
    <select name="category" id="ed-cat">
      <?php foreach ($cats as $c): ?>
        <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars(kb_pretty($c)) ?></option>
      <?php endforeach; ?>
      <option value="__new__">— nowa kategoria —</option>
    </select>
  </div>
  <div class="ed-field" id="ed-newcat-wrap" style="display:none">
    <label>Nowa kategoria</label>
    <input type="text" name="new_category" id="ed-newcat" placeholder="np. docker">
  </div>
  <div class="ed-field">
    <label>Plik (.md)</label>
    <input type="text" name="filename" id="ed-file" placeholder="auto ze slug-a">
  </div>
  <button type="submit" class="btn btn-primary">Zapisz instrukcję</button>
</form>

<div class="ed-panes">
  <div class="ed-editor">
    <div class="ed-pane-head">Markdown</div>
    <textarea name="content" id="ed-content" form="ed-form" placeholder="# Tytuł&#10;&#10;Pisz treść w Markdown..."></textarea>
  </div>
  <div class="ed-preview">
    <div class="ed-pane-head">Podgląd na żywo</div>
    <article class="kb-doc" id="ed-prev"><p style="color:#888">Podgląd pojawi się tutaj…</p></article>
  </div>
</div>

<script>
const csrf = <?= json_encode($csrf) ?>;
const $ = s => document.querySelector(s);
const title = $('#ed-title'), cat = $('#ed-cat'), newWrap = $('#ed-newcat-wrap'),
      fileIn = $('#ed-file'), content = $('#ed-content'), prev = $('#ed-prev');

// pokaż pole nowej kategorii
cat.addEventListener('change', () => {
  newWrap.style.display = cat.value === '__new__' ? '' : 'none';
});

// slug z tytułu -> nazwa pliku (dopóki użytkownik nie edytował ręcznie)
let fileTouched = false;
fileIn.addEventListener('input', () => fileTouched = true);
function slug(s){
  const map={'ą':'a','ć':'c','ę':'e','ł':'l','ń':'n','ó':'o','ś':'s','ż':'z','ź':'z'};
  return s.toLowerCase().replace(/[ąćęłńóśżź]/g,m=>map[m]).replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'');
}
title.addEventListener('input', () => { if(!fileTouched) fileIn.value = slug(title.value); });

// podgląd na żywo (debounce -> render serwerowy Parsedownem)
let t=null;
function render(){
  const body = content.value;
  const md = (title.value && !/^\s*#\s+/.test(body)) ? ('# '+title.value+'\n\n'+body) : body;
  fetch('podglad.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'csrf='+encodeURIComponent(csrf)+'&md='+encodeURIComponent(md)})
    .then(r => r.ok ? r.text() : Promise.reject())
    .then(html => { prev.innerHTML = html || '<p style="color:#888">…</p>'; })
    .catch(()=>{ prev.innerHTML = '<p style="color:#c00">Błąd podglądu</p>'; });
}
content.addEventListener('input', () => { clearTimeout(t); t=setTimeout(render, 350); });
title.addEventListener('input', () => { clearTimeout(t); t=setTimeout(render, 350); });
</script>
</body>
</html>
