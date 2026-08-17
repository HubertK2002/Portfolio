<?php
require_once __DIR__ . '/../lib/nodes.php';
require_once __DIR__ . '/../lib/auth.php';
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
if (!kb_csrf_check($_POST['csrf'] ?? '')) kb_fail('Nieprawidłowy token (odśwież edytor).');

$id       = isset($_POST['id']) && $_POST['id'] !== '' ? (int)$_POST['id'] : 0;
$parentId = ($_POST['parent_id'] ?? '') === '' ? null : (int)$_POST['parent_id'];
$title    = trim($_POST['title'] ?? '');
$slug     = trim($_POST['slug'] ?? '');
$content  = (string)($_POST['content'] ?? '');

if ($title === '') kb_fail('Podaj tytuł.');
if ($parentId !== null && !node_get($parentId)) kb_fail('Wybrany rodzic nie istnieje.');

if ($id) {
    if (!node_get($id)) kb_fail('Węzeł nie istnieje.');
    // ochrona przed cyklem: rodzic nie może być sobą ani potomkiem
    if ($parentId === $id) kb_fail('Węzeł nie może być swoim rodzicem.');
    $cur = $parentId;
    while ($cur) {
        if ($cur === $id) kb_fail('Nie można przenieść węzła do jego potomka.');
        $p = node_get($cur);
        $cur = $p ? ($p['parent_id'] ? (int)$p['parent_id'] : null) : null;
    }
    node_update($id, $parentId, $title, $slug, $content);
    $newId = $id;
} else {
    $newId = node_create($parentId, $title, $slug, $content);
}

header('Location: index.php?node=' . $newId);
exit;
