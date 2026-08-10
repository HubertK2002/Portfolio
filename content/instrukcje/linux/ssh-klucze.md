# Logowanie po kluczu SSH (bez hasła)

Jak skonfigurować logowanie kluczem zamiast hasła.

## 1. Wygeneruj klucz (lokalnie)

```bash
ssh-keygen -t ed25519 -C "twoj@email.pl"
```

## 2. Skopiuj klucz na serwer

```bash
ssh-copy-id user@serwer
```

## 3. Wyłącz logowanie hasłem (opcjonalnie, dla bezpieczeństwa)

W `/etc/ssh/sshd_config`:

```
PasswordAuthentication no
```

Następnie:

```bash
sudo systemctl restart ssh
```

> **Uwaga:** przed wyłączeniem hasła upewnij się, że logowanie kluczem działa — inaczej odetniesz sobie dostęp.
