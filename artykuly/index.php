<?php
require __DIR__ . '/../lib/kb.php';

$SECTION_ROOT = realpath(CONTENT_ROOT . '/artykuly');

$q       = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$docRel  = isset($_GET['doc']) ? (string)$_GET['doc'] : '';
$docPath = $docRel !== '' ? kb_resolve($docRel, $SECTION_ROOT) : null;

$pageTitle = 'Artykuły — Hubert Kwiecień';
if ($docPath) $pageTitle = kb_title_from_file($docPath) . ' — Artykuły';
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
      <a href="../instrukcje/index.php">Instrukcje</a>
      <a href="index.php" class="current"><span class="pl">Artykuły</span><span class="en">Articles</span></a>
      <a href="../index.html#contact"><span class="pl">Kontakt</span><span class="en">Contact</span></a>
      <button class="btn-lang">EN</button>
    </div>
  </div>
</nav>

<div class="art-wrap">
  <?php if ($q !== ''): ?>
    <?php $results = kb_search($q, $SECTION_ROOT); ?>
    <p class="art-back"><a href="index.php"><span class="pl">← Wszystkie artykuły</span><span class="en">← All articles</span></a></p>
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
    <p class="art-back"><a href="index.php"><span class="pl">← Wszystkie artykuły</span><span class="en">← All articles</span></a></p>
    <article class="kb-doc art-single"><?= kb_render($docPath) ?></article>

  <?php else: ?>
    <header class="art-head">
      <h1 class="pl">Artykuły</h1><h1 class="en">Articles</h1>
      <p class="pl">Dłuższe notatki i przemyślenia — o kodzie, narzędziach i dobrych praktykach.</p>
      <p class="en">Longer notes and thoughts — about code, tools and good practices.</p>
      <form class="kb-search art-search" method="get" action="index.php" role="search">
        <input type="search" name="q" placeholder="Szukaj / Search…" aria-label="Szukaj">
      </form>
    </header>
    <?php $list = kb_list($SECTION_ROOT); ?>
    <?php if (!$list): ?>
      <p class="kb-empty"><span class="pl">Brak artykułów — dodaj pliki .md w <code>content/artykuly/</code>.</span><span class="en">No articles yet — add .md files in <code>content/artykuly/</code>.</span></p>
    <?php else: ?>
      <ul class="art-list">
        <?php foreach ($list as $a): ?>
          <li class="art-card">
            <a href="?doc=<?= urlencode($a['rel']) ?>"><h2><?= htmlspecialchars($a['title']) ?></h2></a>
            <?php if ($a['excerpt']): ?><p class="art-ex"><?= htmlspecialchars($a['excerpt']) ?></p><?php endif; ?>
            <a class="art-more" href="?doc=<?= urlencode($a['rel']) ?>"><span class="pl">Czytaj →</span><span class="en">Read →</span></a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php endif; ?>
</div>

<script src="../script.js"></script>
</body>
</html>
