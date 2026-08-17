<?php
// ============================================================
//  KONFIGURACJA — skopiuj do "config.php" i uzupełnij.
//  config.php jest w .gitignore, więc sekrety nie trafią do repo.
//
//  Hash hasła do edytora (nie trzymamy hasła jawnie):
//     php -r 'echo password_hash("TWOJE_HASLO", PASSWORD_DEFAULT), "\n";'
// ============================================================
return [
    'password_hash' => '$2y$12$WSTAW_TUTAJ_WYGENEROWANY_HASH',

    // Baza danych
    'db_host' => '127.0.0.1',
    'db_port' => 3306,
    'db_name' => 'kb',
    'db_user' => 'kb',
    'db_pass' => 'WSTAW_HASLO_DO_BAZY',
];
