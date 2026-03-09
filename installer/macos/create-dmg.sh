#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
#  Kore ERP — macOS DMG Builder
#
#  Run this script from the project root (or anywhere) to build a
#  distributable DMG installer for macOS.
#
#  Usage:
#      bash installer/macos/create-dmg.sh [output-dir]
#
#  Output:
#      KoreERP-Installer.dmg   (in output-dir, default: repo root)
#
#  Requirements (on the build machine):
#      macOS 12+  |  Xcode CLI tools  |  hdiutil (built-in)
#      Optional:  brew install create-dmg   (for prettier DMG)
#
#  What ends up inside the DMG:
#      ├── install.command      ← double-click to install
#      ├── uninstall.command    ← remove Kore ERP
#      ├── README.txt
#      └── KoreERP/             ← the full application source
#              ├── app/
#              ├── install/sql/
#              ├── composer.json
#              └── ... (all app files, NO vendor/ or .env)
# ─────────────────────────────────────────────────────────────────────────────

set -euo pipefail

# ── Paths ─────────────────────────────────────────────────────────────────────
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
REPO_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
OUT_DIR="${1:-$REPO_ROOT}"
DMG_NAME="KoreERP-Installer"
DMG_VOL="Kore ERP Installer"
DMG_SIZE="300m"          # Adjust if app grows; 300 MB covers code + installer

RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; GRAY='\033[0;37m'; BOLD='\033[1m'; NC='\033[0m'

ok()   { echo -e "  ${GREEN}✓${NC}  $*"; }
warn() { echo -e "  ${YELLOW}⚠${NC}  $*"; }
info() { echo -e "  ${GRAY}→${NC}  $*"; }
die()  { echo -e "  ${RED}✗${NC}  $*"; exit 1; }

echo
echo -e "  ${CYAN}${BOLD}Kore ERP — macOS DMG Builder${NC}"
echo -e "  $(printf '─%.0s' {1..52})"
echo -e "  ${GRAY}Repo root : $REPO_ROOT${NC}"
echo -e "  ${GRAY}Output    : $OUT_DIR/${DMG_NAME}.dmg${NC}"
echo

# ── Sanity checks ─────────────────────────────────────────────────────────────
[[ "$(uname)" == "Darwin" ]] || die "This script must run on macOS."
[[ -f "$REPO_ROOT/artisan" ]] || die "Cannot find artisan. Run from the kore-erp repo."

# ── Staging directory ─────────────────────────────────────────────────────────
STAGING="$(mktemp -d)/dmg-staging"
mkdir -p "$STAGING/KoreERP"
info "Staging directory: $STAGING"

# ── Copy application source (no vendor, no .env, no git) ─────────────────────
info "Copying application files (this may take a moment)..."
rsync -a --quiet \
    --exclude='.git' \
    --exclude='.gitignore' \
    --exclude='vendor/' \
    --exclude='node_modules/' \
    --exclude='.env' \
    --exclude='.env.*' \
    --exclude='storage/logs/*.log' \
    --exclude='storage/framework/cache/*' \
    --exclude='storage/framework/sessions/*' \
    --exclude='storage/framework/views/*' \
    --exclude='bootstrap/cache/*.php' \
    --exclude='*.DS_Store' \
    --exclude='installer/macos/create-dmg.sh' \
    --exclude='KoreERP-Installer.dmg' \
    "$REPO_ROOT/" "$STAGING/KoreERP/"
ok "Application files staged."

# ── Copy installer scripts ────────────────────────────────────────────────────
cp "$SCRIPT_DIR/install.command"   "$STAGING/install.command"
cp "$SCRIPT_DIR/uninstall.command" "$STAGING/uninstall.command"
chmod +x "$STAGING/install.command" "$STAGING/uninstall.command"
ok "Installer scripts added."

# ── Write README.txt ──────────────────────────────────────────────────────────
APP_VER="$(grep -m1 "KORE_VERSION" "$REPO_ROOT/install/index.php" 2>/dev/null | grep -o "'[^']*'" | tr -d "'"  || echo "1.0.0")"
cat > "$STAGING/README.txt" << README
╔══════════════════════════════════════════════════════════════════╗
║                 Kore ERP  v${APP_VER}  —  macOS Installer              ║
╚══════════════════════════════════════════════════════════════════╝

QUICK START
───────────
1. Double-click  install.command
   • First time: right-click → Open (to bypass Gatekeeper)
   • The Terminal will open and guide you through setup.

2. The installer will:
   • Install Homebrew (if needed)
   • Install PHP 8.3, MySQL 8, Apache, and Composer
   • Deploy Kore ERP to  ~/Sites/kore-erp
   • Create the database and apply all schema migrations
   • Configure Apache on port 8080
   • Open your browser to the web setup wizard

3. In the browser wizard:
   • Create your administrator account
   • Set your company name and preferences
   • Click Install

4. After setup, delete the /install folder from the app
   for security (the wizard will remind you).

SYSTEM REQUIREMENTS
───────────────────
• macOS 12 Monterey or later
• Apple Silicon (M1/M2/M3/M4) or Intel
• ~1 GB free disk space
• Internet connection (for Homebrew packages)

UNINSTALL
─────────
Double-click  uninstall.command

SUPPORT
───────
App directory:   ~/Sites/kore-erp
Install log:     ~/Library/Logs/kore-erp-install.log
Apache logs:     ~/Sites/kore-erp/storage/logs/apache-error.log

README
ok "README.txt written."

# ── Create writable DMG ───────────────────────────────────────────────────────
info "Creating disk image..."
TEMP_DMG="$(dirname "$STAGING")/${DMG_NAME}-rw.dmg"
hdiutil create \
    -srcfolder "$STAGING" \
    -volname   "$DMG_VOL" \
    -fs        "HFS+" \
    -fsargs    "-c c=64,a=16,b=16" \
    -format    UDRW \
    -size      "$DMG_SIZE" \
    "$TEMP_DMG" \
    > /dev/null
ok "Writable DMG created."

# ── (Optional) Mount and customise appearance ─────────────────────────────────
info "Customising DMG appearance..."
MOUNT_DIR="$(mktemp -d)/kore-erp-dmg"
hdiutil attach "$TEMP_DMG" -mountpoint "$MOUNT_DIR" -nobrowse -quiet

# Set a simple icon arrangement via AppleScript
osascript << APPLESCRIPT 2>/dev/null || true
tell application "Finder"
    tell disk "$DMG_VOL"
        open
        set current view of container window to icon view
        set toolbar visible of container window to false
        set statusbar visible of container window to false
        set bounds of container window to {200, 100, 780, 500}
        set opts to icon view options of container window
        set icon size of opts to 72
        set arrangement of opts to not arranged
        -- Position icons
        set position of item "install.command"   to {120, 180}
        set position of item "uninstall.command" to {120, 310}
        set position of item "README.txt"        to {300, 180}
        set position of item "KoreERP"           to {470, 180}
        close
    end tell
end tell
APPLESCRIPT

# Sync and detach
sync
hdiutil detach "$MOUNT_DIR" -quiet
ok "DMG appearance set."

# ── Convert to read-only compressed DMG ───────────────────────────────────────
FINAL_DMG="$OUT_DIR/${DMG_NAME}.dmg"
info "Compressing to final DMG..."
hdiutil convert "$TEMP_DMG" \
    -format UDZO \
    -imagekey zlib-level=9 \
    -o "$FINAL_DMG" \
    > /dev/null
ok "Compressed DMG created."

# ── Cleanup ───────────────────────────────────────────────────────────────────
rm -f "$TEMP_DMG"
rm -rf "$(dirname "$STAGING")"

# ── Summary ───────────────────────────────────────────────────────────────────
DMG_SIZE_MB=$(du -m "$FINAL_DMG" | cut -f1)
echo
echo -e "  ${GREEN}${BOLD}✓  DMG build complete!${NC}"
echo
echo -e "  ${BOLD}File   :${NC} $FINAL_DMG"
echo -e "  ${BOLD}Size   :${NC} ${DMG_SIZE_MB} MB"
echo
echo -e "  ${GRAY}To distribute: share $FINAL_DMG with your testers."
echo -e "  They right-click install.command → Open on first run.${NC}"
echo
