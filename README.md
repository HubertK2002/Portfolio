# Portfolio — Hubert Kwiecień

Osobista strona-portfolio (PHP / Backend Developer) wraz z **bazą wiedzy** — moimi
instrukcjami i artykułami „jak-to-zrobić".

## Struktura

```
index.html              # strona główna (statyczna, dwujęzyczna PL/EN)
style.css               # style całości
script.js               # przełącznik języka + menu mobilne
projects/               # strony poszczególnych projektów
instrukcje/             # baza wiedzy (PHP)
  index.php             #   drzewo + treść + wyszukiwarka
  lib/kb.php            #   silnik: drzewo z folderów, render, szukanie
  lib/Parsedown.php     #   Markdown -> HTML
content/                # TREŚĆ bazy wiedzy
  instrukcje/           #   foldery = gałęzie, pliki .md = wpisy
  artykuly/
assets/                 # zdjęcie
```

## Jak dodać instrukcję

Wrzuć plik `.md` do dowolnego folderu w `content/`. Nowy folder = nowa gałąź w drzewie.
Tytuł wpisu brany jest z pierwszego nagłówka `# ...`. Nic więcej nie trzeba — drzewo
i nawigacja budują się automatycznie.

## Uruchomienie lokalne

Część `/instrukcje/` wymaga PHP:

```bash
php -S localhost:8000
```

Strona główna (`index.html`) jest w pełni statyczna.

## Technologie

Statyczny front (HTML/CSS/JS) + lekki backend bazy wiedzy w PHP (Parsedown do renderu
Markdown). Bez frameworka i bez kroku budowania.
