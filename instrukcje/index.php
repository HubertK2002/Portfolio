<?php
require __DIR__ . '/../lib/kb.php';

$SECTION_ROOT = realpath(CONTENT_ROOT . '/instrukcje');

$q       = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$docRel  = isset($_GET['doc']) ? (string)$_GET['doc'] : '';
$docPath = $docRel !== '' ? kb_resolve($docRel, $SECTION_ROOT) : null;
$tree    = kb_tree($SECTION_ROOT);

$pageTitle = 'Instrukcje — Hubert Kwiecień';
if ($docPath) $pageTitle = kb_title_from_file($docPath) . ' — Instrukcje';

function kb_render_tree($node, $activeRel) {
    $folderIc = '<svg class="ic" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>';
    $fileIc   = '<svg class="ic" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm0 7V3.5L18.5 9H14z"/></svg>';
    $html = '';
    foreach ($node['dirs'] as $dir) {
        $html .= '<details open class="kb-branch"><summary>' . $folderIc . '<span class="kb-folder">' . htmlspecialchars($dir['label']) . '</span></summary>';
        $html .= kb_render_tree($dir['children'], $activeRel);
        $html .= '</details>';
    }
    if ($node['files']) {
        $html .= '<ul class="kb-files">';
        foreach ($node['files'] as $f) {
            $active = ($f['rel'] === $activeRel) ? ' class="active"' : '';
            $html .= '<li><a' . $active . ' href="?doc=' . urlencode($f['rel']) . '">'
                   . $fileIc . '<span>' . htmlspecialchars($f['title']) . '</span></a></li>';
        }
        $html .= '</ul>';
    }
    return $html;
}
?><!DOCTYPE html>
<html lang="pl" data-lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="stylesheet" href="../style.css">
</head>
<body>

<nav class="nav">
  <div class="inner">
    <a href="../index.html" class="brand">Hubert<span>.</span>Kwiecień</a>
    <button class="nav-toggle" aria-label="Menu">☰</button>
    <div class="links">
      <a href="../index.html#projects"><span class="pl">Projekty</span><span class="en">Projects</span></a>
      <a href="index.php" class="current">Instrukcje</a>
      <a href="../artykuly/index.php"><span class="pl">Artykuły</span><span class="en">Articles</span></a>
      <a href="../index.html#contact"><span class="pl">Kontakt</span><span class="en">Contact</span></a>
      <button class="btn-lang">EN</button>
    </div>
  </div>
</nav>

<div class="kb-wrap">
  <aside class="kb-side">
    <div class="kb-side-head">
      <span><span class="pl">Instrukcje</span><span class="en">Guides</span></span>
      <a class="kb-add" href="edytor.php" title="Dodaj instrukcję">+&nbsp;<span class="pl">Dodaj</span><span class="en">Add</span></a>
    </div>
    <form class="kb-search" method="get" action="index.php" role="search">
      <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Szukaj / Search…" aria-label="Szukaj">
    </form>
    <nav class="kb-tree" aria-label="Instrukcje">
      <?php if (!$tree['dirs'] && !$tree['files']): ?>
        <p class="kb-empty"><span class="pl">Brak instrukcji — dodaj pliki .md w <code>content/instrukcje/</code>.</span><span class="en">No guides yet — add .md files in <code>content/instrukcje/</code>.</span></p>
      <?php else: ?>
        <?= kb_render_tree($tree, $docPath ? kb_relpath($docPath) : '') ?>
      <?php endif; ?>
    </nav>
  </aside>

  <main class="kb-content">
    <?php if ($q !== ''): ?>
      <?php $results = kb_search($q, $SECTION_ROOT); ?>
      <h1 class="pl">Wyniki: „<?= htmlspecialchars($q) ?>"</h1>
      <h1 class="en">Results: “<?= htmlspecialchars($q) ?>”</h1>
      <?php if (!$results): ?>
        <p><span class="pl">Nic nie znaleziono.</span><span class="en">Nothing found.</span></p>
      <?php else: ?>
        <ul class="kb-results">
          <?php foreach ($results as $r): ?>
            <li><a href="?doc=<?= urlencode($r['rel']) ?>"><?= htmlspecialchars($r['title']) ?></a>
              <?php if ($r['snippet']): ?><p class="snip">…<?= htmlspecialchars($r['snippet']) ?>…</p><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    <?php elseif ($docPath): ?>
      <article class="kb-doc"><?= kb_render($docPath) ?></article>
    <?php else: ?>
      <div class="kb-doc">
        <h1 class="pl">Instrukcje</h1><h1 class="en">Guides</h1>
        <p class="pl">Moje notatki „jak-to-zrobić", pogrupowane w kategorie. Wybierz temat z drzewa po lewej albo skorzystaj z wyszukiwarki. Każdy folder to kategoria, każdy plik <code>.md</code> to instrukcja.</p>
        <p class="en">My how-to notes, grouped into categories. Pick a topic from the tree on the left or use search. Each folder is a category, each <code>.md</code> file is a guide.</p>
      </div>
    <?php endif; ?>
  </main>
</div>

<script src="../script.js"></script>
</body>
</html>
