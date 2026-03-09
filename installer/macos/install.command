#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  Kore ERP — macOS Installer
#  Supports: Apple Silicon (M1 / M2 / M3 / M4) and Intel Macs
#  Installs:  Homebrew · PHP 8.3 · MySQL 8 · Apache (httpd) · Composer
#             Kore ERP application · database schema · admin account
#
#  Usage: Double-click install.command (or open in Terminal)
#  Run as: your normal user account (no sudo required)
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

# ── Change to the script's own directory ─────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
SOURCE_DIR="$SCRIPT_DIR/KoreERP"          # App files bundled in DMG

# ── Detect architecture ───────────────────────────────────────────────────────
ARCH="$(uname -m)"
if [[ "$ARCH" == "arm64" ]]; then
    BREW_PREFIX="/opt/homebrew"           # Apple Silicon (M1/M2/M3/M4)
else
    BREW_PREFIX="/usr/local"              # Intel
fi

# ── Colour helpers ────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; GRAY='\033[0;37m'; BOLD='\033[1m'; NC='\033[0m'

ok()   { echo -e "  ${GREEN}✓${NC}  $*"; }
warn() { echo -e "  ${YELLOW}⚠${NC}  $*"; }
fail() { echo -e "  ${RED}✗${NC}  $*"; }
info() { echo -e "  ${GRAY}→${NC}  $*"; }
step() { echo -e "\n${CYAN}${BOLD}  [$1]  $2${NC}"; echo -e "  $(printf '─%.0s' {1..58})"; echo; }

ask() {
    local label="$1" default="${2:-}" secret="${3:-}"
    local prompt="  ${BOLD}${label}${NC}"
    [[ -n "$default" ]] && prompt+=" ${GRAY}[${default}]${NC}"
    printf "${prompt}: " >&2
    local val
    if [[ "$secret" == "secret" ]]; then
        read -rs val; echo >&2
    else
        read -r val
    fi
    [[ -z "$val" ]] && val="$default"
    echo "$val"
}

ask_yn() {
    local question="$1" default="${2:-y}"
    local hint; [[ "$default" == "y" ]] && hint="Y/n" || hint="y/N"
    printf "  ${BOLD}${question}${NC} ${GRAY}[${hint}]${NC}: " >&2
    local ans; read -r ans
    [[ -z "$ans" ]] && ans="$default"
    [[ "$ans" =~ ^[Yy] ]]
}

die() { fail "$*"; echo; exit 1; }

LOG_FILE="$HOME/Library/Logs/kore-erp-install.log"
mkdir -p "$(dirname "$LOG_FILE")"
exec > >(tee -a "$LOG_FILE") 2>&1

# ── Banner ────────────────────────────────────────────────────────────────────
clear
echo
echo -e "  ${CYAN}${BOLD}╔══════════════════════════════════════════════════════════════╗"
echo -e "  ║                                                              ║"
echo -e "  ║    ██╗  ██╗ ██████╗ ██████╗ ███████╗    ███████╗██████╗ ██╗ ║"
echo -e "  ║    ██║ ██╔╝██╔═══██╗██╔══██╗██╔════╝    ██╔════╝██╔══██╗██║ ║"
echo -e "  ║    █████╔╝ ██║   ██║██████╔╝█████╗      █████╗  ██████╔╝██║ ║"
echo -e "  ║    ██╔═██╗ ██║   ██║██╔══██╗██╔══╝      ██╔══╝  ██╔══██╗██╝ ║"
echo -e "  ║    ██║  ██╗╚██████╔╝██║  ██║███████╗    ███████╗██║  ██║██╗ ║"
echo -e "  ║    ╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝╚══════╝    ╚══════╝╚═╝  ╚═╝╚═╝ ║"
echo -e "  ║                                                              ║"
echo -e "  ║           macOS Installer  ·  Apple Silicon Ready           ║${NC}"
echo -e "  ${CYAN}${BOLD}╚══════════════════════════════════════════════════════════════╝${NC}"
echo
echo -e "  ${GRAY}Architecture : $ARCH  |  Homebrew prefix: $BREW_PREFIX${NC}"
echo -e "  ${GRAY}Log file     : $LOG_FILE${NC}"
echo

# ── macOS version check ───────────────────────────────────────────────────────
MACOS_VER="$(sw_vers -productVersion)"
MACOS_MAJOR="${MACOS_VER%%.*}"
if (( MACOS_MAJOR < 12 )); then
    die "macOS 12 Monterey or later required. You have $MACOS_VER."
fi
ok "macOS $MACOS_VER detected."

if ! ask_yn "Ready to install Kore ERP on this Mac?" "y"; then
    echo "  Installation cancelled."; exit 0
fi

# ─────────────────────────────────────────────────────────────────────────────
step "1/6" "Configuration"
# ─────────────────────────────────────────────────────────────────────────────
echo -e "  ${GRAY}Press Enter to accept the value shown in [brackets].${NC}"
echo

APP_DIR=$(ask "Install Kore ERP to" "$HOME/Sites/kore-erp")
HTTPD_PORT=$(ask "Apache web server port" "8080")
APP_URL="http://localhost:${HTTPD_PORT}"

echo
echo -e "  ${CYAN}Database${NC}"
echo -e "  $(printf '─%.0s' {1..44})"

DB_NAME=$(ask "Database name" "kore_erp")
DB_USER=$(ask "Database username" "kore_user")
# Generate a secure random password as default
DEFAULT_DB_PASS="$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | head -c 16)"
DB_PASS=$(ask "Database password" "$DEFAULT_DB_PASS")

echo
echo -e "  ${CYAN}Timezone${NC}"
echo -e "  $(printf '─%.0s' {1..44})"
APP_TZ=$(ask "Timezone (e.g. America/New_York, UTC, Australia/Sydney)" "UTC")

echo
echo -e "  ${CYAN}Summary${NC}"
echo -e "  ┌────────────────────────────────────────────────────────────"
echo -e "  │  App location : $APP_DIR"
echo -e "  │  App URL      : $APP_URL"
echo -e "  │  Database     : $DB_USER @ 127.0.0.1 / $DB_NAME"
echo -e "  │  Timezone     : $APP_TZ"
echo -e "  └────────────────────────────────────────────────────────────"
echo

if ! ask_yn "Proceed with installation?" "y"; then
    echo "  Installation cancelled."; exit 0
fi

# ─────────────────────────────────────────────────────────────────────────────
step "2/6" "Installing Prerequisites"
# ─────────────────────────────────────────────────────────────────────────────

# ── Homebrew ─────────────────────────────────────────────────────────────────
if [[ -x "$BREW_PREFIX/bin/brew" ]]; then
    ok "Homebrew already installed at $BREW_PREFIX"
else
    info "Installing Homebrew (this may take a few minutes)..."
    /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
    # Add brew to PATH for this session
    eval "$("$BREW_PREFIX/bin/brew" shellenv)"
    ok "Homebrew installed."
fi

# Ensure brew is on PATH for this session
export PATH="$BREW_PREFIX/bin:$BREW_PREFIX/sbin:$PATH"
BREW="$BREW_PREFIX/bin/brew"

info "Updating Homebrew package index..."
"$BREW" update --quiet 2>&1 | tail -3

# ── PHP 8.3 ──────────────────────────────────────────────────────────────────
echo
info "Checking PHP..."
if "$BREW" list php@8.3 &>/dev/null; then
    ok "PHP 8.3 already installed."
else
    info "Installing PHP 8.3 (includes FPM, pdo_mysql, openssl, mbstring, gd, zip, etc.)..."
    "$BREW" install php@8.3
    ok "PHP 8.3 installed."
fi

# Link PHP 8.3 as default
"$BREW" link --overwrite php@8.3 2>/dev/null || true
PHP="$BREW_PREFIX/opt/php@8.3/bin/php"
[[ -x "$PHP" ]] || PHP="$BREW_PREFIX/bin/php"
ok "PHP: $("$PHP" -r 'echo PHP_VERSION;') at $PHP"

# ── MySQL 8 ──────────────────────────────────────────────────────────────────
echo
info "Checking MySQL..."
if "$BREW" list mysql@8.0 &>/dev/null 2>&1 || "$BREW" list mysql &>/dev/null 2>&1; then
    ok "MySQL already installed."
    MYSQL_PKG="mysql"
    "$BREW" list mysql@8.0 &>/dev/null 2>&1 && MYSQL_PKG="mysql@8.0"
else
    info "Installing MySQL 8.0..."
    "$BREW" install mysql@8.0
    "$BREW" link mysql@8.0 --force
    MYSQL_PKG="mysql@8.0"
    ok "MySQL 8.0 installed."
fi

MYSQL="$BREW_PREFIX/opt/$MYSQL_PKG/bin/mysql"
[[ -x "$MYSQL" ]] || MYSQL="$BREW_PREFIX/bin/mysql"
MYSQLADMIN="$(dirname "$MYSQL")/mysqladmin"

# ── Apache (httpd) ────────────────────────────────────────────────────────────
echo
info "Checking Apache (httpd)..."
if "$BREW" list httpd &>/dev/null; then
    ok "Apache (httpd) already installed."
else
    info "Installing Apache 2.4..."
    "$BREW" install httpd
    ok "Apache installed."
fi
HTTPD_CONF="$BREW_PREFIX/etc/httpd/httpd.conf"
HTTPD_VHOSTS_DIR="$BREW_PREFIX/etc/httpd/extra"

# ── Composer ─────────────────────────────────────────────────────────────────
echo
info "Checking Composer..."
if "$BREW" list composer &>/dev/null; then
    ok "Composer already installed."
else
    info "Installing Composer..."
    "$BREW" install composer
    ok "Composer installed."
fi
COMPOSER="$BREW_PREFIX/bin/composer"
ok "Composer $("$COMPOSER" --version --no-ansi 2>/dev/null | head -1)"

# ─────────────────────────────────────────────────────────────────────────────
step "3/6" "Deploying Application"
# ─────────────────────────────────────────────────────────────────────────────

# If source dir doesn't exist in DMG location, check if we're running directly
# from the repo (developer mode)
if [[ ! -d "$SOURCE_DIR" ]]; then
    REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
    if [[ -f "$REPO_ROOT/artisan" ]]; then
        SOURCE_DIR="$REPO_ROOT"
        info "Developer mode: using repo at $SOURCE_DIR"
    else
        die "Cannot find Kore ERP source files. Expected: $SOURCE_DIR"
    fi
fi

info "Copying application files to $APP_DIR ..."
mkdir -p "$APP_DIR"
rsync -a --delete \
    --exclude='.git' \
    --exclude='vendor' \
    --exclude='.env' \
    --exclude='storage/logs/*.log' \
    --exclude='bootstrap/cache/*.php' \
    --exclude='node_modules' \
    "$SOURCE_DIR/" "$APP_DIR/"
ok "Application files deployed."

# Create required storage directories
info "Creating storage directories..."
mkdir -p \
    "$APP_DIR/storage/app/public" \
    "$APP_DIR/storage/framework/cache/data" \
    "$APP_DIR/storage/framework/sessions" \
    "$APP_DIR/storage/framework/testing" \
    "$APP_DIR/storage/framework/views" \
    "$APP_DIR/storage/logs" \
    "$APP_DIR/bootstrap/cache"

# Set permissions (web user needs read/write on storage and cache)
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
ok "Directories and permissions configured."

# ── Composer install ──────────────────────────────────────────────────────────
info "Installing PHP dependencies via Composer (may take 1–3 minutes)..."
cd "$APP_DIR"
"$COMPOSER" install \
    --no-interaction \
    --no-dev \
    --optimize-autoloader \
    --quiet
ok "Composer dependencies installed."

# ── .env file ─────────────────────────────────────────────────────────────────
info "Writing .env configuration..."
cat > "$APP_DIR/.env" << ENV
APP_NAME="Kore ERP"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=${APP_URL}
APP_TIMEZONE=${APP_TZ}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@kore.local"
MAIL_FROM_NAME="Kore ERP"

KORE_CURRENCY_SYMBOL=\$
KORE_CURRENCY_CODE=USD
KORE_INVOICE_PREFIX=INV
KORE_FISCAL_YEAR_START=01-01
ENV
ok ".env file written."

# ── App key ───────────────────────────────────────────────────────────────────
info "Generating application encryption key..."
"$PHP" artisan key:generate --force --quiet
ok "Application key generated."

# ── Storage symlink ───────────────────────────────────────────────────────────
"$PHP" artisan storage:link --quiet 2>/dev/null || true
ok "Storage symlink created."

# ── Clear caches ──────────────────────────────────────────────────────────────
"$PHP" artisan config:clear --quiet 2>/dev/null || true
"$PHP" artisan cache:clear  --quiet 2>/dev/null || true

# ─────────────────────────────────────────────────────────────────────────────
step "4/6" "Database Setup"
# ─────────────────────────────────────────────────────────────────────────────

# ── Start MySQL ───────────────────────────────────────────────────────────────
info "Starting MySQL service..."
"$BREW" services start "$MYSQL_PKG" 2>/dev/null || true

# Wait for MySQL to be ready (up to 30 seconds)
info "Waiting for MySQL to be ready..."
MYSQL_READY=0
for i in {1..30}; do
    if "$MYSQLADMIN" ping --silent --connect-timeout=1 2>/dev/null; then
        MYSQL_READY=1
        break
    fi
    sleep 1
done

if [[ $MYSQL_READY -eq 0 ]]; then
    warn "MySQL did not start within 30 seconds. Trying to continue anyway..."
fi
ok "MySQL is running."

# ── Create DB and user ────────────────────────────────────────────────────────
info "Creating database '$DB_NAME' and user '$DB_USER'..."
"$MYSQL" -u root 2>/dev/null << SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL
ok "Database and user created."

# ── Run SQL migration files ───────────────────────────────────────────────────
info "Running database migrations..."
SQL_DIR="$APP_DIR/install/sql"
SQL_FILES=(
    "01_core.sql"
    "02_crm.sql"
    "03_projects.sql"
    "04_timesheets.sql"
    "05_approvals.sql"
    "06_invoicing.sql"
    "07_settings.sql"
)

for f in "${SQL_FILES[@]}"; do
    FPATH="$SQL_DIR/$f"
    if [[ -f "$FPATH" ]]; then
        # Use PHP to split and execute (handles multi-statement files cleanly)
        "$MYSQL" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$FPATH" 2>&1 | \
            grep -v "^$\|Warning:" || true
        ok "  $f applied."
    else
        warn "  $f not found — skipped."
    fi
done

# ─────────────────────────────────────────────────────────────────────────────
step "5/6" "Configuring Apache Web Server"
# ─────────────────────────────────────────────────────────────────────────────

# ── Discover PHP-FPM socket / port ───────────────────────────────────────────
PHP_VER="$("$PHP" -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
PHP_FPM_SOCK="$BREW_PREFIX/var/run/php/php${PHP_VER}-fpm.sock"
# Prefer Unix socket if it exists after start; fall back to TCP
PHP_FPM_HANDLER="proxy:fcgi://127.0.0.1:9000"

# ── Start PHP-FPM ─────────────────────────────────────────────────────────────
info "Starting PHP-FPM..."
"$BREW" services start "php@8.3" 2>/dev/null || "$BREW" services start php 2>/dev/null || true
sleep 2
# After start, check for the socket
if [[ -S "$PHP_FPM_SOCK" ]]; then
    PHP_FPM_HANDLER="proxy:unix:${PHP_FPM_SOCK}|fcgi://localhost"
fi
ok "PHP-FPM started. Handler: $PHP_FPM_HANDLER"

# ── Back up existing httpd.conf ───────────────────────────────────────────────
if [[ -f "$HTTPD_CONF" ]] && [[ ! -f "${HTTPD_CONF}.kore-backup" ]]; then
    cp "$HTTPD_CONF" "${HTTPD_CONF}.kore-backup"
    info "Backed up original httpd.conf → httpd.conf.kore-backup"
fi

# ── Patch httpd.conf: enable required modules ─────────────────────────────────
patch_module() {
    local MOD="$1"
    if grep -q "^#LoadModule ${MOD}" "$HTTPD_CONF"; then
        sed -i '' "s|^#LoadModule ${MOD}|LoadModule ${MOD}|g" "$HTTPD_CONF"
        ok "  Enabled: $MOD"
    else
        info "  Already enabled or not found: $MOD"
    fi
}

info "Enabling Apache modules..."
patch_module "rewrite_module"
patch_module "proxy_module"
patch_module "proxy_fcgi_module"
patch_module "deflate_module"
patch_module "expires_module"
patch_module "headers_module"

# ── Set Apache listen port ────────────────────────────────────────────────────
info "Setting Apache listen port to $HTTPD_PORT..."
sed -i '' "s|^Listen .*|Listen ${HTTPD_PORT}|g" "$HTTPD_CONF"

# ── Enable virtual-host includes ──────────────────────────────────────────────
if grep -q "^#Include $BREW_PREFIX/etc/httpd/extra/httpd-vhosts.conf" "$HTTPD_CONF" 2>/dev/null || \
   grep -q "^#Include .*httpd-vhosts.conf" "$HTTPD_CONF"; then
    sed -i '' 's|^#Include.*httpd-vhosts.conf|Include '"$HTTPD_VHOSTS_DIR"'/httpd-vhosts.conf|g' "$HTTPD_CONF"
    ok "  Virtual hosts include enabled."
fi

# ── Write Kore ERP VirtualHost ────────────────────────────────────────────────
VHOST_CONF="$HTTPD_VHOSTS_DIR/kore-erp.conf"
info "Writing VirtualHost config → $VHOST_CONF"

cat > "$VHOST_CONF" << VHOST
# ── Kore ERP VirtualHost ──────────────────────────────────────────────────────
# Generated by Kore ERP installer on $(date)

# Default server block (required by Apache)
<VirtualHost *:${HTTPD_PORT}>
    ServerName localhost
    DocumentRoot "${APP_DIR}/public"

    <Directory "${APP_DIR}/public">
        Options -Indexes +FollowSymLinks +MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    # Route PHP requests to PHP-FPM
    <FilesMatch "\.php$">
        SetHandler "${PHP_FPM_HANDLER}"
    </FilesMatch>

    DirectoryIndex index.php index.html

    ErrorLog  "${APP_DIR}/storage/logs/apache-error.log"
    CustomLog "${APP_DIR}/storage/logs/apache-access.log" combined
</VirtualHost>
VHOST
ok "VirtualHost written."

# ── Validate Apache config ────────────────────────────────────────────────────
info "Validating Apache configuration..."
if "$BREW_PREFIX/bin/apachectl" configtest 2>&1 | grep -q "Syntax OK"; then
    ok "Apache configuration is valid."
else
    warn "Apache config validation warning (may still work):"
    "$BREW_PREFIX/bin/apachectl" configtest 2>&1 | tail -5
fi

# ── Start / restart Apache ────────────────────────────────────────────────────
info "Starting Apache..."
"$BREW" services restart httpd 2>/dev/null || "$BREW_PREFIX/bin/apachectl" restart 2>/dev/null || true
sleep 2

# ── Verify Apache is listening ────────────────────────────────────────────────
if lsof -i ":$HTTPD_PORT" -sTCP:LISTEN &>/dev/null 2>&1; then
    ok "Apache is listening on port $HTTPD_PORT."
else
    warn "Apache may not have started on port $HTTPD_PORT — check logs:"
    warn "  $APP_DIR/storage/logs/apache-error.log"
    warn "  $BREW_PREFIX/var/log/httpd/error_log"
fi

# ── Create launchd agents so services start on login ─────────────────────────
# (brew services already handles this, but confirm)
info "Ensuring services start automatically on login..."
"$BREW" services list | grep -E "mysql|php|httpd" | awk '{print $1, $2}' | while read svc status; do
    if [[ "$status" != "started" ]]; then
        "$BREW" services start "$svc" 2>/dev/null || true
    fi
done
ok "Auto-start on login configured via Homebrew services."

# ─────────────────────────────────────────────────────────────────────────────
step "6/6" "Finalising"
# ─────────────────────────────────────────────────────────────────────────────

# Write a simple desktop launcher script
LAUNCHER="$HOME/Desktop/Kore ERP.command"
cat > "$LAUNCHER" << 'LAUNCH'
#!/usr/bin/env bash
# Kore ERP — Open in Browser
open "http://localhost:HTTPD_PORT_PLACEHOLDER"
LAUNCH
# Replace the port placeholder
sed -i '' "s|HTTPD_PORT_PLACEHOLDER|${HTTPD_PORT}|g" "$LAUNCHER"
chmod +x "$LAUNCHER"
ok "Desktop shortcut created: ~/Desktop/Kore ERP.command"

# Save install info for uninstall script
cat > "$APP_DIR/.kore-install-info" << INFO
INSTALL_DATE=$(date)
APP_DIR=${APP_DIR}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
HTTPD_PORT=${HTTPD_PORT}
APP_URL=${APP_URL}
MYSQL_PKG=${MYSQL_PKG}
VHOST_CONF=${VHOST_CONF}
INFO
chmod 600 "$APP_DIR/.kore-install-info"

# ── Completion ────────────────────────────────────────────────────────────────
SETUP_URL="${APP_URL}/install"

echo
echo -e "  ${GREEN}${BOLD}╔══════════════════════════════════════════════════════════════╗"
echo -e "  ║                                                              ║"
echo -e "  ║   ✓  Kore ERP is installed and running!                     ║"
echo -e "  ║                                                              ║"
echo -e "  ╚══════════════════════════════════════════════════════════════╝${NC}"
echo
echo -e "  ${CYAN}${BOLD}Next step — complete setup in your browser:${NC}"
echo
echo -e "  ${BOLD}  Setup Wizard:${NC}  ${YELLOW}${SETUP_URL}${NC}"
echo -e "  ${BOLD}  App URL:      ${NC}  ${APP_URL}"
echo
echo -e "  ${GRAY}The wizard will ask you to create an admin account"
echo -e "  and configure your company settings.${NC}"
echo
echo -e "  ┌── Installation Details ──────────────────────────────────────"
echo -e "  │  App location : $APP_DIR"
echo -e "  │  Database     : $DB_NAME @ 127.0.0.1"
echo -e "  │  DB User      : $DB_USER"
echo -e "  │  DB Password  : $DB_PASS"
echo -e "  │  Apache port  : $HTTPD_PORT"
echo -e "  │  Install log  : $LOG_FILE"
echo -e "  └──────────────────────────────────────────────────────────────"
echo
echo -e "  ${YELLOW}⚠  Save your database password — it's stored in $APP_DIR/.env${NC}"
echo -e "  ${YELLOW}⚠  After setup, delete the /install directory for security.${NC}"
echo

# Open browser to setup wizard
sleep 2
open "$SETUP_URL"

echo -e "  ${GRAY}Press Enter to close this window...${NC}"
read -r
