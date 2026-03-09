#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Kore ERP - Windows Server Installer
.DESCRIPTION
    Automated installer for Kore ERP on Windows Server.
    Installs PHP dependencies, configures the database, sets up IIS,
    and launches the web-based setup wizard.
.NOTES
    Version   : 1.0.0
    Requires  : PowerShell 5.1+, Run as Administrator
    Run from  : The kore-erp project root directory
    Tested on : Windows Server 2016/2019/2022, Windows 10/11
#>

Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"
$ProgressPreference    = "SilentlyContinue"

# ─── Constants ────────────────────────────────────────────────────────────────
$Script:VERSION  = "1.0.0"
$Script:APP_ROOT = $PSScriptRoot
$Script:LOG_FILE = Join-Path $env:TEMP "kore-erp-install-$(Get-Date -Format 'yyyyMMdd-HHmmss').log"
$Script:SQL_DIR  = Join-Path $Script:APP_ROOT "install\sql"
$Script:ENV_FILE = Join-Path $Script:APP_ROOT ".env"

$Script:PHPExe      = $null
$Script:MySQLExe    = $null
$Script:ComposerExe = $null

# ─── Logging ──────────────────────────────────────────────────────────────────
function Log {
    param([string]$Msg, [string]$Level = "INFO")
    $ts = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    Add-Content $Script:LOG_FILE "[$ts][$Level] $Msg" -ErrorAction SilentlyContinue
}

# ─── Output Helpers ───────────────────────────────────────────────────────────
function Write-Banner {
    Clear-Host
    Write-Host ""
    Write-Host "  ╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Blue
    Write-Host "  ║                                                              ║" -ForegroundColor Blue
    Write-Host "  ║    ██╗  ██╗ ██████╗ ██████╗ ███████╗    ███████╗██████╗ ██╗ ║" -ForegroundColor Cyan
    Write-Host "  ║    ██║ ██╔╝██╔═══██╗██╔══██╗██╔════╝    ██╔════╝██╔══██╗██║ ║" -ForegroundColor Cyan
    Write-Host "  ║    █████╔╝ ██║   ██║██████╔╝█████╗      █████╗  ██████╔╝██║ ║" -ForegroundColor Cyan
    Write-Host "  ║    ██╔═██╗ ██║   ██║██╔══██╗██╔══╝      ██╔══╝  ██╔══██╗██╝ ║" -ForegroundColor Cyan
    Write-Host "  ║    ██║  ██╗╚██████╔╝██║  ██║███████╗    ███████╗██║  ██║██╗ ║" -ForegroundColor Cyan
    Write-Host "  ║    ╚═╝  ╚═╝ ╚═════╝ ╚═╝  ╚═╝╚══════╝    ╚══════╝╚═╝  ╚═╝╚═╝ ║" -ForegroundColor Cyan
    Write-Host "  ║                                                              ║" -ForegroundColor Blue
    Write-Host "  ║         Windows Server Installer  v$Script:VERSION                   ║" -ForegroundColor White
    Write-Host "  ║                                                              ║" -ForegroundColor Blue
    Write-Host "  ╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Blue
    Write-Host ""
    Write-Host "  Install log: $Script:LOG_FILE" -ForegroundColor DarkGray
    Write-Host ""
    Log "=== Kore ERP Installer v$Script:VERSION started ==="
    Log "App root: $Script:APP_ROOT"
}

function Write-Section {
    param([string]$Number, [string]$Title)
    Write-Host ""
    Write-Host "  ┌──────────────────────────────────────────────────────────────" -ForegroundColor DarkCyan
    Write-Host "  │  STEP $Number  —  $Title" -ForegroundColor Cyan
    Write-Host "  └──────────────────────────────────────────────────────────────" -ForegroundColor DarkCyan
    Write-Host ""
    Log "--- STEP $Number : $Title ---"
}

function Write-OK   { param([string]$M) Write-Host "    [✓] $M" -ForegroundColor Green;  Log $M "OK"   }
function Write-WARN { param([string]$M) Write-Host "    [!] $M" -ForegroundColor Yellow; Log $M "WARN" }
function Write-FAIL { param([string]$M) Write-Host "    [✗] $M" -ForegroundColor Red;    Log $M "FAIL" }
function Write-INFO { param([string]$M) Write-Host "    [-] $M" -ForegroundColor Gray;   Log $M "INFO" }

function Ask {
    param([string]$Prompt, [string]$Default = "", [switch]$Secret, [switch]$Optional)
    $hint = if ($Default) { " [$Default]" } else { "" }
    Write-Host "    $Prompt$hint : " -NoNewline -ForegroundColor White
    if ($Secret) {
        $s = Read-Host -AsSecureString
        $v = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
            [Runtime.InteropServices.Marshal]::SecureStringToBSTR($s))
    } else {
        $v = Read-Host
    }
    if (-not $v -and $Default) { $v = $Default }
    if (-not $v -and -not $Optional) {
        Write-FAIL "This field is required."
        return Ask $Prompt $Default -Secret:$Secret -Optional:$Optional
    }
    return $v
}

function AskYN {
    param([string]$Q, [bool]$Def = $true)
    $hint = if ($Def) { "Y/n" } else { "y/N" }
    Write-Host "    $Q [$hint]: " -NoNewline -ForegroundColor White
    $r = Read-Host
    if (-not $r) { return $Def }
    return $r -imatch "^[Yy]"
}

function Pause-Exit {
    param([string]$Msg = "Press Enter to exit...")
    Write-Host ""
    Write-Host "  $Msg" -ForegroundColor DarkGray -NoNewline
    $null = Read-Host
    exit 1
}

# ─── Prerequisite Finders ─────────────────────────────────────────────────────
function Find-PHP {
    $cmd = Get-Command "php" -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }

    $candidates = @(
        "C:\php\php.exe",
        "C:\PHP8\php.exe", "C:\PHP82\php.exe", "C:\PHP83\php.exe",
        "C:\xampp\php\php.exe", "C:\xampp64\php\php.exe",
        "C:\wamp64\bin\php\php8.2*\php.exe",
        "C:\wamp64\bin\php\php8.3*\php.exe",
        "C:\laragon\bin\php\php-8.2*\php.exe",
        "C:\laragon\bin\php\php-8.3*\php.exe",
        "C:\Program Files\PHP\v8.2\php.exe",
        "C:\Program Files\PHP\v8.3\php.exe"
    )
    foreach ($p in $candidates) {
        $r = Resolve-Path $p -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($r) { return $r.Path }
    }
    return $null
}

function Find-MySQL {
    $cmd = Get-Command "mysql" -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }

    $candidates = @(
        "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe",
        "C:\Program Files\MySQL\MySQL Server 8.4\bin\mysql.exe",
        "C:\Program Files\MySQL\MySQL Server 9.0\bin\mysql.exe",
        "C:\Program Files\MariaDB 10*\bin\mysql.exe",
        "C:\xampp\mysql\bin\mysql.exe",
        "C:\xampp64\mysql\bin\mysql.exe",
        "C:\wamp64\bin\mysql\mysql8*\bin\mysql.exe",
        "C:\wamp64\bin\mariadb\mariadb*\bin\mysql.exe",
        "C:\laragon\bin\mysql\mysql-8*\bin\mysql.exe"
    )
    foreach ($p in $candidates) {
        $r = Resolve-Path $p -ErrorAction SilentlyContinue | Select-Object -First 1
        if ($r) { return $r.Path }
    }
    return $null
}

function Find-Composer {
    $cmd = Get-Command "composer" -ErrorAction SilentlyContinue
    if ($cmd) { return $cmd.Source }

    $candidates = @(
        "C:\ProgramData\ComposerSetup\bin\composer.bat",
        "$env:APPDATA\Composer\vendor\bin\composer.bat",
        "C:\xampp\composer\composer.bat",
        "C:\laragon\bin\composer\composer.bat"
    )
    foreach ($p in $candidates) {
        if (Test-Path $p) { return $p }
    }
    return $null
}

function Install-Composer {
    Write-INFO "Downloading Composer installer from getcomposer.org..."
    $setup = Join-Path $env:TEMP "composer-setup.exe"
    try {
        Invoke-WebRequest -Uri "https://getcomposer.org/Composer-Setup.exe" `
            -OutFile $setup -UseBasicParsing -TimeoutSec 60
        Write-INFO "Running Composer setup (silent)..."
        $proc = Start-Process $setup -ArgumentList "/VERYSILENT /SUPPRESSMSGBOXES /NORESTART" `
            -Wait -PassThru
        Remove-Item $setup -Force -ErrorAction SilentlyContinue
        if ($proc.ExitCode -eq 0) {
            $env:PATH = [System.Environment]::GetEnvironmentVariable("PATH","Machine") + ";" +
                        [System.Environment]::GetEnvironmentVariable("PATH","User")
            return Find-Composer
        }
    } catch {
        Write-WARN "Composer auto-install failed: $_"
    }
    return $null
}

# ─── PHP Version Check ────────────────────────────────────────────────────────
function Test-PHPVersion {
    param([string]$PhpPath)
    try {
        $v = & $PhpPath -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>$null
        $parts = $v -split '\.'
        $major = [int]$parts[0]
        $minor = [int]$parts[1]
        return @{ Version = $v; OK = ($major -gt 8 -or ($major -eq 8 -and $minor -ge 2)) }
    } catch {
        return @{ Version = "unknown"; OK = $false }
    }
}

# ─── Run MySQL Query ─────────────────────────────────────────────────────────
function Invoke-MySQL {
    param([string]$Sql, [string]$Database = "")
    $args = @(
        "--host=$($cfg.DBHost)",
        "--port=$($cfg.DBPort)",
        "--user=$($cfg.DBUser)",
        "--password=$($cfg.DBPass)",
        "--batch",
        "--silent"
    )
    if ($Database) { $args += "--database=$Database" }
    $result = $Sql | & $Script:MySQLExe @args 2>&1
    return @{ Output = $result; ExitCode = $LASTEXITCODE }
}

function Invoke-MySQLFile {
    param([string]$FilePath, [string]$Database)
    $args = @(
        "--host=$($cfg.DBHost)",
        "--port=$($cfg.DBPort)",
        "--user=$($cfg.DBUser)",
        "--password=$($cfg.DBPass)",
        "--database=$Database",
        "--batch",
        "--silent"
    )
    $result = Get-Content $FilePath -Raw | & $Script:MySQLExe @args 2>&1
    return @{ Output = $result; ExitCode = $LASTEXITCODE }
}

# ─── Ensure Directory ─────────────────────────────────────────────────────────
function Ensure-Dir {
    param([string]$Path)
    if (-not (Test-Path $Path)) {
        New-Item -ItemType Directory -Path $Path -Force | Out-Null
    }
}

# ═════════════════════════════════════════════════════════════════════════════
#  MAIN
# ═════════════════════════════════════════════════════════════════════════════

Write-Banner

# Verify we're in the right directory
if (-not (Test-Path (Join-Path $Script:APP_ROOT "artisan"))) {
    Write-FAIL "Cannot find 'artisan' in the current directory."
    Write-FAIL "Please run install.bat from the kore-erp project root."
    Pause-Exit
}

Write-Host "  Application directory: $Script:APP_ROOT" -ForegroundColor Gray
Write-Host ""
if (-not (AskYN "Ready to begin Kore ERP installation?" $true)) {
    Write-Host "  Installation cancelled." -ForegroundColor Yellow
    exit 0
}

# ─── STEP 1: Prerequisites ────────────────────────────────────────────────────
Write-Section "1/5" "Checking Prerequisites"
$prereqFailed = $false

# — PHP ——————————————————————————————————————————————————
Write-Host "  PHP 8.2+" -ForegroundColor Gray
$phpPath = Find-PHP
if ($phpPath) {
    $phpInfo = Test-PHPVersion $phpPath
    if ($phpInfo.OK) {
        Write-OK "PHP $($phpInfo.Version) found: $phpPath"
        $Script:PHPExe = $phpPath
    } else {
        Write-FAIL "PHP $($phpInfo.Version) is too old. PHP 8.2+ required."
        Write-INFO "Download: https://windows.php.net/download/"
        $prereqFailed = $true
    }
} else {
    Write-FAIL "PHP not found."
    Write-INFO "Option 1 — PHP for Windows:  https://windows.php.net/download/"
    Write-INFO "Option 2 — XAMPP (easiest):  https://www.apachefriends.org/"
    Write-INFO "Option 3 — Laragon:          https://laragon.org/"
    $prereqFailed = $true
}

# Required PHP extensions
if ($Script:PHPExe) {
    Write-Host ""
    Write-Host "  PHP Extensions" -ForegroundColor Gray
    $required = @("pdo_mysql","openssl","mbstring","tokenizer","xml","ctype","fileinfo","gd","bcmath","json","zip")
    $loadedExts = & $Script:PHPExe -m 2>$null
    foreach ($ext in $required) {
        if ($loadedExts -contains $ext) {
            Write-OK "  $ext"
        } else {
            Write-WARN "  $ext  (missing — enable in php.ini)"
        }
    }
}

Write-Host ""

# — MySQL ————————————————————————————————————————————————
Write-Host "  MySQL / MariaDB" -ForegroundColor Gray
$mysqlPath = Find-MySQL
if ($mysqlPath) {
    Write-OK "MySQL client found: $mysqlPath"
    $Script:MySQLExe = $mysqlPath
} else {
    Write-FAIL "MySQL/MariaDB not found."
    Write-INFO "Download MySQL: https://dev.mysql.com/downloads/mysql/"
    Write-INFO "Or use XAMPP which bundles MySQL + Apache."
    $prereqFailed = $true
}

Write-Host ""

# — Composer ———————————————————————————————————————————————
Write-Host "  Composer" -ForegroundColor Gray
$composerPath = Find-Composer
if ($composerPath) {
    Write-OK "Composer found: $composerPath"
    $Script:ComposerExe = $composerPath
} else {
    Write-WARN "Composer not found."
    if (AskYN "  Auto-download and install Composer now?" $true) {
        $composerPath = Install-Composer
        if ($composerPath) {
            Write-OK "Composer installed: $composerPath"
            $Script:ComposerExe = $composerPath
        } else {
            Write-FAIL "Composer auto-install failed."
            Write-INFO "Install manually from: https://getcomposer.org/download/"
            $prereqFailed = $true
        }
    } else {
        Write-FAIL "Composer is required."
        $prereqFailed = $true
    }
}

if ($prereqFailed) {
    Write-Host ""
    Write-FAIL "Please install the missing prerequisites, then re-run install.bat"
    Pause-Exit
}

Write-Host ""
Write-OK "All prerequisites satisfied."

# ─── STEP 2: Configuration ────────────────────────────────────────────────────
Write-Section "2/5" "Configuration"

Write-Host "  Press Enter to accept the value shown in [brackets]." -ForegroundColor DarkGray
Write-Host ""

$cfg = @{}

Write-Host "  Application" -ForegroundColor Cyan
Write-Host "  ──────────────────────────────────────────────────────" -ForegroundColor DarkGray
$cfg.AppUrl   = Ask "Application URL (http://your-server-ip or http://kore.company.com)" "http://localhost"
$cfg.AppTZ    = Ask "Timezone (e.g. America/New_York, UTC, Australia/Sydney)" "UTC"
Write-Host ""

Write-Host "  Database" -ForegroundColor Cyan
Write-Host "  ──────────────────────────────────────────────────────" -ForegroundColor DarkGray
$cfg.DBHost   = Ask "Database Host" "127.0.0.1"
$cfg.DBPort   = Ask "Database Port" "3306"
$cfg.DBName   = Ask "Database Name" "kore_erp"
$cfg.DBUser   = Ask "Database Username" "root"
$cfg.DBPass   = Ask "Database Password" "" -Secret

# Test DB connection
Write-Host ""
Write-INFO "Testing database connection..."
try {
    $testResult = "SELECT 1;" | & $Script:MySQLExe `
        "--host=$($cfg.DBHost)" "--port=$($cfg.DBPort)" `
        "--user=$($cfg.DBUser)" "--password=$($cfg.DBPass)" `
        "--batch" "--silent" 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-OK "Database connection successful."
    } else {
        Write-FAIL "Could not connect: $testResult"
        Write-FAIL "Check credentials and ensure MySQL service is running."
        Pause-Exit
    }
} catch {
    Write-FAIL "Database connection failed: $_"
    Pause-Exit
}

Write-Host ""

Write-Host "  Web Server" -ForegroundColor Cyan
Write-Host "  ──────────────────────────────────────────────────────" -ForegroundColor DarkGray
$iisService = Get-Service "W3SVC" -ErrorAction SilentlyContinue
if ($iisService) {
    $cfg.SetupIIS = AskYN "Configure IIS website automatically?" $true
    if ($cfg.SetupIIS) {
        $cfg.IISSiteName = Ask "IIS Website Name" "Kore ERP"
        $cfg.IISPort     = Ask "HTTP Port" "80"
        $cfg.IISHost     = Ask "Hostname binding (leave blank for all)" "" -Optional
    }
} else {
    Write-WARN "IIS not detected. Manual web server config required after install."
    Write-INFO "Point web root to: $(Join-Path $Script:APP_ROOT 'public')"
    $cfg.SetupIIS = $false
}

Write-Host ""

# — Summary ——————————————————————————————————————————————
Write-Host "  ┌── Configuration Summary ──────────────────────────────────" -ForegroundColor DarkCyan
Write-Host "  │  App URL   :  $($cfg.AppUrl)" -ForegroundColor White
Write-Host "  │  Timezone  :  $($cfg.AppTZ)" -ForegroundColor White
Write-Host "  │  Database  :  $($cfg.DBUser)@$($cfg.DBHost):$($cfg.DBPort)/$($cfg.DBName)" -ForegroundColor White
if ($cfg.SetupIIS) {
    Write-Host "  │  IIS Site  :  $($cfg.IISSiteName) on port $($cfg.IISPort)" -ForegroundColor White
}
Write-Host "  │  Directory :  $Script:APP_ROOT" -ForegroundColor White
Write-Host "  └───────────────────────────────────────────────────────────" -ForegroundColor DarkCyan
Write-Host ""

if (-not (AskYN "Proceed with installation?" $true)) {
    Write-Host "  Installation cancelled." -ForegroundColor Yellow
    exit 0
}

# ─── STEP 3: Install PHP Dependencies ────────────────────────────────────────
Write-Section "3/5" "Installing PHP Dependencies"

Write-INFO "Running composer install (this may take 1-3 minutes)..."
Push-Location $Script:APP_ROOT
try {
    if ($Script:ComposerExe -like "*.phar") {
        $out = & $Script:PHPExe $Script:ComposerExe install --no-interaction --optimize-autoloader --no-dev 2>&1
    } else {
        $out = & $Script:ComposerExe install --no-interaction --optimize-autoloader --no-dev 2>&1
    }
    Log "Composer output: $out"
    if ($LASTEXITCODE -eq 0) {
        Write-OK "Composer dependencies installed."
    } else {
        Write-WARN "Composer finished with warnings (exit $LASTEXITCODE). Check log for details."
        ($out | Select-Object -Last 5) | ForEach-Object { Write-INFO $_ }
    }
} catch {
    Write-FAIL "Composer failed: $_"
    Pop-Location
    Pause-Exit
}
Pop-Location

# ─── STEP 4: Configure Application ───────────────────────────────────────────
Write-Section "4/5" "Configuring Application"

# Create storage directories
Write-INFO "Creating storage directories..."
$storageDirs = @(
    "storage\app",
    "storage\app\public",
    "storage\framework\cache",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\testing",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache"
)
foreach ($d in $storageDirs) {
    Ensure-Dir (Join-Path $Script:APP_ROOT $d)
}
Write-OK "Storage directories ready."

# Write .env file
Write-INFO "Writing .env configuration file..."
$envContent = @"
APP_NAME="Kore ERP"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=$($cfg.AppUrl)
APP_TIMEZONE=$($cfg.AppTZ)

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=$($cfg.DBHost)
DB_PORT=$($cfg.DBPort)
DB_DATABASE=$($cfg.DBName)
DB_USERNAME=$($cfg.DBUser)
DB_PASSWORD=$($cfg.DBPass)

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=587
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@company.com"
MAIL_FROM_NAME="Kore ERP"

KORE_CURRENCY_SYMBOL=$
KORE_CURRENCY_CODE=USD
KORE_INVOICE_PREFIX=INV
KORE_FISCAL_YEAR_START=01-01
"@

Set-Content -Path $Script:ENV_FILE -Value $envContent -Encoding UTF8
Write-OK ".env file created."

# Generate application key
Write-INFO "Generating application encryption key..."
Push-Location $Script:APP_ROOT
$keyOut = & $Script:PHPExe artisan key:generate --force 2>&1
Log "key:generate: $keyOut"
if ($LASTEXITCODE -eq 0) {
    Write-OK "Application key generated."
} else {
    Write-WARN "key:generate: $keyOut"
}

# Storage symlink
Write-INFO "Creating storage symlink..."
$linkOut = & $Script:PHPExe artisan storage:link 2>&1
Log "storage:link: $linkOut"
if ($LASTEXITCODE -eq 0) {
    Write-OK "Storage link created."
} else {
    Write-WARN "Storage link: $linkOut"
}

# Clear bootstrap cache
& $Script:PHPExe artisan config:clear 2>&1 | Out-Null
& $Script:PHPExe artisan cache:clear 2>&1 | Out-Null
Pop-Location
Write-OK "Application caches cleared."

# Set filesystem permissions for IIS / web user
Write-INFO "Setting file permissions..."
$permDirs = @(
    (Join-Path $Script:APP_ROOT "storage"),
    (Join-Path $Script:APP_ROOT "bootstrap\cache"),
    (Join-Path $Script:APP_ROOT "public\storage")
)
foreach ($dir in $permDirs) {
    if (Test-Path $dir) {
        try {
            $acl  = Get-Acl $dir
            # IIS_IUSRS (standard IIS worker process account)
            $rule1 = New-Object System.Security.AccessControl.FileSystemAccessRule(
                "IIS_IUSRS", "Modify", "ContainerInherit,ObjectInherit", "None", "Allow")
            # IUSR (anonymous IIS user)
            $rule2 = New-Object System.Security.AccessControl.FileSystemAccessRule(
                "IUSR", "ReadAndExecute", "ContainerInherit,ObjectInherit", "None", "Allow")
            $acl.AddAccessRule($rule1)
            $acl.AddAccessRule($rule2)
            Set-Acl $dir $acl
            Write-OK "  Permissions set: $dir"
        } catch {
            Write-WARN "  Could not set ACL on $dir (non-critical): $_"
        }
    }
}

# ─── STEP 5: Database + Web Server ───────────────────────────────────────────
Write-Section "5/5" "Database & Web Server"

# Create database
Write-INFO "Creating database '$($cfg.DBName)' if not exists..."
$createDB = @"
CREATE DATABASE IF NOT EXISTS ``$($cfg.DBName)``
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
"@
$r = Invoke-MySQL $createDB
if ($r.ExitCode -eq 0) {
    Write-OK "Database '$($cfg.DBName)' ready."
} else {
    Write-FAIL "Could not create database: $($r.Output)"
    Pause-Exit
}

# Run SQL migration files
$sqlFiles = @(
    "01_core.sql",
    "02_crm.sql",
    "03_projects.sql",
    "04_timesheets.sql",
    "05_approvals.sql",
    "06_invoicing.sql",
    "07_settings.sql"
)

Write-INFO "Running database migrations..."
$sqlFailed = $false
foreach ($f in $sqlFiles) {
    $path = Join-Path $Script:SQL_DIR $f
    if (Test-Path $path) {
        $r = Invoke-MySQLFile $path $cfg.DBName
        if ($r.ExitCode -eq 0) {
            Write-OK "  $f"
        } else {
            # Warnings about existing tables are normal on re-install
            $errText = "$($r.Output)"
            if ($errText -match "already exists|Duplicate entry") {
                Write-WARN "  $f (table already exists — skipped, non-critical)"
            } else {
                Write-FAIL "  $f failed: $errText"
                $sqlFailed = $true
            }
        }
    } else {
        Write-WARN "  $f not found at $path"
    }
}

if ($sqlFailed) {
    Write-WARN "Some SQL files had errors. Check the install log for details."
    Write-INFO "Log: $Script:LOG_FILE"
} else {
    Write-OK "All database migrations applied."
}

# — IIS Configuration ————————————————————————————————————
if ($cfg.SetupIIS) {
    Write-Host ""
    Write-INFO "Configuring IIS..."
    try {
        Import-Module WebAdministration -ErrorAction Stop

        $poolName  = ($cfg.IISSiteName -replace "[^a-zA-Z0-9_]", "_")
        $publicDir = Join-Path $Script:APP_ROOT "public"

        # Application Pool
        if (-not (Test-Path "IIS:\AppPools\$poolName")) {
            New-WebAppPool -Name $poolName | Out-Null
            Write-OK "  App Pool '$poolName' created."
        } else {
            Write-OK "  App Pool '$poolName' already exists."
        }

        # Set app pool to use LocalSystem (adjust to service account in production)
        Set-ItemProperty "IIS:\AppPools\$poolName" processModel.identityType LocalSystem

        # Enable 64-bit
        Set-ItemProperty "IIS:\AppPools\$poolName" enable32BitAppOnWin64 $false

        # Remove existing site if present
        if (Get-WebSite -Name $cfg.IISSiteName -ErrorAction SilentlyContinue) {
            Remove-WebSite -Name $cfg.IISSiteName
            Write-INFO "  Removed existing site '$($cfg.IISSiteName)'."
        }

        # Create website
        New-WebSite -Name $cfg.IISSiteName `
            -PhysicalPath $publicDir `
            -ApplicationPool $poolName `
            -Port ([int]$cfg.IISPort) `
            -Force | Out-Null

        # Hostname binding
        if ($cfg.IISHost) {
            Set-WebBinding -Name $cfg.IISSiteName `
                -BindingInformation "*:$($cfg.IISPort):" `
                -PropertyName HostHeader -Value $cfg.IISHost
            Write-OK "  Hostname binding: $($cfg.IISHost)"
        }

        Write-OK "  Website '$($cfg.IISSiteName)' created on port $($cfg.IISPort)."
        Write-OK "  Physical path: $publicDir"

        # Check URL Rewrite Module
        $rwModule = Get-WebConfiguration `
            -Filter "system.webServer/globalModules/add[@name='RewriteModule']" 2>$null
        if ($rwModule) {
            Write-OK "  IIS URL Rewrite module detected."
        } else {
            Write-WARN "  IIS URL Rewrite 2.1 module NOT detected."
            Write-WARN "  Routes will NOT work without it."
            Write-INFO "  Download: https://www.iis.net/downloads/microsoft/url-rewrite"
        }

        # Restart IIS
        Write-INFO "  Restarting IIS..."
        & iisreset /restart /noforce 2>&1 | Out-Null
        Write-OK "  IIS restarted."

    } catch {
        Write-WARN "IIS configuration encountered an error: $_"
        Write-INFO "You may need to configure IIS manually."
        Write-INFO "Point site root to: $(Join-Path $Script:APP_ROOT 'public')"
        Write-INFO "See DEPLOYMENT.md for step-by-step IIS instructions."
    }
} else {
    # XAMPP / Apache hint
    $xamppService = Get-Service "Apache2.4" -ErrorAction SilentlyContinue
    if ($xamppService) {
        Write-Host ""
        Write-INFO "XAMPP Apache detected. Add this virtual host to httpd-vhosts.conf:"
        Write-Host ""
        $docRoot = (Join-Path $Script:APP_ROOT "public").Replace('\', '/')
        Write-Host "    <VirtualHost *:80>" -ForegroundColor DarkYellow
        Write-Host "        ServerName kore.local" -ForegroundColor DarkYellow
        Write-Host "        DocumentRoot `"$docRoot`"" -ForegroundColor DarkYellow
        Write-Host "        <Directory `"$docRoot`">" -ForegroundColor DarkYellow
        Write-Host "            AllowOverride All" -ForegroundColor DarkYellow
        Write-Host "            Require all granted" -ForegroundColor DarkYellow
        Write-Host "        </Directory>" -ForegroundColor DarkYellow
        Write-Host "    </VirtualHost>" -ForegroundColor DarkYellow
        Write-Host ""
        Write-INFO "Then add '127.0.0.1 kore.local' to C:\Windows\System32\drivers\etc\hosts"
    }
}

# ─── COMPLETION ───────────────────────────────────────────────────────────────
$appUrl = $cfg.AppUrl
$setupUrl = $appUrl.TrimEnd('/') + "/install"

Write-Host ""
Write-Host "  ╔══════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "  ║                                                              ║" -ForegroundColor Green
Write-Host "  ║   ✓  Kore ERP server-side installation complete!            ║" -ForegroundColor Green
Write-Host "  ║                                                              ║" -ForegroundColor Green
Write-Host "  ╚══════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""
Write-Host "  ┌── What to do next ───────────────────────────────────────────" -ForegroundColor Cyan
Write-Host "  │" -ForegroundColor Cyan
Write-Host "  │  1. Open the web setup wizard to create your admin account:" -ForegroundColor White
Write-Host "  │     $setupUrl" -ForegroundColor Yellow
Write-Host "  │" -ForegroundColor Cyan
Write-Host "  │  2. The wizard will:" -ForegroundColor White
Write-Host "  │     • Verify requirements" -ForegroundColor Gray
Write-Host "  │     • Create the database tables" -ForegroundColor Gray
Write-Host "  │     • Set up your admin account" -ForegroundColor Gray
Write-Host "  │     • Configure company settings" -ForegroundColor Gray
Write-Host "  │" -ForegroundColor Cyan
Write-Host "  │  3. After setup, delete the /install directory for security:" -ForegroundColor White
Write-Host "  │     Remove-Item -Recurse -Force `"$(Join-Path $Script:APP_ROOT 'install')`"" -ForegroundColor Gray
Write-Host "  │" -ForegroundColor Cyan
Write-Host "  └──────────────────────────────────────────────────────────────" -ForegroundColor Cyan
Write-Host ""
Write-Host "  ┌── Configuration Details ─────────────────────────────────────" -ForegroundColor DarkGray
Write-Host "  │  App URL    : $appUrl" -ForegroundColor White
Write-Host "  │  Database   : $($cfg.DBName) @ $($cfg.DBHost)" -ForegroundColor White
Write-Host "  │  App Root   : $Script:APP_ROOT" -ForegroundColor White
Write-Host "  │  .env File  : $Script:ENV_FILE" -ForegroundColor White
Write-Host "  │  Install Log: $Script:LOG_FILE" -ForegroundColor White
Write-Host "  └──────────────────────────────────────────────────────────────" -ForegroundColor DarkGray
Write-Host ""

Log "=== Installation complete. Setup wizard: $setupUrl ==="

if (AskYN "Open the web setup wizard in your browser now?" $true) {
    Start-Process $setupUrl
}

Write-Host ""
Write-Host "  Press Enter to exit." -ForegroundColor DarkGray -NoNewline
$null = Read-Host
