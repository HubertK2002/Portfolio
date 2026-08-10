# Konfiguracja Nginx + PHP-FPM (Debian)

Szybka ściąga do postawienia aplikacji PHP na Nginx z PHP-FPM.

## Instalacja

```bash
sudo apt update
sudo apt install nginx php-fpm php-mysql
```

## Blok serwera

Plik `/etc/nginx/sites-available/moja-app`:

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/moja-app/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
```

## Aktywacja

```bash
sudo ln -s /etc/nginx/sites-available/moja-app /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

> **Wskazówka:** `nginx -t` sprawdza konfigurację przed przeładowaniem — zawsze uruchamiaj przed `reload`.
