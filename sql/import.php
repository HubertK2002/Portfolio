<?php
// Jednorazowy import plików content/instrukcje/*.md do tabeli nodes.
// Foldery -> węzły-kategorie, pliki .md -> węzły-instrukcje (treść bez nagłówka H1).
require __DIR__ . '/../lib/nodes.php';

$root = realpath(__DIR__ . '/../content/instrukcje');
if (!$root) { fwrite(STDERR, "Brak content/instrukcje\n"); exit(1); }

// Nie duplikuj, jeśli już coś jest
$count = (int)kb_db()->query("SELECT COUNT(*) FROM nodes")->fetchColumn();
if ($count > 0) { echo "Tabela nodes nie jest pusta ($count) — pomijam import.\n"; exit(0); }

function strip_h1($md) {
    // usuń pierwszą linię "# ..." (tytuł trzymamy osobno)
    return preg_replace('/^\s*#\s+.+\R+/u', '', $md, 1);
}

function import_dir($dir, $parentId) {
    $entries = scandir($dir);
    natcasesort($entries);
    foreach ($entries as $e) {
        if ($e[0] === '.') continue;
        $p = $dir . DIRECTORY_SEPARATOR . $e;
        if (is_dir($p)) {
            $id = node_create($parentId, kb_pretty($e), $e, ''); // kategoria (na razie bez opisu)
            echo "kategoria: $e (#$id)\n";
            import_dir($p, $id);
        } elseif (preg_match('/\.md$/i', $e)) {
            $content = strip_h1(file_get_contents($p));
            $title = kb_title_from_file($p);
            $slug = preg_replace('/\.md$/i', '', $e);
            $id = node_create($parentId, $title, $slug, $content);
            echo "  instrukcja: $title (#$id)\n";
        }
    }
}

import_dir($root, null);
echo "Gotowe. Węzłów: " . kb_db()->query("SELECT COUNT(*) FROM nodes")->fetchColumn() . "\n";
