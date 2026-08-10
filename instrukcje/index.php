<?php
require __DIR__ . '/lib/kb.php';

$q       = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$docRel  = isset($_GET['doc']) ? (string)$_GET['doc'] : '';
$docPath = $docRel !== '' ? kb_resolve($docRel) : null;
$tree    = kb_tree();

$pageTitle = 'Baza wiedzy — Hubert Kwiecień';
if ($docPath) $pageTitle = kb_title_from_file($docPath) . ' — Hubert Kwiecień';

/** Render drzewa jako zagnieżdżone <details>/<ul> (działa bez JS) */
function kb_render_tree($node, $activeRel) {
    $html = '';
    foreach ($node['dirs'] as $dir) {
        $html .= '<details open class="kb-branch"><summary>' . htmlspecialchars($dir['label']) . '</summary>';
        $html .= kb_render_tree($dir['children'], $activeRel);
        $html .= '</details>';
    }
    if ($node['files']) {
        $html .= '<ul class="kb-files">';
        foreach ($node['files'] as $f) {
            $active = ($f['rel'] === $activeRel) ? ' class="active"' : '';
            $html .= '<li><a' . $active . ' href="?doc=' . urlencode($f['rel']) . '">'
                   . htmlspecialchars($f['title']) . '</a></li>';
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
      <a href="index.php"><span class="pl">Baza wiedzy</span><span class="en">Knowledge base</span></a>
      <a href="../index.html#contact"><span class="pl">Kontakt</span><span class="en">Contact</span></a>
      <button class="btn-lang">EN</button>
    </div>
  </div>
</nav>

<div class="kb-wrap">
  <!-- SIDEBAR: drzewo z folderów -->
  <aside class="kb-side">
    <form class="kb-search" method="get" action="index.php" role="search">
      <input type="search" name="q" value="<?= htmlspecialchars($q) ?>"
             placeholder="Szukaj / Search…" aria-label="Szukaj">
    </form>
    <nav class="kb-tree" aria-label="Instrukcje">
      <?php if (!$tree['dirs'] && !$tree['files']): ?>
        <p class="kb-empty"><span class="pl">Brak treści — dodaj pliki .md w folderze <code>content/</code>.</span><span class="en">No content yet — add .md files in the <code>content/</code> folder.</span></p>
      <?php else: ?>
        <?= kb_render_tree($tree, $docPath ? kb_relpath($docPath) : '') ?>
      <?php endif; ?>
    </nav>
  </aside>

  <!-- CONTENT -->
  <main class="kb-content">
    <?php if ($q !== ''): ?>
      <?php $results = kb_search($q); ?>
      <h1 class="pl">Wyniki wyszukiwania: „<?= htmlspecialchars($q) ?>"</h1>
      <h1 class="en">Search results: “<?= htmlspecialchars($q) ?>”</h1>
      <?php if (!$results): ?>
        <p><span class="pl">Nic nie znaleziono.</span><span class="en">Nothing found.</span></p>
      <?php else: ?>
        <ul class="kb-results">
          <?php foreach ($results as $r): ?>
            <li>
              <a href="?doc=<?= urlencode($r['rel']) ?>"><?= htmlspecialchars($r['title']) ?></a>
              <?php if ($r['snippet']): ?><p class="snip">…<?= htmlspecialchars($r['snippet']) ?>…</p><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

    <?php elseif ($docPath): ?>
      <article class="kb-doc"><?= kb_render($docPath) ?></article>

    <?php else: ?>
      <div class="kb-doc">
        <h1 class="pl">Baza wiedzy</h1>
        <h1 class="en">Knowledge base</h1>
        <p class="pl">Moje instrukcje i notatki „jak-to-zrobić". Wybierz temat z drzewa po lewej albo skorzystaj z wyszukiwarki. Drzewo buduje się automatycznie z folderów — każdy folder to gałąź, każdy plik <code>.md</code> to wpis.</p>
        <p class="en">My how-to guides and notes. Pick a topic from the tree on the left or use the search box. The tree is generated automatically from folders — each folder is a branch, each <code>.md</code> file is an entry.</p>
      </div>
    <?php endif; ?>
  </main>
</div>

<script src="../script.js"></script>
</body>
</html>
