<?php
require_once __DIR__ . '/../lib/nodes.php';
require_once __DIR__ . '/../lib/auth.php';

$logged = kb_is_logged_in();
$q      = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$nodeId = isset($_GET['node']) ? (int)$_GET['node'] : 0;
$node   = $nodeId ? node_get($nodeId) : null;
$tree   = node_tree();

$pageTitle = 'Instrukcje — Hubert Kwiecień';
if ($node) $pageTitle = $node['title'] . ' — Instrukcje';

const FOLDER_IC = '<svg class="ic" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>';
const FILE_IC   = '<svg class="ic" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6zm0 7V3.5L18.5 9H14z"/></svg>';

function render_tree($nodes, $activeId) {
    $out = '';
    foreach ($nodes as $n) {
        $active  = ((int)$n['id'] === (int)$activeId) ? ' active' : '';
        $hasKids = !empty($n['children']);
        $label   = ($hasKids ? FOLDER_IC : FILE_IC) . '<span>' . htmlspecialchars($n['title']) . '</span>';
        $link    = '<a class="kb-node' . $active . '" href="?node=' . (int)$n['id'] . '">' . $label . '</a>';
        if ($hasKids) {
            $out .= '<details open class="kb-branch"><summary>' . $link . '</summary>'
                  . '<div class="kb-children">' . render_tree($n['children'], $activeId) . '</div></details>';
        } else {
            $out .= $link;
        }
    }
    return $out;
}
?><!DOCTYPE html>
<html lang="pl" data-lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?></title>
<link rel="stylesheet" href="../style.css?v=<?= @filemtime(__DIR__ . '/../style.css') ?>">
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
      <?php if ($logged): ?><a href="logout.php">Wyloguj</a><?php endif; ?>
      <a href="../index.html#contact"><span class="pl">Kontakt</span><span class="en">Contact</span></a>
    </div>
  </div>
</nav>

<div class="kb-wrap">
  <aside class="kb-side">
    <div class="kb-side-head">
      <span>Instrukcje</span>
      <a class="kb-add" href="edytor.php">+&nbsp;Dodaj</a>
    </div>
    <form class="kb-search" method="get" action="index.php" role="search">
      <input type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Szukaj / Search…" aria-label="Szukaj">
    </form>
    <nav class="kb-tree" aria-label="Drzewo instrukcji">
      <?php if (!$tree): ?>
        <p class="kb-empty">Brak instrukcji. Kliknij „+ Dodaj".</p>
      <?php else: ?>
        <?= render_tree($tree, $nodeId) ?>
      <?php endif; ?>
    </nav>
  </aside>

  <main class="kb-content">
    <?php if ($q !== ''): ?>
      <?php $results = node_search($q); ?>
      <h1>Wyniki: „<?= htmlspecialchars($q) ?>"</h1>
      <?php if (!$results): ?><p>Nic nie znaleziono.</p><?php else: ?>
        <ul class="kb-results">
          <?php foreach ($results as $r): ?>
            <li><a href="?node=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a>
              <?php if ($r['snippet']): ?><p class="snip">…<?= htmlspecialchars($r['snippet']) ?>…</p><?php endif; ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>

    <?php elseif ($node): ?>
      <?php $anc = node_ancestors($node['id']); array_pop($anc); ?>
      <?php if ($anc): ?>
        <nav class="kb-crumb"><?php foreach ($anc as $a): ?><a href="?node=<?= $a['id'] ?>"><?= htmlspecialchars($a['title']) ?></a> / <?php endforeach; ?></nav>
      <?php endif; ?>
      <article class="kb-doc">
        <h1><?= htmlspecialchars($node['title']) ?></h1>
        <?php if (!empty($node['content'])): ?>
          <?= kb_render_text($node['content']) ?>
        <?php else: ?>
          <p class="kb-muted">Ta kategoria nie ma jeszcze opisu.</p>
        <?php endif; ?>
      </article>

      <?php
        $children = kb_db()->prepare("SELECT id,title,(content IS NOT NULL AND content<>'') AS hc FROM nodes WHERE parent_id=? ORDER BY position,title");
        $children->execute([$node['id']]); $children = $children->fetchAll();
      ?>
      <?php if ($children): ?>
        <div class="kb-childbox">
          <h2>W tej kategorii</h2>
          <ul class="kb-childlist">
            <?php foreach ($children as $c): ?>
              <li><a href="?node=<?= $c['id'] ?>"><?= ($c['hc'] ? FILE_IC : FOLDER_IC) ?><span><?= htmlspecialchars($c['title']) ?></span></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <?php if ($logged): ?>
        <div class="kb-actions">
          <a class="btn btn-primary" href="edytor.php?edit=<?= $node['id'] ?>">Edytuj</a>
          <a class="btn btn-ghost-d" href="edytor.php?parent=<?= $node['id'] ?>">+ Podrzędny</a>
          <form method="post" action="usun.php" onsubmit="return confirm('Usunąć „<?= htmlspecialchars(addslashes($node['title'])) ?>" i wszystkie podrzędne?');" style="display:inline">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(kb_csrf()) ?>">
            <input type="hidden" name="id" value="<?= $node['id'] ?>">
            <button type="submit" class="btn btn-danger">Usuń</button>
          </form>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <article class="kb-doc">
        <h1>Instrukcje</h1>
        <p>Drzewo instrukcji — każdy węzeł to instrukcja z własną treścią, a kategorie mogą mieć opisy. Wybierz temat z drzewa po lewej albo skorzystaj z wyszukiwarki.</p>
        <?php if ($logged): ?><p><a class="btn btn-primary" href="edytor.php">+ Dodaj instrukcję</a></p><?php endif; ?>
      </article>
    <?php endif; ?>
  </main>
</div>

<script src="../script.js?v=<?= @filemtime(__DIR__ . '/../script.js') ?>"></script>
</body>
</html>
