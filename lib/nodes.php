<?php
// ============================================================
//  nodes.php — drzewo instrukcji oparte o bazę (tabela nodes)
//  Każdy węzeł = instrukcja z treścią; może mieć dzieci i własny opis.
// ============================================================
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/kb.php'; // kb_slug, kb_render_text

/** Wszystkie węzły (płasko), z flagą has_content */
function node_all() {
    return kb_db()->query(
        "SELECT id, parent_id, slug, title, position,
                (content IS NOT NULL AND content <> '') AS has_content
         FROM nodes ORDER BY position, title"
    )->fetchAll();
}

/** Zbuduj zagnieżdżone drzewo z płaskiej listy */
function node_tree() {
    $rows = node_all();
    $byParent = [];
    foreach ($rows as $r) {
        $byParent[$r['parent_id'] === null ? 0 : (int)$r['parent_id']][] = $r;
    }
    $build = function ($pid) use (&$build, $byParent) {
        $out = [];
        foreach ($byParent[$pid] ?? [] as $r) {
            $r['children'] = $build((int)$r['id']);
            $out[] = $r;
        }
        return $out;
    };
    return $build(0);
}

function node_get($id) {
    $st = kb_db()->prepare("SELECT * FROM nodes WHERE id = ?");
    $st->execute([(int)$id]);
    return $st->fetch() ?: null;
}

/** Ścieżka przodków root->węzeł (breadcrumb) */
function node_ancestors($id) {
    $chain = [];
    $cur = node_get($id);
    while ($cur) {
        array_unshift($chain, $cur);
        $cur = $cur['parent_id'] ? node_get($cur['parent_id']) : null;
    }
    return $chain;
}

/** Unikalny slug wśród rodzeństwa */
function node_unique_slug($parentId, $base, $exceptId = null) {
    $base = kb_slug($base) ?: 'wpis';
    $try = $base; $i = 1;
    while (true) {
        $sql = "SELECT id FROM nodes WHERE slug = :slug AND "
             . ($parentId === null ? "parent_id IS NULL" : "parent_id = :pid")
             . ($exceptId ? " AND id <> :eid" : "");
        $st = kb_db()->prepare($sql);
        $p = [':slug' => $try];
        if ($parentId !== null) $p[':pid'] = (int)$parentId;
        if ($exceptId) $p[':eid'] = (int)$exceptId;
        $st->execute($p);
        if (!$st->fetch()) return $try;
        $i++; $try = $base . '-' . $i;
    }
}

function node_create($parentId, $title, $slug, $content) {
    $parentId = ($parentId === '' || $parentId === null) ? null : (int)$parentId;
    $slug = node_unique_slug($parentId, $slug !== '' ? $slug : $title);
    $st = kb_db()->prepare("SELECT COALESCE(MAX(position),0)+1 FROM nodes WHERE "
        . ($parentId === null ? "parent_id IS NULL" : "parent_id = ?"));
    $st->execute($parentId === null ? [] : [$parentId]);
    $position = (int)$st->fetchColumn();
    $ins = kb_db()->prepare("INSERT INTO nodes (parent_id, slug, title, content, position) VALUES (?,?,?,?,?)");
    $ins->execute([$parentId, $slug, $title, ($content !== '' ? $content : null), $position]);
    return (int)kb_db()->lastInsertId();
}

function node_update($id, $parentId, $title, $slug, $content) {
    $parentId = ($parentId === '' || $parentId === null) ? null : (int)$parentId;
    $slug = node_unique_slug($parentId, $slug !== '' ? $slug : $title, (int)$id);
    $st = kb_db()->prepare("UPDATE nodes SET parent_id=?, slug=?, title=?, content=? WHERE id=?");
    $st->execute([$parentId, $slug, $title, ($content !== '' ? $content : null), (int)$id]);
}

function node_delete($id) {
    $st = kb_db()->prepare("DELETE FROM nodes WHERE id = ?"); // ON DELETE CASCADE zdejmie dzieci
    $st->execute([(int)$id]);
}

/** Wyszukiwanie po tytule i treści */
function node_search($q) {
    $q = trim((string)$q);
    if ($q === '') return [];
    $st = kb_db()->prepare(
        "SELECT id, title, content FROM nodes WHERE title LIKE :q OR content LIKE :q ORDER BY title LIMIT 50"
    );
    $st->execute([':q' => '%' . $q . '%']);
    $out = [];
    foreach ($st as $r) {
        $snip = '';
        if ($r['content'] && ($pos = stripos($r['content'], $q)) !== false) {
            $snip = trim(preg_replace('/\s+/', ' ', substr($r['content'], max(0, $pos - 45), 150)));
        }
        $out[] = ['id' => (int)$r['id'], 'title' => $r['title'], 'snippet' => $snip];
    }
    return $out;
}

/** Opcje do <select> rodzica (hierarchiczne). $excludeId pomija węzeł i jego poddrzewo. */
function node_options($tree, $excludeId = null, $depth = 0, &$out = null) {
    if ($out === null) $out = [];
    foreach ($tree as $n) {
        if ($excludeId && (int)$n['id'] === (int)$excludeId) continue; // pomiń siebie i poddrzewo
        $out[] = ['id' => (int)$n['id'], 'label' => str_repeat('— ', $depth) . $n['title']];
        if (!empty($n['children'])) node_options($n['children'], $excludeId, $depth + 1, $out);
    }
    return $out;
}
