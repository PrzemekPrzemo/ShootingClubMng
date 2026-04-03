#!/usr/bin/env bash
# =============================================================================
#  Klub Strzelecki — Skrypt instalacyjny
#  Użycie: bash install.sh
# =============================================================================

set -euo pipefail

# ─── Kolory ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# ─── Helpers ─────────────────────────────────────────────────────────────────
info()    { echo -e "${CYAN}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
error()   { echo -e "${RED}[BŁĄD]${NC}  $*" >&2; }
die()     { error "$*"; exit 1; }

ask() {
    # ask <prompt> [default]
    local prompt="$1"
    local default="${2:-}"
    local value
    if [[ -n "$default" ]]; then
        read -rp "$(echo -e "  ${BOLD}${prompt}${NC} [${default}]: ")" value
        echo "${value:-$default}"
    else
        read -rp "$(echo -e "  ${BOLD}${prompt}${NC}: ")" value
        echo "$value"
    fi
}

ask_secret() {
    local prompt="$1"
    local value confirm
    while true; do
        read -rsp "$(echo -e "  ${BOLD}${prompt}${NC}: ")" value
        echo
        read -rsp "$(echo -e "  ${BOLD}Powtórz hasło${NC}: ")" confirm
        echo
        if [[ "$value" == "$confirm" ]]; then
            echo "$value"
            return
        fi
        warn "Hasła nie są identyczne. Spróbuj ponownie."
    done
}

ask_yn() {
    local prompt="$1"
    local default="${2:-y}"
    local answer
    read -rp "$(echo -e "  ${BOLD}${prompt}${NC} [y/n, domyślnie ${default}]: ")" answer
    answer="${answer:-$default}"
    [[ "${answer,,}" == "y" ]]
}

separator() { echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"; }

# =============================================================================
# 0. Baner
# =============================================================================
clear
separator
echo -e "  ${BOLD}${RED}🎯  Klub Strzelecki — System zarządzania${NC}"
echo -e "  Instalator v1.0"
separator
echo

# =============================================================================
# 1. Sprawdzenie wymagań
# =============================================================================
info "Sprawdzanie wymagań systemowych…"

INSTALL_DIR="$(cd "$(dirname "$0")" && pwd)"
info "Katalog instalacji: ${INSTALL_DIR}"

# PHP
if ! command -v php &>/dev/null; then
    die "PHP nie jest zainstalowane. Zainstaluj PHP 8.1 lub nowsze."
fi
PHP_VER=$(php -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
PHP_MAJOR=$(php -r 'echo PHP_MAJOR_VERSION;')
PHP_MINOR=$(php -r 'echo PHP_MINOR_VERSION;')
if (( PHP_MAJOR < 8 )) || { (( PHP_MAJOR == 8 )) && (( PHP_MINOR < 1 )); }; then
    die "Wymagane PHP >= 8.1. Zainstalowane: ${PHP_VER}"
fi
success "PHP ${PHP_VER}"

# MySQL / MariaDB client
MYSQL_CMD=""
if command -v mysql &>/dev/null; then
    MYSQL_CMD="mysql"
    success "MySQL client: $(mysql --version | head -1)"
elif command -v mariadb &>/dev/null; then
    MYSQL_CMD="mariadb"
    success "MariaDB client: $(mariadb --version | head -1)"
else
    die "Klient MySQL/MariaDB nie jest zainstalowany."
fi

# PHP rozszerzenia
for ext in pdo pdo_mysql mbstring json; do
    if php -m | grep -qi "^${ext}$"; then
        success "Rozszerzenie PHP: ${ext}"
    else
        die "Brakuje rozszerzenia PHP: ${ext}. Zainstaluj i spróbuj ponownie."
    fi
done

echo

# =============================================================================
# 2. Konfiguracja bazy danych
# =============================================================================
separator
echo -e "  ${BOLD}KROK 1 — Konfiguracja bazy danych${NC}"
separator
echo

DB_HOST=$(ask "Host bazy danych" "localhost")
DB_PORT=$(ask "Port bazy danych" "3306")
DB_NAME=$(ask "Nazwa bazy danych" "shooting_club")
DB_USER=$(ask "Użytkownik bazy danych" "root")

read -rsp "$(echo -e "  ${BOLD}Hasło użytkownika bazy danych${NC}: ")" DB_PASS
echo

echo
info "Testowanie połączenia z bazą danych…"

MYSQL_CONN_ARGS="-h${DB_HOST} -P${DB_PORT} -u${DB_USER}"
if [[ -n "$DB_PASS" ]]; then
    MYSQL_CONN_ARGS="${MYSQL_CONN_ARGS} -p${DB_PASS}"
fi

if ! $MYSQL_CMD ${MYSQL_CONN_ARGS} -e "SELECT 1;" &>/dev/null; then
    die "Nie można połączyć się z bazą danych. Sprawdź dane i spróbuj ponownie."
fi
success "Połączenie z bazą danych działa."

# Sprawdź czy baza istnieje
DB_EXISTS=$($MYSQL_CMD ${MYSQL_CONN_ARGS} -se "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}';" 2>/dev/null || true)
if [[ -n "$DB_EXISTS" ]]; then
    warn "Baza danych '${DB_NAME}' już istnieje."
    if ask_yn "Czy chcesz ją usunąć i stworzyć od nowa? (UWAGA: usunie wszystkie dane!)" "n"; then
        $MYSQL_CMD ${MYSQL_CONN_ARGS} -e "DROP DATABASE \`${DB_NAME}\`;"
        info "Baza '${DB_NAME}' usunięta."
        $MYSQL_CMD ${MYSQL_CONN_ARGS} -e "CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
        success "Baza '${DB_NAME}' utworzona."
    else
        info "Używam istniejącej bazy danych."
    fi
else
    info "Tworzenie bazy danych '${DB_NAME}'…"
    $MYSQL_CMD ${MYSQL_CONN_ARGS} -e "CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    success "Baza '${DB_NAME}' utworzona."
fi

echo

# =============================================================================
# 3. Dane aplikacji
# =============================================================================
separator
echo -e "  ${BOLD}KROK 2 — Dane aplikacji${NC}"
separator
echo

APP_CLUB_NAME=$(ask "Nazwa klubu" "Klub Strzelecki")
APP_CLUB_EMAIL=$(ask "E-mail klubu" "")
APP_CLUB_PHONE=$(ask "Telefon klubu" "")

echo
echo -e "  ${BOLD}Tryb debug (nie włączaj na produkcji!)${NC}"
if ask_yn "Włączyć tryb debug?" "n"; then
    APP_DEBUG="true"
else
    APP_DEBUG="false"
fi

echo

# =============================================================================
# 4. Konto administratora
# =============================================================================
separator
echo -e "  ${BOLD}KROK 3 — Konto administratora${NC}"
separator
echo

ADMIN_FULLNAME=$(ask "Imię i nazwisko administratora" "Administrator")
ADMIN_USERNAME=$(ask "Login administratora" "admin")
ADMIN_EMAIL=$(ask "E-mail administratora" "admin@${APP_CLUB_NAME,,}.pl")

while true; do
    ADMIN_PASS=$(ask_secret "Hasło administratora (min. 8 znaków)")
    if [[ ${#ADMIN_PASS} -ge 8 ]]; then
        break
    fi
    warn "Hasło musi mieć co najmniej 8 znaków."
done

echo

# =============================================================================
# 5. Generowanie konfiguracji
# =============================================================================
separator
echo -e "  ${BOLD}KROK 4 — Generowanie plików konfiguracyjnych${NC}"
separator

# ── database.local.php ──
info "Tworzenie config/database.local.php…"
cat > "${INSTALL_DIR}/config/database.local.php" <<PHP
<?php
// Plik wygenerowany przez install.sh — $(date '+%Y-%m-%d %H:%M:%S')
// NIE COMMITUJ tego pliku do repozytorium!

return [
    'host'     => '${DB_HOST}',
    'port'     => ${DB_PORT},
    'dbname'   => '${DB_NAME}',
    'username' => '${DB_USER}',
    'password' => '${DB_PASS}',
    'charset'  => 'utf8mb4',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ],
];
PHP
success "config/database.local.php"

# ── app.local.php ──
info "Tworzenie config/app.local.php…"
cat > "${INSTALL_DIR}/config/app.local.php" <<PHP
<?php
// Plik wygenerowany przez install.sh — $(date '+%Y-%m-%d %H:%M:%S')

return [
    'app_name'    => '${APP_CLUB_NAME}',
    'app_version' => '1.0.0',
    'debug'       => ${APP_DEBUG},
    'timezone'    => 'Europe/Warsaw',
    'locale'      => 'pl_PL',
    'base_url'    => '',
    'session_lifetime' => 7200,
    'root_path'   => dirname(__DIR__),
    'view_path'   => dirname(__DIR__) . '/app/Views',
    'upload_path' => dirname(__DIR__) . '/public/uploads',
];
PHP
success "config/app.local.php"

echo

# =============================================================================
# 6. Import schematu bazy danych
# =============================================================================
separator
echo -e "  ${BOLD}KROK 5 — Import schematu bazy danych${NC}"
separator

info "Importowanie ${INSTALL_DIR}/database/schema.sql…"
$MYSQL_CMD ${MYSQL_CONN_ARGS} "${DB_NAME}" < "${INSTALL_DIR}/database/schema.sql"
success "Schemat bazy danych zaimportowany."

# Aktualizacja nazwy klubu i e-maila w settings
info "Aktualizacja ustawień klubu w bazie danych…"
$MYSQL_CMD ${MYSQL_CONN_ARGS} "${DB_NAME}" <<SQL
UPDATE settings SET value = '${APP_CLUB_NAME}'  WHERE \`key\` = 'club_name';
UPDATE settings SET value = '${APP_CLUB_EMAIL}' WHERE \`key\` = 'club_email';
UPDATE settings SET value = '${APP_CLUB_PHONE}' WHERE \`key\` = 'club_phone';
SQL
success "Ustawienia klubu zapisane."

# Generuj hash hasła administratora przez PHP
info "Generowanie konta administratora…"
ADMIN_HASH=$(php -r "echo password_hash('${ADMIN_PASS}', PASSWORD_BCRYPT, ['cost' => 12]);")

$MYSQL_CMD ${MYSQL_CONN_ARGS} "${DB_NAME}" <<SQL
-- Usuń domyślnego admina z seeda, jeśli istnieje
DELETE FROM users WHERE username = 'admin';

-- Utwórz właściwe konto admina
INSERT INTO users (username, email, password, role, full_name, is_active)
VALUES ('${ADMIN_USERNAME}', '${ADMIN_EMAIL}', '${ADMIN_HASH}', 'admin', '${ADMIN_FULLNAME}', 1);
SQL
success "Konto administratora '${ADMIN_USERNAME}' utworzone."

echo

# =============================================================================
# 7. Uprawnienia plików i katalogów
# =============================================================================
separator
echo -e "  ${BOLD}KROK 6 — Uprawnienia plików i katalogów${NC}"
separator

# Katalogi do zapisu
info "Tworzenie katalogów na pliki tymczasowe…"
mkdir -p "${INSTALL_DIR}/logs"
mkdir -p "${INSTALL_DIR}/public/uploads"

info "Ustawianie uprawnień katalogów…"

# Pliki PHP — tylko do odczytu dla serwera WWW
find "${INSTALL_DIR}" \
    -not -path "${INSTALL_DIR}/.git/*" \
    -not -path "${INSTALL_DIR}/logs/*" \
    -not -path "${INSTALL_DIR}/public/uploads/*" \
    -type f \
    -exec chmod 644 {} \;
success "Pliki: 644 (rw-r--r--)"

# Katalogi
find "${INSTALL_DIR}" \
    -not -path "${INSTALL_DIR}/.git/*" \
    -type d \
    -exec chmod 755 {} \;
success "Katalogi: 755 (rwxr-xr-x)"

# Katalogi wymagające zapisu przez serwer WWW
chmod 775 "${INSTALL_DIR}/logs"
chmod 775 "${INSTALL_DIR}/public/uploads"
success "logs/ i public/uploads/: 775 (rwxrwxr-x)"

# Pliki z danymi wrażliwymi — bardziej restrykcyjne
chmod 600 "${INSTALL_DIR}/config/database.local.php"
chmod 600 "${INSTALL_DIR}/config/app.local.php"
success "config/*.local.php: 600 (rw-------)"

# Skrypt instalacyjny — tylko odczyt po instalacji
chmod 644 "${INSTALL_DIR}/install.sh"

# Sprawdź czy istnieje użytkownik www-data / apache / nginx
WWW_USER=""
for candidate in www-data apache nginx http; do
    if id "$candidate" &>/dev/null; then
        WWW_USER="$candidate"
        break
    fi
done

if [[ -n "$WWW_USER" ]]; then
    info "Wykryto użytkownika serwera WWW: ${WWW_USER}"
    if ask_yn "Ustawić właściciela logs/ i uploads/ na ${WWW_USER}?" "y"; then
        chown -R "${WWW_USER}:${WWW_USER}" "${INSTALL_DIR}/logs" \
            "${INSTALL_DIR}/public/uploads" 2>/dev/null || \
            warn "Brak uprawnień do zmiany właściciela (uruchom jako root)."
        success "Właściciel logs/ i uploads/ → ${WWW_USER}"
    fi
else
    warn "Nie wykryto użytkownika serwera WWW (www-data/apache/nginx)."
    warn "Ustaw ręcznie: chown -R <www-user>: ${INSTALL_DIR}/logs ${INSTALL_DIR}/public/uploads"
fi

echo

# =============================================================================
# 8. Aktualizacja index.php (obsługa app.local.php)
# =============================================================================
# Dodaj obsługę config/app.local.php do front controllera jeśli nie ma
if ! grep -q 'app.local.php' "${INSTALL_DIR}/public/index.php"; then
    info "Aktualizacja public/index.php do obsługi app.local.php…"
    sed -i 's|require ROOT_PATH . '"'"'/config/app.php'"'"';|$localApp = ROOT_PATH . '"'"'/config/app.local.php'"'"';\n$appConfig = file_exists($localApp) ? require $localApp : require ROOT_PATH . '"'"'/config/app.php'"'"';|g' \
        "${INSTALL_DIR}/public/index.php"
    success "public/index.php zaktualizowany."
fi

echo

# =============================================================================
# 9. Weryfikacja instalacji
# =============================================================================
separator
echo -e "  ${BOLD}KROK 7 — Weryfikacja instalacji${NC}"
separator

ERRORS=0

# Sprawdź tabele
info "Sprawdzanie struktury bazy danych…"
EXPECTED_TABLES="users members member_age_categories member_disciplines member_medical_exams licenses payments payment_types disciplines competitions competition_entries competition_results competition_groups settings activity_log"
for tbl in $EXPECTED_TABLES; do
    COUNT=$($MYSQL_CMD ${MYSQL_CONN_ARGS} "${DB_NAME}" -se "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}' AND TABLE_NAME='${tbl}';" 2>/dev/null)
    if [[ "$COUNT" == "1" ]]; then
        success "Tabela: ${tbl}"
    else
        error "Brak tabeli: ${tbl}"
        ((ERRORS++))
    fi
done

# Sprawdź konto admina
info "Sprawdzanie konta administratora…"
ADMIN_COUNT=$($MYSQL_CMD ${MYSQL_CONN_ARGS} "${DB_NAME}" -se "SELECT COUNT(*) FROM users WHERE username='${ADMIN_USERNAME}' AND role='admin';" 2>/dev/null)
if [[ "$ADMIN_COUNT" == "1" ]]; then
    success "Konto administratora: ${ADMIN_USERNAME}"
else
    error "Brak konta administratora!"
    ((ERRORS++))
fi

# Sprawdź pliki konfiguracyjne
for cfg in config/database.local.php config/app.local.php; do
    if [[ -f "${INSTALL_DIR}/${cfg}" ]]; then
        success "Plik: ${cfg}"
    else
        error "Brak pliku: ${cfg}"
        ((ERRORS++))
    fi
done

echo

# =============================================================================
# 10. Podsumowanie
# =============================================================================
separator
if [[ $ERRORS -eq 0 ]]; then
    echo -e "  ${GREEN}${BOLD}✅  Instalacja zakończona pomyślnie!${NC}"
else
    echo -e "  ${RED}${BOLD}⚠️   Instalacja zakończona z ${ERRORS} błędami.${NC}"
fi
separator
echo
echo -e "  ${BOLD}Dane logowania:${NC}"
echo -e "    Login:  ${CYAN}${ADMIN_USERNAME}${NC}"
echo -e "    Hasło:  ${CYAN}(podane podczas instalacji)${NC}"
echo
echo -e "  ${BOLD}Konfiguracja serwera WWW:${NC}"
echo -e "    Document Root → ${CYAN}${INSTALL_DIR}/public/${NC}"
echo
echo -e "  ${BOLD}Następne kroki:${NC}"
echo -e "    1. Ustaw Document Root serwera na: ${CYAN}${INSTALL_DIR}/public${NC}"
echo -e "    2. Włącz mod_rewrite (Apache) lub odpowiednik (Nginx/Plesk)"
echo -e "    3. Otwórz aplikację i zaloguj się"
echo -e "    4. Uzupełnij dane klubu w: ${CYAN}Konfiguracja → Ustawienia${NC}"
echo
if [[ "$APP_DEBUG" == "true" ]]; then
    warn "Tryb DEBUG jest włączony. Wyłącz go przed wdrożeniem produkcyjnym!"
    warn "Edytuj: config/app.local.php → 'debug' => false"
fi
separator
echo
