#!/usr/bin/env bash
# =============================================================================
#  fix-permissions.sh — naprawia uprawnienia po git pull lub reinstalacji
#  Użycie: bash fix-permissions.sh
#          PHP_FPM_USER=myuser bash fix-permissions.sh  (ręczne nadpisanie)
# =============================================================================
set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'; CYAN='\033[0;36m'; NC='\033[0m'
info()    { echo -e "${CYAN}[INFO]${NC}  $*"; }
success() { echo -e "${GREEN}[OK]${NC}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${NC}  $*"; }
die()     { echo -e "${RED}[BŁĄD]${NC}  $*" >&2; exit 1; }

INSTALL_DIR="$(cd "$(dirname "$0")" && pwd)"
DOMAIN_NAME="$(basename "$(dirname "${INSTALL_DIR}")")"

echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "  Naprawianie uprawnień: ${INSTALL_DIR}"
echo -e "  Domena: ${DOMAIN_NAME}"
echo -e "${CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"

# ── Wykryj użytkownika PHP-FPM ────────────────────────────────────────────────
WWW_USER="${PHP_FPM_USER:-}"

if [[ -z "$WWW_USER" ]]; then
    # Metoda 1 — Plesk per-domain
    PLESK_FPM_CONF="/var/www/vhosts/system/${DOMAIN_NAME}/etc/php-fpm.conf"
    if [[ -f "$PLESK_FPM_CONF" ]]; then
        _u=$(grep -E "^user\s*=" "$PLESK_FPM_CONF" 2>/dev/null | awk -F'=' '{print $2}' | tr -d ' ')
        [[ -n "$_u" ]] && id "$_u" &>/dev/null && WWW_USER="$_u"
    fi
fi

if [[ -z "$WWW_USER" ]]; then
    # Metoda 2 — Plesk shared pools
    for _phpver in 8.3 8.2 8.1 8.0 7.4; do
        _pool_dir="/opt/plesk/php/${_phpver}/etc/php-fpm.d"
        [[ -d "$_pool_dir" ]] || continue
        _conf=$(grep -rl "${DOMAIN_NAME}" "$_pool_dir" 2>/dev/null | head -1)
        if [[ -n "$_conf" ]]; then
            _u=$(grep -E "^user\s*=" "$_conf" 2>/dev/null | awk -F'=' '{print $2}' | tr -d ' ')
            if [[ -n "$_u" ]] && id "$_u" &>/dev/null; then
                WWW_USER="$_u"; break
            fi
        fi
    done
fi

if [[ -z "$WWW_USER" ]]; then
    # Metoda 3 — klasyczny
    for candidate in www-data apache nginx http; do
        if id "$candidate" &>/dev/null; then
            WWW_USER="$candidate"; break
        fi
    done
fi

[[ -z "$WWW_USER" ]] && die "Nie wykryto użytkownika PHP-FPM. Podaj ręcznie: PHP_FPM_USER=... bash fix-permissions.sh"
info "Użytkownik PHP-FPM: ${WWW_USER}"

# ── Utwórz brakujące katalogi ─────────────────────────────────────────────────
mkdir -p "${INSTALL_DIR}/logs"
mkdir -p "${INSTALL_DIR}/public/uploads"
mkdir -p "${INSTALL_DIR}/storage/medical"
mkdir -p "${INSTALL_DIR}/storage/photos"
mkdir -p "${INSTALL_DIR}/storage/backups"

# ── Uprawnienia plików ────────────────────────────────────────────────────────
# Pliki PHP — 644
find "${INSTALL_DIR}" \
    -not -path "${INSTALL_DIR}/.git/*" \
    -not -path "${INSTALL_DIR}/logs/*" \
    -not -path "${INSTALL_DIR}/storage/*" \
    -not -path "${INSTALL_DIR}/public/uploads/*" \
    -type f -exec chmod 644 {} \;

# Katalogi — 755
find "${INSTALL_DIR}" \
    -not -path "${INSTALL_DIR}/.git/*" \
    -type d -exec chmod 755 {} \;

success "Pliki: 644 | Katalogi: 755"

# Katalogi zapisu — 775, właściciel FPM user
chown -R "${WWW_USER}:${WWW_USER}" \
    "${INSTALL_DIR}/logs" \
    "${INSTALL_DIR}/public/uploads" \
    "${INSTALL_DIR}/storage"
chmod -R 775 \
    "${INSTALL_DIR}/logs" \
    "${INSTALL_DIR}/public/uploads" \
    "${INSTALL_DIR}/storage"
success "logs/, uploads/, storage/: 775 → ${WWW_USER}"

# Pliki konfiguracyjne — root:FPM_USER 640
for cfg in config/database.local.php config/app.local.php; do
    if [[ -f "${INSTALL_DIR}/${cfg}" ]]; then
        chown "root:${WWW_USER}" "${INSTALL_DIR}/${cfg}"
        chmod 640 "${INSTALL_DIR}/${cfg}"
        success "${cfg}: 640 (root:${WWW_USER})"
    fi
done

echo
success "Uprawnienia naprawione. Możesz odświeżyć stronę."
