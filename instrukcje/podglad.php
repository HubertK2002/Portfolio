<?php
// Podgląd na żywo — renderuje przesłany Markdown (tylko dla zalogowanych)
require __DIR__ . '/../lib/auth.php';
require __DIR__ . '/../lib/kb.php';

if (!kb_is_logged_in()) { http_response_code(403); exit; }
if (!kb_csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit; }

header('Content-Type: text/html; charset=utf-8');
echo kb_render_text($_POST['md'] ?? '');
