<?php
/**
 * KORE ERP - Setup Wizard
 * File: install/index.php
 *
 * Steps: Requirements Check > Database Config > Admin Account > Company Settings > Install > Complete
 *
 * SECURITY: Delete or rename the /install directory after setup is complete.
 */

session_start();

// ─────────────────────────────────────────────
// CONFIGURATION
// ─────────────────────────────────────────────
define('KORE_VERSION', '1.0.0');
define('INSTALL_LOCK_FILE', __DIR__ . '/../storage/installed.lock');
define('ENV_FILE', __DIR__ . '/../.env');
define('MIN_PHP_VERSION', '8.2.0');

$required_extensions = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];

// ─────────────────────────────────────────────
// GUARD: Already installed?
// ─────────────────────────────────────────────
if (file_exists(INSTALL_LOCK_FILE) && !isset($_GET['force'])) {
    die('<html><body style="font-family:sans-serif;text-align:center;padding:80px;">
        <h2>Kore ERP is already installed.</h2>
        <p>For security, please delete the <code>/install</code> directory from your server.</p>
        <a href="../" style="color:#0d6efd;">Go to Application</a>
    </body></html>');
}

// ─────────────────────────────────────────────
// STEP ROUTING
// ─────────────────────────────────────────────
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(6, $step));

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {

        case 1:
            header('Location: ?step=2');
            exit;

        case 2:
            $db_host = trim($_POST['db_host'] ?? 'localhost');
            $db_port = trim($_POST['db_port'] ?? '3306');
            $db_name = trim($_POST['db_name'] ?? 'kore_erp');
            $db_user = trim($_POST['db_user'] ?? '');
            $db_pass = $_POST['db_pass'] ?? '';

            if (empty($db_user)) { $errors[] = 'Database username is required.'; }
            if (empty($db_name)) { $errors[] = 'Database name is required.'; }

            if (empty($errors)) {
                try {
                    $dsn = "mysql:host={$db_host};port={$db_port};charset=utf8mb4";
                    $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                    $_SESSION['install'] = array_merge($_SESSION['install'] ?? [], [
                        'db_host' => $db_host,
                        'db_port' => $db_port,
                        'db_name' => $db_name,
                        'db_user' => $db_user,
                        'db_pass' => $db_pass,
                    ]);
                    header('Location: ?step=3');
                    exit;
                } catch (PDOException $e) {
                    $errors[] = 'Database connection failed: ' . $e->getMessage();
                }
            }
            break;

        case 3:
            $admin_name  = trim($_POST['admin_name'] ?? '');
            $admin_email = trim($_POST['admin_email'] ?? '');
            $admin_pass  = $_POST['admin_pass'] ?? '';
            $admin_pass2 = $_POST['admin_pass2'] ?? '';

            if (empty($admin_name))  { $errors[] = 'Full name is required.'; }
            if (empty($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Valid email address is required.';
            }
            if (strlen($admin_pass) < 8) { $errors[] = 'Password must be at least 8 characters.'; }
            if ($admin_pass !== $admin_pass2) { $errors[] = 'Passwords do not match.'; }

            if (empty($errors)) {
                $_SESSION['install']['admin_name']  = $admin_name;
                $_SESSION['install']['admin_email'] = $admin_email;
                $_SESSION['install']['admin_pass']  = password_hash($admin_pass, PASSWORD_BCRYPT);
                header('Location: ?step=4');
                exit;
            }
            break;

        case 4:
            $company_name = trim($_POST['company_name'] ?? '');
            $app_url      = trim($_POST['app_url'] ?? 'http://localhost');
            $timezone     = trim($_POST['timezone'] ?? 'UTC');
            $fiscal_start = trim($_POST['fiscal_start'] ?? '01');
            $currency     = trim($_POST['currency'] ?? 'USD');

            if (empty($company_name)) { $errors[] = 'Company name is required.'; }

            if (empty($errors)) {
                $_SESSION['install']['company_name'] = $company_name;
                $_SESSION['install']['app_url']      = $app_url;
                $_SESSION['install']['timezone']     = $timezone;
                $_SESSION['install']['fiscal_start'] = $fiscal_start;
                $_SESSION['install']['currency']     = $currency;
                header('Location: ?step=5');
                exit;
            }
            break;

        case 5:
            $install_errors = runInstallation();
            if (empty($install_errors)) {
                header('Location: ?step=6');
                exit;
            } else {
                $errors = $install_errors;
            }
            break;
    }
}

// ─────────────────────────────────────────────
// INSTALLATION FUNCTION
// ─────────────────────────────────────────────
function runInstallation(): array
{
    $data = $_SESSION['install'] ?? [];

    if (empty($data['db_host'])) {
        return ['Session data missing. Please restart the wizard from Step 1.'];
    }

    try {
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Run SQL schema files
        $sql_files = [
            __DIR__ . '/sql/01_core.sql',
            __DIR__ . '/sql/02_crm.sql',
            __DIR__ . '/sql/03_projects.sql',
            __DIR__ . '/sql/04_timesheets.sql',
            __DIR__ . '/sql/05_approvals.sql',
            __DIR__ . '/sql/06_invoicing.sql',
            __DIR__ . '/sql/07_settings.sql',
        ];

        foreach ($sql_files as $file) {
            if (file_exists($file)) {
                $sql = file_get_contents($file);
                // Execute each statement individually
                foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                    if (!empty($stmt)) {
                        try {
                            $pdo->exec($stmt);
                        } catch (PDOException $e) {
                            // Ignore "already exists" errors on re-install
                            if (strpos($e->getMessage(), 'already exists') === false &&
                                strpos($e->getMessage(), 'Duplicate entry') === false) {
                                throw $e;
                            }
                        }
                    }
                }
            }
        }

        // Create admin user
        $nameParts = explode(' ', $data['admin_name'], 2);
        $firstName = $nameParts[0];
        $lastName  = $nameParts[1] ?? 'User';
        $stmt = $pdo->prepare(
            "INSERT INTO users (first_name, last_name, email, password, role_id, is_active, created_at, updated_at)
             SELECT ?, ?, ?, ?, id, 1, NOW(), NOW() FROM roles WHERE name = 'Admin' LIMIT 1
             ON DUPLICATE KEY UPDATE password = VALUES(password), is_active = 1, updated_at = NOW()"
        );
        $stmt->execute([$firstName, $lastName, $data['admin_email'], $data['admin_pass']]);

        // Write system settings
        $settingStmt = $pdo->prepare(
            "INSERT INTO system_settings (setting_key, setting_value)
             VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?"
        );
        $settings = [
            ['app_name',          $data['company_name'] . ' ERP'],
            ['company_name',      $data['company_name']],
            ['app_url',           $data['app_url']],
            ['timezone',          $data['timezone']],
            ['fiscal_year_start', $data['fiscal_start'] . '-01'],
            ['currency_code',     $data['currency']],
            ['currency_symbol',   currencySymbol($data['currency'])],
            ['invoice_prefix',    'INV'],
            ['app_installed',     '1'],
            ['app_version',       KORE_VERSION],
        ];
        foreach ($settings as $s) {
            $settingStmt->execute([$s[0], $s[1], $s[1]]);
        }

        // Generate and write .env file
        $env = generateEnvFile($data);
        if (!file_put_contents(ENV_FILE, $env)) {
            return ['Could not write .env file. Check directory permissions.'];
        }

        // Create storage directories
        $dirs = [
            dirname(INSTALL_LOCK_FILE),
            __DIR__ . '/../storage/framework/sessions',
            __DIR__ . '/../storage/framework/cache/data',
            __DIR__ . '/../storage/framework/views',
            __DIR__ . '/../storage/logs',
            __DIR__ . '/../bootstrap/cache',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        // Write install lock
        file_put_contents(INSTALL_LOCK_FILE, date('Y-m-d H:i:s') . "\nKore ERP v" . KORE_VERSION);

        unset($_SESSION['install']);

    } catch (Exception $e) {
        return ['Installation error: ' . $e->getMessage()];
    }

    return [];
}

function generateEnvFile(array $data): string
{
    $key = 'base64:' . base64_encode(random_bytes(32));
    $url = rtrim($data['app_url'], '/');
    $tz  = $data['timezone'];
    $db  = $data['db_name'];
    $dbh = $data['db_host'];
    $dbp = $data['db_port'];
    $dbu = $data['db_user'];
    $dbpw = $data['db_pass'];
    $curr = $data['currency'];
    $sym  = currencySymbol($curr);

    return <<<ENV
APP_NAME="Kore ERP"
APP_ENV=production
APP_KEY={$key}
APP_DEBUG=false
APP_URL={$url}
APP_TIMEZONE={$tz}

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST={$dbh}
DB_PORT={$dbp}
DB_DATABASE={$db}
DB_USERNAME={$dbu}
DB_PASSWORD={$dbpw}

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@company.com"
MAIL_FROM_NAME="Kore ERP"

KORE_CURRENCY_SYMBOL={$sym}
KORE_CURRENCY_CODE={$curr}
KORE_INVOICE_PREFIX=INV
KORE_FISCAL_YEAR_START=01-01
ENV;
}

function currencySymbol(string $code): string
{
    return match(strtoupper($code)) {
        'USD' => '$', 'EUR' => '€', 'GBP' => '£',
        'AUD' => 'A$', 'CAD' => 'C$', 'ZAR' => 'R',
        'NZD' => 'NZ$', 'SGD' => 'S$', 'JPY' => '¥',
        default => $code,
    };
}

// ─────────────────────────────────────────────
// REQUIREMENTS CHECK
// ─────────────────────────────────────────────
function checkRequirements(): array
{
    global $required_extensions;
    $results = [];

    $results['php'] = [
        'label' => 'PHP Version &ge; ' . MIN_PHP_VERSION,
        'pass'  => version_compare(PHP_VERSION, MIN_PHP_VERSION, '>='),
        'value' => PHP_VERSION,
    ];

    $results['pdo_mysql'] = [
        'label' => 'PDO MySQL Driver',
        'pass'  => in_array('mysql', PDO::getAvailableDrivers()),
        'value' => in_array('mysql', PDO::getAvailableDrivers()) ? 'Available' : 'MISSING',
    ];

    $results['storage_writable'] = [
        'label' => 'storage/ directory writable',
        'pass'  => is_writable(__DIR__ . '/../storage') || !file_exists(__DIR__ . '/../storage'),
        'value' => '',
    ];

    $results['env_writable'] = [
        'label' => '.env file writable',
        'pass'  => is_writable(__DIR__ . '/..') || !file_exists(ENV_FILE),
        'value' => '',
    ];

    $results['vendor_exists'] = [
        'label' => 'vendor/ directory exists (composer install run)',
        'pass'  => is_dir(__DIR__ . '/../vendor'),
        'value' => is_dir(__DIR__ . '/../vendor') ? 'Found' : 'MISSING — run composer install',
    ];

    foreach ($required_extensions as $ext) {
        $results['ext_' . $ext] = [
            'label' => "PHP Extension: {$ext}",
            'pass'  => extension_loaded($ext),
            'value' => extension_loaded($ext) ? 'Loaded' : 'MISSING',
        ];
    }

    return $results;
}

$allPassed    = true;
$requirements = [];
if ($step === 1) {
    $requirements = checkRequirements();
    foreach ($requirements as $r) {
        if (!$r['pass']) { $allPassed = false; break; }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kore ERP — Setup Wizard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-size: 0.92rem; }
        .wizard-wrap { max-width: 700px; margin: 50px auto 80px; }
        .step-bar { display: flex; margin-bottom: 2rem; }
        .step-bar .s {
            flex: 1; text-align: center; padding: 8px 2px;
            font-size: 0.75rem; border-bottom: 3px solid #dee2e6; color: #9ca3af;
        }
        .step-bar .s.active { border-bottom-color: #0d6efd; color: #0d6efd; font-weight: 700; }
        .step-bar .s.done   { border-bottom-color: #198754; color: #198754; }
        .kore-logo { font-size: 1.6rem; font-weight: 800; color: #0d6efd; letter-spacing: -1px; }
        .kore-logo span { color: #9ca3af; font-weight: 300; }
        .req-row { display:flex; align-items:center; padding:7px 0; border-bottom:1px solid #f3f4f6; font-size:0.85rem; }
        .req-row:last-child { border-bottom:none; }
    </style>
</head>
<body>
<div class="container wizard-wrap">
    <div class="card shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex align-items-center justify-content-between">
                <div class="kore-logo">KORE <span>ERP</span></div>
                <small class="text-muted">Setup Wizard &middot; v<?= KORE_VERSION ?></small>
            </div>
        </div>

        <div class="card-body p-4">

            <!-- Step Indicator -->
            <div class="step-bar">
                <?php
                $stepLabels = ['Requirements', 'Database', 'Admin', 'Settings', 'Install', 'Complete'];
                foreach ($stepLabels as $i => $label):
                    $n   = $i + 1;
                    $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
                ?>
                <div class="s <?= $cls ?>">
                    <?php if ($n < $step): ?><i class="bi bi-check-circle-fill"></i><?php else: ?><?= $n ?>.<?php endif; ?>
                    <?= $label ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Error Alert -->
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1">
                    <?php foreach ($errors as $e): ?>
                    <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- ── STEP 1: Requirements ── -->
            <?php if ($step === 1): ?>
            <h5 class="mb-1"><i class="bi bi-clipboard-check me-2"></i>System Requirements</h5>
            <p class="text-muted mb-3" style="font-size:0.82rem;">Verifying your server meets all requirements to run Kore ERP.</p>

            <div class="mb-4">
                <?php foreach ($requirements as $req): ?>
                <div class="req-row">
                    <span class="me-2 <?= $req['pass'] ? 'text-success' : 'text-danger' ?>">
                        <i class="bi <?= $req['pass'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i>
                    </span>
                    <span class="flex-grow-1"><?= $req['label'] ?></span>
                    <small class="text-muted ms-2"><?= htmlspecialchars($req['value']) ?></small>
                </div>
                <?php endforeach; ?>
            </div>

            <form method="POST">
                <button type="submit" class="btn btn-primary" <?= !$allPassed ? 'disabled' : '' ?>>
                    Continue <i class="bi bi-arrow-right ms-1"></i>
                </button>
                <?php if (!$allPassed): ?>
                <p class="text-danger mt-2 small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Resolve all failed requirements before continuing.
                </p>
                <?php endif; ?>
            </form>

            <!-- ── STEP 2: Database ── -->
            <?php elseif ($step === 2): ?>
            <h5 class="mb-1"><i class="bi bi-database me-2"></i>Database Configuration</h5>
            <p class="text-muted mb-3" style="font-size:0.82rem;">Enter your MySQL/MariaDB connection details. The database will be created if it doesn't exist.</p>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-500">Database Host</label>
                        <input type="text" name="db_host" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($_POST['db_host'] ?? ($_SESSION['install']['db_host'] ?? 'localhost')) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-500">Port</label>
                        <input type="text" name="db_port" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($_POST['db_port'] ?? ($_SESSION['install']['db_port'] ?? '3306')) ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-500">Database Name</label>
                        <input type="text" name="db_name" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($_POST['db_name'] ?? ($_SESSION['install']['db_name'] ?? 'kore_erp')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-500">Username</label>
                        <input type="text" name="db_user" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($_POST['db_user'] ?? ($_SESSION['install']['db_user'] ?? '')) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-500">Password</label>
                        <input type="password" name="db_pass" class="form-control form-control-sm">
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <a href="?step=1" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    <button type="submit" class="btn btn-sm btn-primary">Test &amp; Continue <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </form>

            <!-- ── STEP 3: Admin Account ── -->
            <?php elseif ($step === 3): ?>
            <h5 class="mb-1"><i class="bi bi-person-gear me-2"></i>Administrator Account</h5>
            <p class="text-muted mb-3" style="font-size:0.82rem;">Create the primary administrator login for Kore ERP.</p>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-500">Full Name</label>
                        <input type="text" name="admin_name" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-500">Email Address</label>
                        <input type="email" name="admin_email" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-500">Password</label>
                        <input type="password" name="admin_pass" class="form-control form-control-sm" minlength="8" required>
                        <div class="form-text">Minimum 8 characters.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-500">Confirm Password</label>
                        <input type="password" name="admin_pass2" class="form-control form-control-sm" required>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <a href="?step=2" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    <button type="submit" class="btn btn-sm btn-primary">Continue <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </form>

            <!-- ── STEP 4: Company Settings ── -->
            <?php elseif ($step === 4): ?>
            <h5 class="mb-1"><i class="bi bi-building me-2"></i>Company &amp; Application Settings</h5>
            <p class="text-muted mb-3" style="font-size:0.82rem;">Configure your organization's details and regional preferences.</p>

            <form method="POST">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-500">Company Name</label>
                        <input type="text" name="company_name" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-500">Application URL</label>
                        <input type="url" name="app_url" class="form-control form-control-sm"
                            value="<?= htmlspecialchars($_POST['app_url'] ?? 'http://localhost') ?>" required>
                        <div class="form-text">e.g. http://192.168.1.100 or https://erp.company.com</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-500">Timezone</label>
                        <select name="timezone" class="form-select form-select-sm">
                            <?php
                            $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                            $selected  = $_POST['timezone'] ?? 'UTC';
                            foreach ($timezones as $tz):
                            ?>
                            <option value="<?= $tz ?>" <?= $tz === $selected ? 'selected' : '' ?>><?= $tz ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-500">Currency</label>
                        <select name="currency" class="form-select form-select-sm">
                            <?php
                            $currencies = ['USD'=>'USD — US Dollar','EUR'=>'EUR — Euro','GBP'=>'GBP — British Pound',
                                'AUD'=>'AUD — Australian Dollar','CAD'=>'CAD — Canadian Dollar',
                                'ZAR'=>'ZAR — South African Rand','NZD'=>'NZD — New Zealand Dollar',
                                'SGD'=>'SGD — Singapore Dollar','JPY'=>'JPY — Japanese Yen'];
                            $selCurr = $_POST['currency'] ?? 'USD';
                            foreach ($currencies as $v => $label):
                            ?>
                            <option value="<?= $v ?>" <?= $v === $selCurr ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-500">Fiscal Year Start</label>
                        <select name="fiscal_start" class="form-select form-select-sm">
                            <?php
                            $months = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April',
                                       '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
                                       '09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
                            $selM = $_POST['fiscal_start'] ?? '01';
                            foreach ($months as $v => $m):
                            ?>
                            <option value="<?= $v ?>" <?= $v === $selM ? 'selected' : '' ?>><?= $m ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mt-4 d-flex gap-2">
                    <a href="?step=3" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    <button type="submit" class="btn btn-sm btn-primary">Continue <i class="bi bi-arrow-right ms-1"></i></button>
                </div>
            </form>

            <!-- ── STEP 5: Install ── -->
            <?php elseif ($step === 5): ?>
            <h5 class="mb-1"><i class="bi bi-play-circle me-2"></i>Ready to Install</h5>
            <p class="text-muted mb-3" style="font-size:0.82rem;">Review your settings below, then click Install to create the database and configure Kore ERP.</p>

            <div class="alert alert-light border mb-4" style="font-size:0.85rem;">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr><td class="text-muted" style="width:150px;">Database</td>
                            <td><code><?= htmlspecialchars($_SESSION['install']['db_name'] ?? '?') ?></code>
                                on <code><?= htmlspecialchars($_SESSION['install']['db_host'] ?? '?') ?>:<?= htmlspecialchars($_SESSION['install']['db_port'] ?? '3306') ?></code></td></tr>
                        <tr><td class="text-muted">Admin Email</td>
                            <td><?= htmlspecialchars($_SESSION['install']['admin_email'] ?? '?') ?></td></tr>
                        <tr><td class="text-muted">Company</td>
                            <td><?= htmlspecialchars($_SESSION['install']['company_name'] ?? '?') ?></td></tr>
                        <tr><td class="text-muted">App URL</td>
                            <td><?= htmlspecialchars($_SESSION['install']['app_url'] ?? '?') ?></td></tr>
                        <tr><td class="text-muted">Timezone</td>
                            <td><?= htmlspecialchars($_SESSION['install']['timezone'] ?? '?') ?></td></tr>
                        <tr><td class="text-muted">Currency</td>
                            <td><?= htmlspecialchars($_SESSION['install']['currency'] ?? 'USD') ?></td></tr>
                    </tbody>
                </table>
            </div>

            <form method="POST">
                <div class="d-flex gap-2">
                    <a href="?step=4" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-play-fill me-1"></i>Install Kore ERP
                    </button>
                </div>
            </form>

            <!-- ── STEP 6: Complete ── -->
            <?php elseif ($step === 6): ?>
            <div class="text-center py-4">
                <div class="text-success mb-3" style="font-size:4rem;"><i class="bi bi-check-circle-fill"></i></div>
                <h4 class="mb-2">Installation Complete!</h4>
                <p class="text-muted mb-4">Kore ERP has been successfully installed and is ready to use.</p>

                <div class="alert alert-warning text-start mb-4" style="font-size:0.82rem;">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Security Notice:</strong> Delete the <code>/install</code> directory from your
                    server immediately to prevent unauthorized re-installation.
                    <br><code>Remove-Item -Recurse -Force "<?= htmlspecialchars(realpath(__DIR__)) ?>"</code>
                </div>

                <a href="../" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Launch Kore ERP
                </a>
            </div>
            <?php endif; ?>

        </div>

        <div class="card-footer bg-white text-center py-2">
            <small class="text-muted">Kore ERP v<?= KORE_VERSION ?> &mdash; Setup Wizard</small>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
