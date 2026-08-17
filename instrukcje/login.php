<?php
require_once __DIR__ . '/../lib/auth.php';

if (kb_is_logged_in()) { header('Location: edytor.php'); exit; }

$err = '';
$next = $_GET['next'] ?? 'edytor.php';
// tylko lokalne, względne przekierowania
if (!preg_match('#^[A-Za-z0-9_./?=&%-]+$#', $next) || strpos($next, '//') !== false) $next = 'edytor.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!kb_configured()) {
        $err = 'Brak konfiguracji hasła (lib/config.php).';
    } elseif (kb_login($_POST['password'] ?? '')) {
        header('Location: ' . $next);
        exit;
    } else {
        usleep(500000); // drobne opóźnienie utrudniające zgadywanie
        $err = 'Błędne hasło.';
    }
}
?><!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Logowanie — Baza wiedzy</title>
<link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="nav"><div class="inner">
  <a href="../index.html" class="brand">Hubert<span>.</span>Kwiecień</a>
  <div class="links"><a href="index.php">← Instrukcje</a></div>
</div></nav>

<div class="auth-wrap">
  <form class="auth-card" method="post" action="login.php?next=<?= htmlspecialchars(urlencode($next)) ?>">
    <h1>Logowanie</h1>
    <p class="auth-sub">Panel dodawania instrukcji</p>
    <?php if ($err): ?><div class="auth-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if (!kb_configured()): ?>
      <div class="auth-note">Najpierw utwórz <code>lib/config.php</code> na podstawie <code>config.sample.php</code> i ustaw hash hasła.</div>
    <?php endif; ?>
    <label>Hasło
      <input type="password" name="password" autofocus autocomplete="current-password" required>
    </label>
    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Zaloguj</button>
  </form>
</div>
</body>
</html>
