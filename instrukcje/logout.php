<?php
require __DIR__ . '/../lib/auth.php';
kb_logout();
header('Location: index.php');
exit;
