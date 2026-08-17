<?php
// ============================================================
//  db.php — połączenie PDO z bazą (dane z lib/config.php)
// ============================================================
require_once __DIR__ . '/auth.php'; // dla kb_config()

function kb_db() {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $c = kb_config();
    $host = $c['db_host'] ?? '127.0.0.1';
    $port = $c['db_port'] ?? 3306;
    $name = $c['db_name'] ?? '';
    $user = $c['db_user'] ?? '';
    $pass = $c['db_pass'] ?? '';
    $dsn  = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
