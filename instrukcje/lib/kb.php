<?php
// ============================================================
//  kb.php — silnik bazy wiedzy (drzewo z folderów + render MD)
//  Foldery w /content = zakładki/gałęzie, pliki .md = wpisy.
// ============================================================

define('KB_ROOT', realpath(__DIR__ . '/../../content'));

/** Ładna etykieta z nazwy pliku/folderu: "nginx-php-fpm" -> "Nginx php fpm" */
function kb_pretty($name) {
    $n = preg_replace('/\.md$/i', '', $name);
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

/** Ścieżka względna wpisu od KB_ROOT, zawsze ze slashami "/" */
function kb_relpath($abs) {
    $rel = substr(realpath($abs), strlen(KB_ROOT) + 1);
    return str_replace(DIRECTORY_SEPARATOR, '/', $rel);
}

/** Rekurencyjne drzewo folderów i plików .md */
function kb_tree($dir = null) {
    if ($dir === null) $dir = KB_ROOT;
    $out = ['dirs' => [], 'files' => []];
    if (!$dir || !is_dir($dir)) return $out;
    $entries = scandir($dir);
    natcasesort($entries);
    foreach ($entries as $e) {
        if ($e === '.' || $e === '..' || $e[0] === '.') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $e;
        if (is_dir($path)) {
            $out['dirs'][] = [
                'name'     => $e,
                'label'    => kb_pretty($e),
                'children' => kb_tree($path),
            ];
        } elseif (preg_match('/\.md$/i', $e)) {
            $out['files'][] = ['rel' => kb_relpath($path), 'title' => kb_title_from_file($path)];
        }
    }
    return $out;
}

/** Bezpieczne rozwiązanie ścieżki wpisu — ochrona przed path traversal */
function kb_resolve($rel) {
    if (!is_string($rel) || $rel === '') return null;
    if (strpos($rel, "\0") !== false) return null;
    $rel = str_replace('\\', '/', $rel);
    $target = realpath(KB_ROOT . '/' . $rel);
    if ($target === false) return null;
    // musi leżeć wewnątrz KB_ROOT i być plikiem .md
    if (strpos($target, KB_ROOT . DIRECTORY_SEPARATOR) !== 0) return null;
    if (!preg_match('/\.md$/i', $target)) return null;
    return $target;
}

/** Render pliku .md do bezpiecznego HTML (Parsedown, safe mode) */
function kb_render($absfile) {
    require_once __DIR__ . '/Parsedown.php';
    static $pd = null;
    if ($pd === null) { $pd = new Parsedown(); $pd->setSafeMode(true); }
    return $pd->text(file_get_contents($absfile));
}

/** Przeszukiwanie treści wszystkich .md */
function kb_search($q) {
    $q = trim((string)$q);
    $results = [];
    if ($q === '' || !KB_ROOT || !is_dir(KB_ROOT)) return $results;
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(KB_ROOT, FilesystemIterator::SKIP_DOTS)
    );
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
