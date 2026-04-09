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
echo -e "  Instalator v2.0 (multi-tenant)"
separator
echo

# =============================================================================
# 1. Sprawdzenie wymagań
# =============================================================================
info "Sprawdzanie wymagań systemowych…"

INSTALL_DIR="$(cd "$(dirname "$0")" && pwd)"
info "Katalog instalacji: ${INSTALL_DIR}"

# PHP — szukaj PHP >= 8.1 w typowych lokalizacjach (Plesk, Ubuntu, Debian, RHEL)
# Można nadpisać z zewnątrz: PHP_CMD=/opt/plesk/php/8.3/bin/php bash install.sh
PHP_CMD="${PHP_CMD:-}"

if [[ -n "$PHP_CMD" ]]; then
    # Użytkownik podał ścieżkę ręcznie — tylko zweryfikuj wersję
    if ! command -v "$PHP_CMD" &>/dev/null && [[ ! -x "$PHP_CMD" ]]; then
        die "Podany PHP_CMD nie istnieje lub nie jest wykonywalny: ${PHP_CMD}"
    fi
    PHP_VER=$("$PHP_CMD" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;')
    _major=$("$PHP_CMD" -r 'echo PHP_MAJOR_VERSION;')
    _minor=$("$PHP_CMD" -r 'echo PHP_MINOR_VERSION;')
    if (( _major < 8 )) || { (( _major == 8 )) && (( _minor < 1 )); }; then
        die "Podany PHP_CMD to wersja ${PHP_VER} — wymagane >= 8.1"
    fi
    success "PHP ${PHP_VER} → ${PHP_CMD} (ręcznie)"
else

# Lista kandydatów od najnowszego
PHP_CANDIDATES=(
    # Plesk — /opt/plesk/php/X.Y/bin/php
    /opt/plesk/php/8.4/bin/php
    /opt/plesk/php/8.3/bin/php
    /opt/plesk/php/8.2/bin/php
    /opt/plesk/php/8.1/bin/php
    # Ubuntu/Debian — php8.X z PPA
    php8.4 php8.3 php8.2 php8.1
    # RHEL/CentOS — SCL lub Remi
    /opt/remi/php84/root/usr/bin/php
    /opt/remi/php83/root/usr/bin/php
    /opt/remi/php82/root/usr/bin/php
    /opt/remi/php81/root/usr/bin/php
    # Domyślne php (może być 8.x)
    php
)

for candidate in "${PHP_CANDIDATES[@]}"; do
    if command -v "$candidate" &>/dev/null || [[ -x "$candidate" ]]; then
        _ver=$("$candidate" -r 'echo PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION;' 2>/dev/null || true)
        _major=$("$candidate" -r 'echo PHP_MAJOR_VERSION;' 2>/dev/null || true)
        _minor=$("$candidate" -r 'echo PHP_MINOR_VERSION;' 2>/dev/null || true)
        if (( _major > 8 )) || { (( _major == 8 )) && (( _minor >= 1 )); }; then
            PHP_CMD="$candidate"
            PHP_VER="$_ver"
            break
        fi
    fi
done

if [[ -z "$PHP_CMD" ]]; then
    # Pokaż co jest dostępne, żeby pomóc użytkownikowi
    echo
    warn "Nie znaleziono PHP >= 8.1 w standardowych lokalizacjach."
    warn "Dostępne wersje PHP na tym serwerze:"
    for candidate in "${PHP_CANDIDATES[@]}"; do
        if command -v "$candidate" &>/dev/null || [[ -x "$candidate" ]]; then
            _v=$("$candidate" -r 'echo phpversion();' 2>/dev/null || echo "?")
            echo -e "    ${CYAN}${candidate}${NC}  →  ${_v}"
        fi
    done
    # Sprawdź Plesk
    if ls /opt/plesk/php/*/bin/php &>/dev/null; then
        echo
        info "Zainstalowane wersje Plesk PHP:"
        for p in /opt/plesk/php/*/bin/php; do
            echo -e "    ${CYAN}${p}${NC}  →  $($p -r 'echo phpversion();' 2>/dev/null || echo '?')"
        done
    fi
    echo
    die "Zainstaluj PHP 8.1+ lub podaj ścieżkę ręcznie: PHP_CMD=/opt/plesk/php/8.3/bin/php bash install.sh"
fi

fi  # koniec bloku else (auto-detect)

success "PHP ${PHP_VER} → ${PHP_CMD}"

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

# PHP rozszerzenia wymagane
for ext in pdo pdo_mysql mbstring json curl openssl fileinfo; do
    if $PHP_CMD -m | grep -qi "^${ext}$"; then
        success "Rozszerzenie PHP: ${ext}"
    else
        die "Brakuje rozszerzenia PHP: ${ext}. Włącz w Plesk: PHP → Rozszerzenia → ${ext}"
    fi
done
# GD (wymagane przez mPDF do generowania PDF)
if $PHP_CMD -m | grep -qi "^gd$"; then
    success "Rozszerzenie PHP: gd"
else
    warn "Brak rozszerzenia PHP: gd — generowanie PDF (mPDF) może nie działać."
    warn "Włącz w Plesk: PHP → Rozszerzenia → gd"
fi

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

# ── Tymczasowy plik opcji MySQL (bezpieczne przekazywanie hasła) ──────────────
# Unikamy -p<hasło> w argumentach (widoczne w ps, problemy ze znakami specjalnymi)
MYSQL_CNF=$(mktemp /tmp/.mysql_install_XXXXXX.cnf)
chmod 600 "$MYSQL_CNF"
cat > "$MYSQL_CNF" <<CNF
[client]
host=${DB_HOST}
port=${DB_PORT}
user=${DB_USER}
password=${DB_PASS}
CNF
# Usuń plik tymczasowy przy wyjściu (normalnym i błędzie)
trap 'rm -f "$MYSQL_CNF"' EXIT

# Wrapper — używa pliku opcji zamiast flag
mysql_cmd() { $MYSQL_CMD --defaults-extra-file="$MYSQL_CNF" "$@"; }

echo
info "Testowanie połączenia z bazą danych…"

# Pokaż rzeczywisty błąd jeśli coś nie gra
if ! MYSQL_ERR=$(mysql_cmd -e "SELECT 1;" 2>&1); then
    error "Nie można połączyć się z bazą danych."
    echo -e "  ${YELLOW}Szczegóły błędu:${NC} ${MYSQL_ERR}"
    echo
    echo -e "  ${BOLD}Wskazówki Plesk:${NC}"
    echo -e "  • Bazę danych i użytkownika utwórz najpierw w panelu Plesk:"
    echo -e "    Strony → wksfg.pl → Bazy danych → Dodaj bazę danych"
    echo -e "  • Użytkownik bazy = ten przypisany w Plesk (np. u_wksfg_)"
    echo -e "  • Host = 'localhost' lub '127.0.0.1'"
    echo -e "  • Hasło = podane przy tworzeniu użytkownika w Plesk"
    echo
    die "Popraw dane i uruchom install.sh ponownie."
fi
success "Połączenie z bazą danych działa."

# ── Sprawdź czy baza danych istnieje i jest dostępna ─────────────────────────
info "Sprawdzanie bazy danych '${DB_NAME}'…"
DB_EXISTS=$(mysql_cmd -se "SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME='${DB_NAME}';" 2>/dev/null || true)

if [[ -n "$DB_EXISTS" ]]; then
    warn "Baza danych '${DB_NAME}' już istnieje."
    # Sprawdź czy ma już tabele (reinstalacja?)
    TABLE_COUNT=$(mysql_cmd -se "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}';" 2>/dev/null || echo "0")
    if [[ "$TABLE_COUNT" -gt 0 ]]; then
        warn "Baza zawiera już ${TABLE_COUNT} tabel(e)!"
        if ask_yn "Czy wyczyścić bazę i zainstalować od nowa? (UWAGA: usunie wszystkie dane!)" "n"; then
            info "Czyszczenie bazy danych '${DB_NAME}'…"
            # Wyczyść tabele zamiast DROP DATABASE (user może nie mieć DROP privilege)
            mysql_cmd "${DB_NAME}" -e "
                SET FOREIGN_KEY_CHECKS=0;
                SET GROUP_CONCAT_MAX_LEN=32768;
                SET @tables = NULL;
                SELECT GROUP_CONCAT('\`', table_name, '\`') INTO @tables
                  FROM information_schema.tables WHERE table_schema = DATABASE();
                SET @tables = IFNULL(@tables, '1');
                SET @drop = CONCAT('DROP TABLE IF EXISTS ', @tables);
                PREPARE stmt FROM @drop;
                EXECUTE stmt;
                DEALLOCATE PREPARE stmt;
                SET FOREIGN_KEY_CHECKS=1;
            " 2>/dev/null && success "Baza wyczyszczona." || warn "Nie udało się wyczyścić — import może nadpisać istniejące tabele."
        else
            info "Kontynuuję z istniejącą bazą (tabele zostaną nadpisane jeśli istnieją)."
        fi
    else
        success "Baza '${DB_NAME}' jest pusta — gotowa do instalacji."
    fi
else
    # Baza nie istnieje — spróbuj utworzyć
    info "Baza '${DB_NAME}' nie istnieje. Próba utworzenia…"
    if mysql_cmd -e "CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null; then
        success "Baza '${DB_NAME}' utworzona."
    else
        error "Nie można automatycznie utworzyć bazy danych."
        echo -e "  ${YELLOW}Na Plesk utwórz bazę ręcznie:${NC}"
        echo -e "  Panel Plesk → Strony → Bazy danych → Dodaj bazę danych"
        echo -e "  Nazwa bazy: ${CYAN}${DB_NAME}${NC}"
        echo
        die "Utwórz bazę w Plesk i uruchom install.sh ponownie."
    fi
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

# =============================================================================
# 4b. Composer — instalacja zależności PHP
# =============================================================================
separator
echo -e "  ${BOLD}KROK 4b — Instalacja zależności (Composer)${NC}"
separator

if [[ -f "${INSTALL_DIR}/vendor/autoload.php" ]]; then
    success "vendor/autoload.php już istnieje — pomijam Composer."
else
    COMPOSER_CMD=""
    # Szukaj systemowego composera
    for candidate in composer composer.phar /usr/local/bin/composer /usr/bin/composer; do
        if command -v "$candidate" &>/dev/null || [[ -x "$candidate" ]]; then
            COMPOSER_CMD="$candidate"
            break
        fi
    done

    if [[ -z "$COMPOSER_CMD" ]]; then
        info "Pobieranie Composer…"
        $PHP_CMD -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
        $PHP_CMD /tmp/composer-setup.php --quiet --install-dir="${INSTALL_DIR}" --filename=composer.phar
        rm -f /tmp/composer-setup.php
        COMPOSER_CMD="${INSTALL_DIR}/composer.phar"
        success "Composer pobrany: ${COMPOSER_CMD}"
    else
        success "Composer znaleziony: ${COMPOSER_CMD}"
    fi

    info "Instalowanie zależności (bez dev, z optymalizacją autoloadera)…"
    $PHP_CMD "$COMPOSER_CMD" install \
        --working-dir="${INSTALL_DIR}" \
        --no-dev \
        --optimize-autoloader \
        --no-interaction 2>&1 | grep -v "^$" | tail -20
    success "Zależności zainstalowane."
fi

echo

echo

# =============================================================================
# 6. Import schematu bazy danych
# =============================================================================
separator
echo -e "  ${BOLD}KROK 5 — Import schematu bazy danych${NC}"
separator

info "Importowanie ${INSTALL_DIR}/database/schema.sql…"
mysql_cmd "${DB_NAME}" < "${INSTALL_DIR}/database/schema.sql"
success "Schemat bazy danych zaimportowany."

# ── Migracje v2–v28 ──
info "Uruchamianie migracji bazy danych…"
MIGRATION_OK=0
MIGRATION_FAIL=0
for MIG_FILE in $(ls "${INSTALL_DIR}/database/migration_v"*.sql | sort -V); do
    MIG_NAME="$(basename "${MIG_FILE}")"
    if mysql_cmd "${DB_NAME}" < "${MIG_FILE}" 2>/dev/null; then
        success "Migracja: ${MIG_NAME}"
        (( MIGRATION_OK++ )) || true
    else
        warn "Migracja z ostrzeżeniem (może być OK przy reinstalacji): ${MIG_NAME}"
        (( MIGRATION_FAIL++ )) || true
    fi
done
info "Migracje: ${MIGRATION_OK} OK, ${MIGRATION_FAIL} z ostrzeżeniami."

# Aktualizacja nazwy klubu i e-maila w settings
info "Aktualizacja ustawień klubu w bazie danych…"
mysql_cmd "${DB_NAME}" <<SQL
UPDATE settings SET value = '${APP_CLUB_NAME}'  WHERE \`key\` = 'club_name';
UPDATE settings SET value = '${APP_CLUB_EMAIL}' WHERE \`key\` = 'club_email';
UPDATE settings SET value = '${APP_CLUB_PHONE}' WHERE \`key\` = 'club_phone';
UPDATE clubs   SET name   = '${APP_CLUB_NAME}'  WHERE id = 1;
SQL
success "Ustawienia klubu zapisane."

# Generuj hash hasła administratora przez PHP
info "Generowanie konta administratora…"
ADMIN_HASH=$($PHP_CMD -r "echo password_hash('${ADMIN_PASS}', PASSWORD_BCRYPT, ['cost' => 12]);")

mysql_cmd "${DB_NAME}" <<SQL
-- Usuń domyślnego admina z seeda, jeśli istnieje
DELETE FROM users WHERE username = 'admin';

-- Utwórz superadmina multi-tenant
INSERT INTO users (username, email, password, role, full_name, is_active, is_super_admin)
VALUES ('${ADMIN_USERNAME}', '${ADMIN_EMAIL}', '${ADMIN_HASH}', 'admin', '${ADMIN_FULLNAME}', 1, 1);

-- Przypisz do klubu domyślnego (id=1) z rolą zarzad
INSERT IGNORE INTO user_clubs (user_id, club_id, role, is_active)
VALUES (LAST_INSERT_ID(), 1, 'zarzad', 1);
SQL
success "Konto superadmina '${ADMIN_USERNAME}' utworzone (is_super_admin=1)."

echo

# =============================================================================
# 7. Uprawnienia plików i katalogów
# =============================================================================
separator
echo -e "  ${BOLD}KROK 6 — Uprawnienia plików i katalogów${NC}"
separator

# Katalogi do zapisu
info "Tworzenie katalogów wymaganych przez aplikację…"
mkdir -p "${INSTALL_DIR}/logs"
mkdir -p "${INSTALL_DIR}/public/uploads"
mkdir -p "${INSTALL_DIR}/storage/medical"
mkdir -p "${INSTALL_DIR}/storage/photos"
mkdir -p "${INSTALL_DIR}/storage/backups"

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
chmod 775 "${INSTALL_DIR}/storage/medical"
chmod 775 "${INSTALL_DIR}/storage/photos"
chmod 775 "${INSTALL_DIR}/storage/backups"
success "logs/, public/uploads/, storage/*: 775 (rwxrwxr-x)"

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
    if ask_yn "Ustawić właściciela katalogów zapisu na ${WWW_USER}?" "y"; then
        chown -R "${WWW_USER}:${WWW_USER}" \
            "${INSTALL_DIR}/logs" \
            "${INSTALL_DIR}/public/uploads" \
            "${INSTALL_DIR}/storage" 2>/dev/null || \
            warn "Brak uprawnień do zmiany właściciela (uruchom jako root)."
        success "Właściciel logs/, uploads/, storage/ → ${WWW_USER}"
    fi

    # Pliki konfiguracyjne — właściciel root, grupa www-user, chmod 640
    # Dzięki temu serwer WWW może odczytać hasła DB, a inni użytkownicy nie
    chown "root:${WWW_USER}" "${INSTALL_DIR}/config/database.local.php" \
                              "${INSTALL_DIR}/config/app.local.php" 2>/dev/null || true
    chmod 640 "${INSTALL_DIR}/config/database.local.php" \
              "${INSTALL_DIR}/config/app.local.php"
    success "config/*.local.php: 640 (root:${WWW_USER}) — tylko serwer WWW może odczytać"
else
    warn "Nie wykryto użytkownika serwera WWW (www-data/apache/nginx)."
    warn "Ustaw ręcznie:"
    warn "  chown root:<www-user> ${INSTALL_DIR}/config/*.local.php"
    warn "  chmod 640 ${INSTALL_DIR}/config/*.local.php"
    warn "  chown -R <www-user>: ${INSTALL_DIR}/logs ${INSTALL_DIR}/public/uploads"
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
# 8b. Weryfikacja PHP serwera WWW (Plesk)
# =============================================================================
separator
echo -e "  ${BOLD}KROK 6b — Konfiguracja PHP serwera WWW${NC}"
separator

# Zapytaj o domenę żeby móc skonstruować link
SITE_DOMAIN=$(ask "Domena serwisu (np. wksfg.pl)" "$(hostname -f 2>/dev/null || echo 'localhost')")

# Wygeneruj tymczasowy plik testowy w public/
PHP_TEST_FILE="${INSTALL_DIR}/public/_phpcheck_$$.php"
cat > "$PHP_TEST_FILE" <<'PHPTEST'
<?php header('Content-Type: text/plain'); echo PHP_VERSION;
PHPTEST
chmod 644 "$PHP_TEST_FILE"
_test_filename="$(basename "$PHP_TEST_FILE")"

# Spróbuj pobrać wersję PHP przez curl
WEB_PHP_VER=""
if command -v curl &>/dev/null; then
    for _proto in https http; do
        for _attempt in 1 2; do
            _response=$(curl -sk --max-time 8 "${_proto}://${SITE_DOMAIN}/${_test_filename}" 2>/dev/null || true)
            if [[ "$_response" =~ ^[0-9]+\.[0-9]+ ]]; then
                WEB_PHP_VER="$_response"
                break 2
            fi
            sleep 1
        done
    done
fi
rm -f "$PHP_TEST_FILE"

if [[ -n "$WEB_PHP_VER" ]]; then
    WEB_MAJOR=$(echo "$WEB_PHP_VER" | cut -d. -f1)
    WEB_MINOR=$(echo "$WEB_PHP_VER" | cut -d. -f2)
    if (( WEB_MAJOR > 8 )) || { (( WEB_MAJOR == 8 )) && (( WEB_MINOR >= 1 )); }; then
        success "PHP serwera WWW: ${WEB_PHP_VER} ✓  (zgodny z instalacją: ${PHP_VER})"
    else
        echo
        error "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        error " PHP SERWERA WWW: ${WEB_PHP_VER} — PRZYCZYNA BŁĘDU 500!"
        error "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
        echo
        warn  "Instalacja używała ${PHP_VER} (${PHP_CMD})"
        warn  "Serwer WWW używa ${WEB_PHP_VER}"
        echo
        echo -e "  ${BOLD}Jak naprawić w Plesk:${NC}"
        echo -e "  1. Zaloguj się do Plesk"
        echo -e "  2. Strony → ${SITE_DOMAIN} → PHP"
        echo -e "  3. Zmień wersję PHP na: ${CYAN}${PHP_VER}${NC}"
        echo -e "  4. Kliknij OK → odśwież stronę"
        echo
    fi
else
    warn "Nie udało się sprawdzić PHP serwera WWW przez curl."
    warn "Sprawdź ręcznie lub poczekaj na diagnose.php po konfiguracji Plesk."
    echo
    echo -e "  ${BOLD}Wymagana konfiguracja Plesk:${NC}"
    echo -e "  • Strony → ${SITE_DOMAIN} → PHP → ustaw: ${CYAN}${PHP_VER}${NC}"
    echo -e "  • Strony → ${SITE_DOMAIN} → Ustawienia hostingu → Document Root:"
    echo -e "    ${CYAN}${INSTALL_DIR#/var/www/vhosts/*/}${NC}"
    echo -e "    (ścieżka względna od httpdocs, np.: shootingclubmng/public)"
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
EXPECTED_TABLES="users members member_age_categories member_disciplines member_medical_exams licenses payments payment_types disciplines competitions competition_entries competition_results competition_groups settings activity_log clubs user_clubs club_settings club_customization club_subscriptions sms_queue email_templates"
for tbl in $EXPECTED_TABLES; do
    COUNT=$(mysql_cmd "${DB_NAME}" -se "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA='${DB_NAME}' AND TABLE_NAME='${tbl}';" 2>/dev/null)
    if [[ "$COUNT" == "1" ]]; then
        success "Tabela: ${tbl}"
    else
        error "Brak tabeli: ${tbl}"
        ((ERRORS++))
    fi
done

# Sprawdź konto admina (is_super_admin=1 po migracji v25)
info "Sprawdzanie konta administratora…"
ADMIN_COUNT=$(mysql_cmd "${DB_NAME}" -se "SELECT COUNT(*) FROM users WHERE username='${ADMIN_USERNAME}' AND is_super_admin=1;" 2>/dev/null)
if [[ "$ADMIN_COUNT" == "1" ]]; then
    success "Konto superadmina: ${ADMIN_USERNAME}"
else
    error "Brak konta superadmina!"
    ((ERRORS++))
fi

# Sprawdź klub domyślny
CLUB_COUNT=$(mysql_cmd "${DB_NAME}" -se "SELECT COUNT(*) FROM clubs WHERE id=1;" 2>/dev/null || echo "0")
if [[ "$CLUB_COUNT" == "1" ]]; then
    success "Klub domyślny (id=1): OK"
else
    error "Brak klubu domyślnego (id=1)!"
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
echo -e "  ${BOLD}Konfiguracja serwera WWW (jeśli nie ustawiono wcześniej):${NC}"
echo -e "    Document Root → ${CYAN}${INSTALL_DIR}/public/${NC}"
echo
echo -e "  ${BOLD}Następne kroki:${NC}"
echo -e "    1. Sprawdź Document Root w Plesk:"
echo -e "       Strony → ${SITE_DOMAIN:-DOMENA} → Ustawienia hostingu"
echo -e "       Ustaw: ${CYAN}${INSTALL_DIR}/public${NC}"
echo -e "    2. PHP 8.3 dla domeny (Plesk → PHP → 8.3)"
echo -e "    3. Zaloguj się: ${CYAN}https://${SITE_DOMAIN:-DOMENA}/auth/login${NC}"
echo -e "       Login: ${CYAN}${ADMIN_USERNAME}${NC} | Hasło: (podane podczas instalacji)"
echo -e "    4. Panel superadmina: ${CYAN}https://${SITE_DOMAIN:-DOMENA}/admin/dashboard${NC}"
echo
echo -e "  ${BOLD}Crony (dodaj w Plesk → Zaplanowane zadania):${NC}"
echo -e "    ${CYAN}* * * * *   ${PHP_CMD} ${INSTALL_DIR}/cli/process_queue.php${NC}"
echo -e "    ${CYAN}0 6 * * *   ${PHP_CMD} ${INSTALL_DIR}/cli/queue_reminders.php${NC}"
echo -e "    ${CYAN}*/5 * * * * ${PHP_CMD} ${INSTALL_DIR}/cli/process_sms_queue.php${NC}"
echo -e "    ${CYAN}0 * * * *   ${PHP_CMD} ${INSTALL_DIR}/cli/cleanup_demos.php${NC}"
echo
if [[ "$APP_DEBUG" == "true" ]]; then
    warn "Tryb DEBUG jest włączony — błędy PHP wyświetlane w przeglądarce."
    warn "Wyłącz przed wdrożeniem: config/app.local.php → 'debug' => false"
fi
separator
echo
