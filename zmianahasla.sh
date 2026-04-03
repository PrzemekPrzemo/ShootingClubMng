#!/usr/bin/env bash
# =============================================================================
# Klub Strzelecki — Zmiana hasła użytkownika systemu
# Użycie: bash zmianahasla.sh
# =============================================================================
set -euo pipefail

# ── Kolory ───────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

info()    { echo -e "${CYAN}[INFO]${RESET}  $*"; }
success() { echo -e "${GREEN}[OK]${RESET}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${RESET}  $*"; }
error()   { echo -e "${RED}[BŁĄD]${RESET}  $*" >&2; }
die()     { error "$*"; exit 1; }

echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo -e "${BOLD}  Klub Strzelecki — Zmiana hasła użytkownika      ${RESET}"
echo -e "${BOLD}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${RESET}"
echo

# ── Wykryj katalog instalacji ─────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DB_CONFIG="${SCRIPT_DIR}/config/database.local.php"

if [[ ! -f "$DB_CONFIG" ]]; then
    die "Nie znaleziono pliku konfiguracyjnego: ${DB_CONFIG}
Upewnij się, że skrypt uruchamiasz z katalogu instalacji lub najpierw uruchom install.sh"
fi

success "Znaleziono konfigurację bazy danych: ${DB_CONFIG}"

# ── Wykryj PHP ────────────────────────────────────────────────────────────────
PHP_CMD=""
for candidate in \
    /opt/plesk/php/8.4/bin/php \
    /opt/plesk/php/8.3/bin/php \
    /opt/plesk/php/8.2/bin/php \
    /opt/plesk/php/8.1/bin/php \
    /opt/remi/php84/root/usr/bin/php \
    /opt/remi/php83/root/usr/bin/php \
    /opt/remi/php82/root/usr/bin/php \
    /opt/remi/php81/root/usr/bin/php \
    php8.4 php8.3 php8.2 php8.1 php8 php; do
    if command -v "$candidate" &>/dev/null; then
        VER=$("$candidate" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || true)
        MAJOR=${VER%%.*}
        if [[ "$MAJOR" -ge 8 ]]; then
            PHP_CMD="$candidate"
            break
        fi
    fi
done

if [[ -z "$PHP_CMD" ]]; then
    die "Nie znaleziono PHP >= 8.x. Ustaw zmienną: PHP_CMD=/ścieżka/do/php bash zmianahasla.sh"
fi

success "PHP: ${PHP_CMD} ($(${PHP_CMD} -r 'echo PHP_VERSION;'))"
echo

# ── Pobierz listę użytkowników przez PHP ──────────────────────────────────────
info "Pobieranie listy użytkowników z bazy danych..."

USERS_JSON=$("$PHP_CMD" -r "
\$c = require '$DB_CONFIG';
try {
    \$pdo = new PDO(
        'mysql:host='.\$c['host'].';port='.(\$c['port'] ?? 3306).';dbname='.\$c['dbname'].';charset=utf8mb4',
        \$c['username'], \$c['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    \$rows = \$pdo->query('SELECT id, username, role, is_active FROM users ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(\$rows);
} catch (PDOException \$e) {
    fwrite(STDERR, 'DB ERROR: ' . \$e->getMessage() . PHP_EOL);
    exit(1);
}
" 2>&1) || die "Błąd połączenia z bazą danych: ${USERS_JSON}"

# Wyświetl użytkowników
echo -e "${BOLD}Użytkownicy systemu:${RESET}"
echo -e "  ${BOLD}$(printf '%-4s %-20s %-12s %s' 'ID' 'Login' 'Rola' 'Status')${RESET}"
echo    "  ──────────────────────────────────────────"

"$PHP_CMD" -r "
\$rows = json_decode('$USERS_JSON', true);
foreach (\$rows as \$r) {
    \$status = \$r['is_active'] ? 'aktywny' : 'nieaktywny';
    printf('  %-4s %-20s %-12s %s' . PHP_EOL, \$r['id'], \$r['username'], \$r['role'], \$status);
}
"

echo

# ── Pytania ───────────────────────────────────────────────────────────────────
while true; do
    read -rp "$(echo -e "${CYAN}Podaj login użytkownika (lub ID):${RESET} ")" INPUT_USER
    [[ -n "$INPUT_USER" ]] && break
    warn "Login nie może być pusty."
done

# Sprawdź czy użytkownik istnieje
USER_EXISTS=$("$PHP_CMD" -r "
\$c = require '$DB_CONFIG';
\$pdo = new PDO(
    'mysql:host='.\$c['host'].';port='.(\$c['port'] ?? 3306).';dbname='.\$c['dbname'].';charset=utf8mb4',
    \$c['username'], \$c['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
\$field = is_numeric('$INPUT_USER') ? 'id' : 'username';
\$stmt = \$pdo->prepare(\"SELECT COUNT(*) FROM users WHERE {\$field} = ?\");
\$stmt->execute(['$INPUT_USER']);
echo \$stmt->fetchColumn();
" 2>/dev/null || echo "0")

if [[ "$USER_EXISTS" == "0" ]]; then
    die "Użytkownik '${INPUT_USER}' nie istnieje w bazie danych."
fi

success "Znaleziono użytkownika: ${INPUT_USER}"
echo

# ── Nowe hasło ────────────────────────────────────────────────────────────────
while true; do
    read -rsp "$(echo -e "${CYAN}Nowe hasło:${RESET} ")" NEW_PASS
    echo
    if [[ ${#NEW_PASS} -lt 8 ]]; then
        warn "Hasło musi mieć co najmniej 8 znaków. Spróbuj ponownie."
        continue
    fi
    read -rsp "$(echo -e "${CYAN}Powtórz hasło:${RESET} ")" NEW_PASS2
    echo
    if [[ "$NEW_PASS" != "$NEW_PASS2" ]]; then
        warn "Hasła nie są identyczne. Spróbuj ponownie."
        continue
    fi
    break
done

# ── Zaktualizuj hasło ─────────────────────────────────────────────────────────
info "Aktualizowanie hasła..."

RESULT=$("$PHP_CMD" -r "
\$c = require '$DB_CONFIG';
\$pdo = new PDO(
    'mysql:host='.\$c['host'].';port='.(\$c['port'] ?? 3306).';dbname='.\$c['dbname'].';charset=utf8mb4',
    \$c['username'], \$c['password'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
\$hash = password_hash('$(printf '%s' "$NEW_PASS" | sed "s/'/'\\\\''/g")', PASSWORD_BCRYPT, ['cost' => 12]);
\$field = is_numeric('$INPUT_USER') ? 'id' : 'username';
\$stmt = \$pdo->prepare(\"UPDATE users SET password = ? WHERE {\$field} = ?\");
\$stmt->execute([\$hash, '$INPUT_USER']);
echo \$stmt->rowCount();
" 2>&1) || die "Błąd podczas aktualizacji hasła: ${RESULT}"

if [[ "$RESULT" -ge 1 ]]; then
    echo
    success "Hasło zostało zmienione pomyślnie dla użytkownika: ${BOLD}${INPUT_USER}${RESET}"
    echo
else
    die "Nie zaktualizowano żadnego rekordu. Sprawdź login/ID."
fi
