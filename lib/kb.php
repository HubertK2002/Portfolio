<?php
// ============================================================
//  kb.php — silnik bazy wiedzy (wspólny dla Instrukcji i Artykułów)
//  content/instrukcje/  -> drzewo kategorii (foldery) + wpisy .md
//  content/artykuly/    -> lista wpisów .md
// ============================================================

define('CONTENT_ROOT', realpath(__DIR__ . '/../content'));

/** Ładna etykieta z nazwy pliku/folderu: "nginx-php-fpm" -> "Nginx php fpm" */
function kb_pretty($name) {
    $n = preg_replace('/\.md$/i', '', $name);
    $n = preg_replace('/^\d{4}-\d{2}-\d{2}-/', '', $n); // zdejmij prefiks daty
    $n = str_replace(['-', '_'], ' ', $n);
    return ucfirst(trim($n));
}

/** Tytuł wpisu: pierwszy nagłówek "# ..." z pliku, inaczej ładna nazwa pliku */
function kb_title_from_file($file) {
    $fh = @fopen($file, 'r');
    if ($fh) {
        while (($line = fgets($fh)) !== false) {
            $t = trim($line);
            if ($t === '') continue;
            if (preg_match('/^#\s+(.+)$/', $t, $m)) { fclose($fh); return trim($m[1]); }
            break;
        }
        fclose($fh);
    }
    return kb_pretty(basename($file));
}

/** Krótki fragment (pierwszy akapit bez nagłówka) — dla listy artykułów */
function kb_excerpt($file, $len = 160) {
    $lines = @file($file);
    if (!$lines) return '';
    $buf = '';
    foreach ($lines as $line) {
        $t = trim($line);
        if ($t === '' || $t[0] === '#' || $t[0] === '`') continue;
        $buf = $t;
        break;
    }
    $buf = preg_replace('/[*_`>#\[\]]/', '', $buf);
    if (mb_strlen($buf) > $len) $buf = mb_substr($buf, 0, $len) . '…';
    return $buf;
}

/** Ścieżka względna wpisu od CONTENT_ROOT, zawsze ze slashami "/" */
function kb_relpath($abs) {
    $rel = substr(realpath($abs), strlen(CONTENT_ROOT) + 1);
    return str_replace(DIRECTORY_SEPARATOR, '/', $rel);
}

/** Rekurencyjne drzewo folderów i plików .md (dla Instrukcji) */
function kb_tree($dir) {
    $out = ['dirs' => [], 'files' => []];
    if (!$dir || !is_dir($dir)) return $out;
    $entries = scandir($dir);
    natcasesort($entries);
    foreach ($entries as $e) {
        if ($e === '.' || $e === '..' || $e[0] === '.') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $e;
        if (is_dir($path)) {
            $out['dirs'][] = ['name' => $e, 'label' => kb_pretty($e), 'children' => kb_tree($path)];
        } elseif (preg_match('/\.md$/i', $e)) {
            $out['files'][] = ['rel' => kb_relpath($path), 'title' => kb_title_from_file($path)];
        }
    }
    return $out;
}

/** Płaska lista wpisów .md pod $dir (rekurencyjnie), najnowsze pierwsze — dla Artykułów */
function kb_list($dir) {
    $items = [];
    if (!$dir || !is_dir($dir)) return $items;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!preg_match('/\.md$/i', $file->getFilename())) continue;
        $items[] = [
            'rel'     => kb_relpath($file->getPathname()),
            'title'   => kb_title_from_file($file->getPathname()),
            'excerpt' => kb_excerpt($file->getPathname()),
            'mtime'   => $file->getMTime(),
        ];
    }
    usort($items, function ($a, $b) { return $b['mtime'] - $a['mtime']; });
    return $items;
}

/** Bezpieczne rozwiązanie ścieżki wpisu — ochrona przed path traversal.
 *  $confineTo (opcjonalnie) ogranicza do podfolderu (np. tylko artykuly/). */
function kb_resolve($rel, $confineTo = null) {
    if (!is_string($rel) || $rel === '') return null;
    if (strpos($rel, "\0") !== false) return null;
    $rel = str_replace('\\', '/', $rel);
    $target = realpath(CONTENT_ROOT . '/' . $rel);
    if ($target === false) return null;
    $base = $confineTo ? realpath($confineTo) : CONTENT_ROOT;
    if (!$base) return null;
    if (strpos($target, $base . DIRECTORY_SEPARATOR) !== 0) return null;
    if (!preg_match('/\.md$/i', $target)) return null;
    return $target;
}

/** Render łańcucha Markdown do bezpiecznego HTML (Parsedown, safe mode) */
function kb_render_text($md) {
    require_once __DIR__ . '/Parsedown.php';
    static $pd = null;
    if ($pd === null) { $pd = new Parsedown(); $pd->setSafeMode(true); }
    return $pd->text((string)$md);
}

/** Render pliku .md do bezpiecznego HTML */
function kb_render($absfile) {
    return kb_render_text(file_get_contents($absfile));
}

/** Slug z tytułu/nazwy (z transliteracją polskich znaków) */
function kb_slug($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $map = ['ą'=>'a','ć'=>'c','ę'=>'e','ł'=>'l','ń'=>'n','ó'=>'o','ś'=>'s','ż'=>'z','ź'=>'z'];
    $s = strtr($s, $map);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/** Przeszukiwanie treści .md w obrębie $root */
function kb_search($q, $root) {
    $q = trim((string)$q);
    $results = [];
    if ($q === '' || !$root || !is_dir($root)) return $results;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!preg_match('/\.md$/i', $file->getFilename())) continue;
        $content = file_get_contents($file->getPathname());
        $inName  = stripos($file->getFilename(), $q) !== false;
        $pos     = stripos($content, $q);
        if ($inName || $pos !== false) {
            $snippet = '';
            if ($pos !== false) {
                $start   = max(0, $pos - 45);
                $snippet = trim(preg_replace('/\s+/', ' ', substr($content, $start, 150)));
            }
            $results[] = [
                'rel'     => kb_relpath($file->getPathname()),
                'title'   => kb_title_from_file($file->getPathname()),
                'snippet' => $snippet,
            ];
        }
    }
    return $results;
}
