#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  Kore ERP — macOS Uninstaller
#  Removes: application files, database, Apache vhost, Desktop shortcut
#  Optionally removes: PHP, MySQL, Apache (Homebrew packages)
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; GRAY='\033[0;37m'; BOLD='\033[1m'; NC='\033[0m'

ok()   { echo -e "  ${GREEN}✓${NC}  $*"; }
warn() { echo -e "  ${YELLOW}⚠${NC}  $*"; }
info() { echo -e "  ${GRAY}→${NC}  $*"; }

ask_yn() {
    local question="$1" default="${2:-n}"
    local hint; [[ "$default" == "y" ]] && hint="Y/n" || hint="y/N"
    printf "  ${BOLD}${question}${NC} ${GRAY}[${hint}]${NC}: "
    local ans; read -r ans
    [[ -z "$ans" ]] && ans="$default"
    [[ "$ans" =~ ^[Yy] ]]
}

ARCH="$(uname -m)"
BREW_PREFIX=$([[ "$ARCH" == "arm64" ]] && echo "/opt/homebrew" || echo "/usr/local")
BREW="$BREW_PREFIX/bin/brew"

clear
echo
echo -e "  ${RED}${BOLD}╔══════════════════════════════════════════════════════════════╗"
echo -e "  ║                                                              ║"
echo -e "  ║   Kore ERP — macOS Uninstaller                             ║"
echo -e "  ║                                                              ║"
echo -e "  ╚══════════════════════════════════════════════════════════════╝${NC}"
echo
echo -e "  ${YELLOW}This will remove Kore ERP from your Mac.${NC}"
echo

# ── Try to load saved install info ───────────────────────────────────────────
POSSIBLE_DIRS=(
    "$HOME/Sites/kore-erp"
    "$HOME/kore-erp"
    "/usr/local/var/kore-erp"
)

APP_DIR=""
for d in "${POSSIBLE_DIRS[@]}"; do
    if [[ -f "$d/.kore-install-info" ]]; then
        APP_DIR="$d"
        source "$d/.kore-install-info" 2>/dev/null || true
        break
    fi
done

if [[ -z "$APP_DIR" ]]; then
    printf "  ${BOLD}Where is Kore ERP installed?${NC} [$HOME/Sites/kore-erp]: "
    read -r APP_DIR
    [[ -z "$APP_DIR" ]] && APP_DIR="$HOME/Sites/kore-erp"
fi

echo
echo -e "  ${GRAY}App directory : $APP_DIR${NC}"
echo -e "  ${GRAY}Database      : ${DB_NAME:-kore_erp}${NC}"
echo

if ! ask_yn "Are you sure you want to uninstall Kore ERP?" "n"; then
    echo "  Uninstall cancelled."; exit 0
fi

echo

# ── Remove Apache VirtualHost ─────────────────────────────────────────────────
VCONF="${VHOST_CONF:-$BREW_PREFIX/etc/httpd/extra/kore-erp.conf}"
if [[ -f "$VCONF" ]]; then
    rm -f "$VCONF"
    ok "Apache VirtualHost config removed."
    # Restart Apache
    "$BREW_PREFIX/bin/apachectl" graceful 2>/dev/null || true
    ok "Apache reloaded."
else
    info "Apache VirtualHost not found at $VCONF — skipped."
fi

# ── Drop database ─────────────────────────────────────────────────────────────
DB="${DB_NAME:-kore_erp}"
DB_U="${DB_USER:-kore_user}"

if [[ -x "$BREW_PREFIX/bin/mysql" ]] || [[ -x "$BREW_PREFIX/opt/mysql@8.0/bin/mysql" ]]; then
    MYSQL="$BREW_PREFIX/bin/mysql"
    [[ -x "$BREW_PREFIX/opt/mysql@8.0/bin/mysql" ]] && MYSQL="$BREW_PREFIX/opt/mysql@8.0/bin/mysql"

    if ask_yn "Drop database '$DB' and user '$DB_U'?" "n"; then
        "$MYSQL" -u root 2>/dev/null << SQL || warn "Could not drop database (may need MySQL root access)."
DROP DATABASE IF EXISTS \`${DB}\`;
DROP USER IF EXISTS '${DB_U}'@'localhost';
DROP USER IF EXISTS '${DB_U}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
        ok "Database '$DB' and user '$DB_U' removed."
    else
        info "Database retained."
    fi
else
    info "MySQL client not found — database not removed."
fi

# ── Remove application files ──────────────────────────────────────────────────
if [[ -d "$APP_DIR" ]]; then
    if ask_yn "Delete application directory '$APP_DIR'?" "y"; then
        rm -rf "$APP_DIR"
        ok "Application directory removed."
    else
        info "Application files retained at $APP_DIR"
    fi
fi

# ── Remove desktop shortcut ───────────────────────────────────────────────────
SHORTCUT="$HOME/Desktop/Kore ERP.command"
if [[ -f "$SHORTCUT" ]]; then
    rm -f "$SHORTCUT"
    ok "Desktop shortcut removed."
fi

# ── Optionally remove Homebrew packages ───────────────────────────────────────
echo
if ask_yn "Remove Homebrew packages installed by Kore ERP? (PHP, MySQL, Apache, Composer)" "n"; then
    warn "Stopping and removing services..."
    "$BREW" services stop httpd    2>/dev/null || true
    "$BREW" services stop php@8.3  2>/dev/null || true
    "$BREW" services stop php      2>/dev/null || true
    "$BREW" services stop mysql@8.0 2>/dev/null || true
    "$BREW" services stop mysql    2>/dev/null || true

    "$BREW" uninstall --force httpd     2>/dev/null && ok "  httpd removed."   || true
    "$BREW" uninstall --force php@8.3  2>/dev/null && ok "  php@8.3 removed." || true
    "$BREW" uninstall --force mysql@8.0 2>/dev/null && ok "  mysql@8.0 removed." || true
    "$BREW" uninstall --force composer 2>/dev/null && ok "  composer removed." || true
    warn "Homebrew packages removed. Homebrew itself was NOT removed."
else
    info "Homebrew packages retained."
fi

echo
echo -e "  ${GREEN}${BOLD}Kore ERP has been uninstalled.${NC}"
echo
echo -e "  ${GRAY}Press Enter to close this window...${NC}"
read -r
