<?php
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/kb.php';
kb_require_login();

function kb_fail($msg) {
    http_response_code(400);
    echo '<!DOCTYPE html><meta charset="UTF-8"><link rel="stylesheet" href="../style.css">'
       . '<div class="auth-wrap"><div class="auth-card"><h1>Nie zapisano</h1>'
       . '<div class="auth-err">' . htmlspecialchars($msg) . '</div>'
       . '<p><a href="edytor.php">← Wróć do edytora</a></p></div></div>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: edytor.php'); exit; }
if (!kb_csrf_check($_POST['csrf'] ?? '')) kb_fail('Nieprawidłowy token (odśwież edytor i spróbuj ponownie).');

$title   = trim($_POST['title'] ?? '');
$catSel  = $_POST['category'] ?? '';
$catNew  = $_POST['new_category'] ?? '';
$fileRaw = $_POST['filename'] ?? '';
$body    = (string)($_POST['content'] ?? '');

$category = kb_slug($catSel === '__new__' ? $catNew : $catSel);
if ($category === '') kb_fail('Podaj kategorię.');

$slug = kb_slug($fileRaw !== '' ? $fileRaw : $title);
if ($slug === '') kb_fail('Podaj tytuł lub nazwę pliku.');

$instr = realpath(CONTENT_ROOT . '/instrukcje');
if (!$instr) kb_fail('Brak folderu content/instrukcje.');

$catDir = $instr . DIRECTORY_SEPARATOR . $category;
if (!is_dir($catDir)) {
    if (!@mkdir($catDir, 0775, true)) kb_fail('Nie udało się utworzyć kategorii.');
}
$catReal = realpath($catDir);
// twarda kontrola: katalog musi leżeć wewnątrz content/instrukcje
if ($catReal === false || strpos($catReal, $instr . DIRECTORY_SEPARATOR) !== 0) kb_fail('Nieprawidłowa kategoria.');

$target = $catReal . DIRECTORY_SEPARATOR . $slug . '.md';
if (file_exists($target)) kb_fail('Plik „' . $slug . '.md" już istnieje w tej kategorii. Zmień nazwę pliku.');

// treść: zapewnij nagłówek z tytułem
$content = $body;
if ($title !== '' && !preg_match('/^\s*#\s+/', $content)) {
    $content = '# ' . $title . "\n\n" . $content;
}
if (file_put_contents($target, $content) === false) kb_fail('Nie udało się zapisać pliku (uprawnienia?).');

$rel = 'instrukcje/' . $category . '/' . $slug . '.md';
header('Location: index.php?doc=' . urlencode($rel));
exit;
