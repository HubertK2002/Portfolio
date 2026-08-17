<?php
require_once __DIR__ . '/../lib/nodes.php';
require_once __DIR__ . '/../lib/auth.php';
kb_require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
if (!kb_csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

$id = (int)($_POST['id'] ?? 0);
if ($id && node_get($id)) node_delete($id); // ON DELETE CASCADE zdejmie potomków

header('Location: index.php');
exit;
