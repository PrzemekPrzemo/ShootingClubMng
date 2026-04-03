# Klub Strzelecki — System zarządzania

PHP 8.x + MySQL 8.x, Bootstrap 5. Architektura MVC bez frameworka.

## Instalacja

1. Sklonuj repo i ustaw katalog `public/` jako document root serwera.
2. Skopiuj `config/database.php` → `config/database.local.php` i uzupełnij dane bazy.
3. Zaimportuj schemat:
   ```bash
   mysql -u root -p shooting_club < database/schema.sql
   ```
4. Zmień hasło admina (domyślny login: `admin`, hasło: `password`):
   - Zaloguj się i zmień hasło przez panel konfiguracji użytkowników, lub
   - Wygeneruj hash w PHP: `echo password_hash('TwojeNoweSilneHasło', PASSWORD_BCRYPT);`
     i wklej do tabeli `users`.

## Moduły

| Moduł | URL |
|-------|-----|
| Logowanie | `/auth/login` |
| Dashboard | `/dashboard` |
| Zawodnicy | `/members` |
| Badania sportowe | `/members/{id}/exams` |
| Licencje PZSS | `/licenses` |
| Finanse | `/finances` |
| Zaległości | `/finances/debts` |
| Zawody | `/competitions` |
| Raporty | `/reports` |
| Konfiguracja | `/config` |

## Role

| Rola | Uprawnienia |
|------|-------------|
| `admin` | Pełny dostęp |
| `zarzad` | Wszystko oprócz zarządzania użytkownikami |
| `instruktor` | Odczyt, tworzenie zawodów i wyników |

## Deployment Plesk

- Document root → `public/`
- PHP 8.x z rozszerzeniami: `pdo_mysql`, `mbstring`, `json`
- Plik `config/database.local.php` zawiera produkcyjne dane (git-ignored)
