<?php
// ============================================================
//  KONFIGURACJA — skopiuj ten plik do "config.php" i ustaw hash hasła.
//  config.php jest w .gitignore, więc sekret nie trafi do repozytorium.
//
//  Wygeneruj hash swojego hasła (nie przechowujemy hasła jawnie):
//     php -r 'echo password_hash("TWOJE_HASLO", PASSWORD_DEFAULT), "\n";'
//  i wklej wynik poniżej.
// ============================================================
return [
    'password_hash' => '$2y$12$WSTAW_TUTAJ_WYGENEROWANY_HASH',
];
