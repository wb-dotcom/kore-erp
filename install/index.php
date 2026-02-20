<?php
/**
 * KORE ERP - Setup Wizard
 * File: install/index.php
 *
 * This wizard guides the administrator through first-time installation.
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
                  <a href="../public/index.php" style="color:#0d6efd;">Go to Application</a>
              </body></html>');
}

// ─────────────────────────────────────────────
// STEP ROUTING
// ─────────────────────────────────────────────
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$step = max(1, min(6, $step));

// Handle POST submissions
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      switch ($step) {
        case 1:
                    // Requirements check - just advance
                    header('Location: ?step=2');
                    exit;

        case 2:
                    // Database configuration
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
                                                            // Try to create database if it doesn't exist
                                                            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                                                            // Store in session
                                                            $_SESSION['install']['db_host'] = $db_host;
                                                            $_SESSION['install']['db_port'] = $db_port;
                                                            $_SESSION['install']['db_name'] = $db_name;
                                                            $_SESSION['install']['db_user'] = $db_user;
                                                            $_SESSION['install']['db_pass'] = $db_pass;
                                                            header('Location: ?step=3');
                                                            exit;
                                      } catch (PDOException $e) {
                                                            $errors[] = 'Database connection failed: ' . $e->getMessage();
                                      }
                    }
                    break;

        case 3:
                    // Admin account
                    $admin_name  = trim($_POST['admin_name'] ?? '');
                    $admin_email = trim($_POST['admin_email'] ?? '');
                    $admin_pass  = $_POST['admin_pass'] ?? '';
                    $admin_pass2 = $_POST['admin_pass2'] ?? '';

                    if (empty($admin_name))  { $errors[] = 'Full name is required.'; }
                    if (empty($admin_email) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Valid email is required.'; }
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
                    // Company settings
                    $company_name = trim($_POST['company_name'] ?? '');
                    $timezone     = trim($_POST['timezone'] ?? 'America/New_York');
                    $fiscal_start = trim($_POST['fiscal_start'] ?? '01');

                    if (empty($company_name)) { $errors[] = 'Company name is required.'; }

                    if (empty($errors)) {
                                      $_SESSION['install']['company_name'] = $company_name;
                                      $_SESSION['install']['timezone']     = $timezone;
                                      $_SESSION['install']['fiscal_start'] = $fiscal_start;
                                      header('Location: ?step=5');
                                      exit;
                    }
                    break;

        case 5:
                    // Run installation
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
function runInstallation(): array {
      $errors = [];
      $data = $_SESSION['install'] ?? [];

    if (empty($data['db_host'])) {
              return ['Session data missing. Please restart the wizard.'];
    }

    try {
              $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};dbname={$data['db_name']};charset=utf8mb4";
              $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

              // Run schema (from DATABASE_SCHEMA - condensed for installer)
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
                                              $pdo->exec($sql);
                            }
              }

              // Seed default roles
              $pdo->exec("INSERT IGNORE INTO roles (name, description) VALUES
                          ('Admin', 'Full system access'),
                                      ('Manager', 'Project and team management'),
                                                  ('Employee', 'Standard employee access')");

              // Seed default proposal statuses
              $pdo->exec("INSERT IGNORE INTO proposal_statuses (name) VALUES ('Approved'),('Pending'),('Lost')");

              // Seed default project types
              $pdo->exec("INSERT IGNORE INTO project_types (name, is_billable) VALUES ('Billable', 1),('Non-Billable', 0)");

              // Seed default project statuses
              $pdo->exec("INSERT IGNORE INTO project_statuses (name) VALUES ('Active'),('Closed'),('On Hold')");

              // Create admin user
              $nameParts = explode(' ', $data['admin_name'], 2);
              $firstName = $nameParts[0];
              $lastName  = $nameParts[1] ?? '';
              $stmt = $pdo->prepare("INSERT INTO users (first_name, last_name, email, password, role_id, is_active) 
                          SELECT ?, ?, ?, ?, id, 1 FROM roles WHERE name = 'Admin' LIMIT 1");
              $stmt->execute([$firstName, $lastName, $data['admin_email'], $data['admin_pass']]);

              // Write system settings
              $settings = [
                            ['company_name',    $data['company_name']],
                            ['timezone',        $data['timezone']],
                            ['fiscal_year_start', $data['fiscal_start']],
                            ['app_installed',   '1'],
                            ['app_version',     KORE_VERSION],
                        ];
              $settingStmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
              foreach ($settings as $s) {
                            $settingStmt->execute([$s[0], $s[1], $s[1]]);
              }

              // Write .env file
              $env = generateEnvFile($data);
              file_put_contents(ENV_FILE, $env);

              // Write install lock
              if (!is_dir(dirname(INSTALL_LOCK_FILE))) {
                            mkdir(dirname(INSTALL_LOCK_FILE), 0755, true);
              }
              file_put_contents(INSTALL_LOCK_FILE, date('Y-m-d H:i:s'));

              // Clear session
              unset($_SESSION['install']);

    } catch (Exception $e) {
              $errors[] = 'Installation error: ' . $e->getMessage();
    }

    return $errors;
}

function generateEnvFile(array $data): string {
      $key = 'base64:' . base64_encode(random_bytes(32));
      return "APP_NAME=\"Kore ERP\"
      APP_ENV=production
      APP_KEY={$key}
      APP_DEBUG=false
      APP_URL=http://localhost

      LOG_CHANNEL=stack

      DB_CONNECTION=mysql
      DB_HOST={$data['db_host']}
      DB_PORT={$data['db_port']}
      DB_DATABASE={$data['db_name']}
      DB_USERNAME={$data['db_user']}
      DB_PASSWORD={$data['db_pass']}

      BROADCAST_DRIVER=log
      CACHE_DRIVER=file
      QUEUE_CONNECTION=sync
      SESSION_DRIVER=file
      SESSION_LIFETIME=120

      MAIL_MAILER=smtp
      MAIL_HOST=
      MAIL_PORT=587
      MAIL_USERNAME=
      MAIL_PASSWORD=
      MAIL_ENCRYPTION=tls
      MAIL_FROM_ADDRESS=\"noreply@yourdomain.com\"
      MAIL_FROM_NAME=\"Kore ERP\"
      ";
}

// ─────────────────────────────────────────────
// CHECK REQUIREMENTS
// ─────────────────────────────────────────────
function checkRequirements(): array {
      global $required_extensions;
      $results = [];

    $results['php_version'] = [
              'label' => 'PHP Version (>= ' . MIN_PHP_VERSION . ')',
              'pass'  => version_compare(PHP_VERSION, MIN_PHP_VERSION, '>='),
              'value' => PHP_VERSION,
          ];

    $results['writable_storage'] = [
              'label' => 'storage/ directory writable',
              'pass'  => is_writable(__DIR__ . '/../storage') || !file_exists(__DIR__ . '/../storage'),
              'value' => '',
          ];

    $results['writable_root'] = [
              'label' => '.env file writable',
              'pass'  => is_writable(__DIR__ . '/..') || !file_exists(__DIR__ . '/../.env'),
              'value' => '',
          ];

    foreach ($required_extensions as $ext) {
              $results['ext_' . $ext] = [
                            'label' => "PHP Extension: {$ext}",
                            'pass'  => extension_loaded($ext),
                            'value' => extension_loaded($ext) ? 'Loaded' : 'MISSING',
                        ];
    }

    $results['pdo_mysql'] = [
              'label' => 'PDO MySQL Driver',
              'pass'  => in_array('mysql', PDO::getAvailableDrivers()),
              'value' => in_array('mysql', PDO::getAvailableDrivers()) ? 'Available' : 'MISSING',
          ];

    return $results;
}

$allPassed = true;
$requirements = [];
if ($step === 1) {
      $requirements = checkRequirements();
      foreach ($requirements as $r) {
                if (!$r['pass']) { $allPassed = false; break; }
      }
}

// ─────────────────────────────────────────────
// HTML OUTPUT
// ─────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Kore ERP — Setup Wizard</title>title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
              body { background: #f0f2f5; }
              .wizard-card { max-width: 720px; margin: 60px auto; }
              .step-indicator { display: flex; justify-content: space-between; margin-bottom: 2rem; }
              .step-indicator .step {
                            flex: 1; text-align: center; padding: 8px 4px;
                            font-size: 0.8rem; border-bottom: 3px solid #dee2e6; color: #6c757d;
              }
              .step-indicator .step.active { border-bottom-color: #0d6efd; color: #0d6efd; font-weight: 600; }
              .step-indicator .step.done   { border-bottom-color: #198754; color: #198754; }
              .req-row { display: flex; align-items: center; padding: 6px 0; border-bottom: 1px solid #f0f0f0; }
              .req-row:last-child { border-bottom: none; }
              .kore-logo { font-size: 1.8rem; font-weight: 700; color: #0d6efd; letter-spacing: -1px; }
              .kore-logo span { color: #6c757d; font-weight: 300; }
          </style>
</head>head>
  <body>
    <div class="container wizard-card">
          <div class="card shadow-sm">
                    <div class="card-header bg-white py-3">
                                  <div class="d-flex align-items-center justify-content-between">
                                                    <div class="kore-logo">KORE <span>ERP</span>span></div>div>
                                                    <small class="text-muted">Setup Wizard &middot; v<?= KORE_VERSION ?></small>small>
                                  </div>div>
                    </div>div>
                    <div class="card-body p-4">

                                  <!-- Step Indicator -->
                                  <div class="step-indicator">
                                                    <?php
                $steps = ['Requirements', 'Database', 'Admin Account', 'Company', 'Install', 'Complete'];
                foreach ($steps as $i => $label):
                                      $num = $i + 1;
                                      $cls = $num < $step ? 'done' : ($num === $step ? 'active' : '');
                                  ?>
                                  <div class="step <?= $cls ?>">
                                                        <?php if ($num < $step): ?><i class="bi bi-check-circle-fill"></i>i><?php else: ?><?= $num ?>.<?php endif; ?>
                                      <?= $label ?>
                                  </div>div>
                                                    <?php endforeach; ?>
                                  </div>div>

                                  <!-- Errors -->
                                  <?php if (!empty($errors)): ?>
                              <div class="alert alert-danger">
                                                <strong>Please fix the following:</strong>strong>
                                                <ul class="mb-0 mt-1">
                                                                      <?php foreach ($errors as $e): ?>
                                      <li><?= htmlspecialchars($e) ?></li>
                                                                    <?php endforeach; ?>
                                                </ul>ul>
                              </div>div>
                                <?php endif; ?>

                                <!-- ── STEP 1: Requirements ── -->
                                <?php if ($step === 1): ?>
                              <h4 class="mb-3"><i class="bi bi-clipboard-check me-2"></i>i>System Requirements</h4>h4>
                                <p class="text-muted mb-3">Checking that your server meets all requirements to run Kore ERP.</p>p>
                    
                                <div class="list-group mb-4">
                                                  <?php foreach ($requirements as $req): ?>
                                  <div class="req-row list-group-item">
                                                        <span class="me-3 <?= $req['pass'] ? 'text-success' : 'text-danger' ?>">
                                                                                  <i class="bi <?= $req['pass'] ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?>"></i>i>
                                                        </span>span>
                                                        <span class="flex-grow-1"><?= $req['label'] ?></span>
                                                        <small class="text-muted"><?= htmlspecialchars($req['value']) ?></small>
                                  </div>div>
                                                  <?php endforeach; ?>
                                </div>div>

                                  <form method="POST">
                                                    <button type="submit" class="btn btn-primary" <?= !$allPassed ? 'disabled' : '' ?>>
                                                                          Continue <i class="bi bi-arrow-right ms-1"></i>i>
                                                    </button>button>
                                                    <?php if (!$allPassed): ?>
                                  <p class="text-danger mt-2 small">Please resolve the failed requirements before continuing.</p>p>
                                                    <?php endif; ?>
                                  </form>form>

                                  <!-- ── STEP 2: Database ── -->
                                  <?php elseif ($step === 2): ?>
                              <h4 class="mb-3"><i class="bi bi-database me-2"></i>i>Database Configuration</h4>h4>
                                  <p class="text-muted mb-3">Enter your MySQL database connection details.</p>p>
                                  <form method="POST">
                                                    <div class="row g-3">
                                                                          <div class="col-md-8">
                                                                                                    <label class="form-label">Database Host</label>label>
                                                                                                    <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
                                                                          </div>div>
                                                                          <div class="col-md-4">
                                                                                                    <label class="form-label">Port</label>label>
                                                                                                    <input type="text" name="db_port" class="form-control" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>" required>
                                                                          </div>div>
                                                                          <div class="col-12">
                                                                                                    <label class="form-label">Database Name</label>label>
                                                                                                    <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars($_POST['db_name'] ?? 'kore_erp') ?>" required>
                                                                                                    <div class="form-text">The database will be created if it doesn't exist.</div>div>
                                                                          </div>div>
                                                                          <div class="col-md-6">
                                                                                                    <label class="form-label">Database Username</label>label>
                                                                                                    <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
                                                                          </div>div>
                                                                          <div class="col-md-6">
                                                                                                    <label class="form-label">Database Password</label>label>
                                                                                                    <input type="password" name="db_pass" class="form-control" value="">
                                                                          </div>div>
                                                    </div>div>
                                                    <div class="mt-4 d-flex gap-2">
                                                                          <a href="?step=1" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>i>Back</a>a>
                                                                          <button type="submit" class="btn btn-primary">Test & Continue <i class="bi bi-arrow-right ms-1"></i>i></button>button>
                                                    </div>div>
                                  </form>form>

                                  <!-- ── STEP 3: Admin Account ── -->
                                  <?php elseif ($step === 3): ?>
                              <h4 class="mb-3"><i class="bi bi-person-gear me-2"></i>i>Administrator Account</h4>h4>
                                  <p class="text-muted mb-3">Create the primary administrator account for Kore ERP.</p>p>
                                  <form method="POST">
                                                    <div class="row g-3">
                                                                          <div class="col-12">
                                                                                                    <label class="form-label">Full Name</label>label>
                                                                                                    <input type="text" name="admin_name" class="form-control" value="<?= htmlspecialchars($_POST['admin_name'] ?? '') ?>" required>
                                                                          </div>div>
                                                                          <div class="col-12">
                                                                                                    <label class="form-label">Email Address</label>label>
                                                                                                    <input type="email" name="admin_email" class="form-control" value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" required>
                                                                          </div>div>
                                                                          <div class="col-md-6">
                                                                                                    <label class="form-label">Password</label>label>
                                                                                                    <input type="password" name="admin_pass" class="form-control" minlength="8" required>
                                                                                                    <div class="form-text">Minimum 8 characters.</div>div>
                                                                          </div>div>
                                                                          <div class="col-md-6">
                                                                                                    <label class="form-label">Confirm Password</label>label>
                                                                                                    <input type="password" name="admin_pass2" class="form-control" required>
                                                                          </div>div>
                                                    </div>div>
                                                    <div class="mt-4 d-flex gap-2">
                                                                          <a href="?step=2" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>i>Back</a>a>
                                                                          <button type="submit" class="btn btn-primary">Continue <i class="bi bi-arrow-right ms-1"></i>i></button>button>
                                                    </div>div>
                                  </form>form>

                                  <!-- ── STEP 4: Company Settings ── -->
                                  <?php elseif ($step === 4): ?>
                              <h4 class="mb-3"><i class="bi bi-building me-2"></i>i>Company Settings</h4>h4>
                                  <p class="text-muted mb-3">Configure your organization's basic information.</p>p>
                                  <form method="POST">
                                                    <div class="row g-3">
                                                                          <div class="col-12">
                                                                                                    <label class="form-label">Company Name</label>label>
                                                                                                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($_POST['company_name'] ?? '') ?>" required>
                                                                          </div>div>
                                                                          <div class="col-md-8">
                                                                                                    <label class="form-label">Timezone</label>label>
                                                                                                    <select name="timezone" class="form-select">
                                                                                                                                  <?php
                                              $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
                                              $selected  = $_POST['timezone'] ?? 'America/New_York';
                                              foreach ($timezones as $tz):
                                                                            ?>
                                                                            <option value="<?= $tz ?>" <?= $tz === $selected ? 'selected' : '' ?>><?= $tz ?></option>
                                                                                                                                  <?php endforeach; ?>
                                                                                                    </select>select>
                                                                          </div>div>
                                                                          <div class="col-md-4">
                                                                                                    <label class="form-label">Fiscal Year Start (Month)</label>label>
                                                                                                    <select name="fiscal_start" class="form-select">
                                                                                                                                  <?php
                                              $months = ['01'=>'January','02'=>'February','03'=>'March','04'=>'April',
                                                                                       '05'=>'May','06'=>'June','07'=>'July','08'=>'August',
                                                                                       '09'=>'September','10'=>'October','11'=>'November','12'=>'December'];
                                              $selMonth = $_POST['fiscal_start'] ?? '01';
                                              foreach ($months as $v => $m):
                                                                            ?>
                                                                            <option value="<?= $v ?>" <?= $v === $selMonth ? 'selected' : '' ?>><?= $m ?></option>
                                                                                                                                  <?php endforeach; ?>
                                                                                                    </select>select>
                                                                          </div>div>
                                                    </div>div>
                                                    <div class="mt-4 d-flex gap-2">
                                                                          <a href="?step=3" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>i>Back</a>a>
                                                                          <button type="submit" class="btn btn-primary">Continue <i class="bi bi-arrow-right ms-1"></i>i></button>button>
                                                    </div>div>
                                  </form>form>

                                  <!-- ── STEP 5: Install ── -->
                                  <?php elseif ($step === 5): ?>
                              <h4 class="mb-3"><i class="bi bi-gear-wide-connected me-2"></i>i>Ready to Install</h4>h4>
                                  <p class="text-muted mb-3">Kore ERP is ready to be installed. This will create the database tables and seed default data.</p>p>
                                  <div class="alert alert-info">
                                                    <strong>Summary:</strong>strong>
                                                    <ul class="mb-0 mt-1">
                                                                          <li>Database: <code><?= htmlspecialchars($_SESSION['install']['db_name'] ?? 'N/A') ?></code> on <code><?= htmlspecialchars($_SESSION['install']['db_host'] ?? 'N/A') ?></code></li>li>
                                                                        <li>Admin: <?= htmlspecialchars($_SESSION['install']['admin_email'] ?? 'N/A') ?></li>li>
                                                                        <li>Company: <?= htmlspecialchars($_SESSION['install']['company_name'] ?? 'N/A') ?></li>li>
                                                                        <li>Timezone: <?= htmlspecialchars($_SESSION['install']['timezone'] ?? 'N/A') ?></li>li>
                                                    </ul>ul>
                                  </div>div>
                                <form method="POST">
                                                  <div class="d-flex gap-2">
                                                                        <a href="?step=4" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>i>Back</a>a>
                                                                        <button type="submit" class="btn btn-success">
                                                                                                  <i class="bi bi-play-fill me-1"></i>i>Install Kore ERP
                                                                        </button>button>
                                                  </div>div>
                                </form>form>

                                  <!-- ── STEP 6: Complete ── -->
                                  <?php elseif ($step === 6): ?>
                              <div class="text-center py-3">
                                                <div class="mb-3 text-success" style="font-size:4rem;"><i class="bi bi-check-circle-fill"></i>i></div>div>
                                                <h3 class="mb-2">Installation Complete!</h3>h3>
                                                <p class="text-muted mb-4">Kore ERP has been successfully installed and configured.</p>p>
                                                <div class="alert alert-warning text-start">
                                                                      <i class="bi bi-exclamation-triangle-fill me-2"></i>i>
                                                                      <strong>Security Notice:</strong>strong> Please delete the <code>/install</code>code> directory from your server immediately to prevent unauthorized re-installation.
                                                </div>div>
                                                <a href="../public/index.php" class="btn btn-primary btn-lg">
                                                                      <i class="bi bi-box-arrow-in-right me-2"></i>i>Launch Kore ERP
                                                </a>a>
                              </div>div>
                                  <?php endif; ?>

                    </div>div>
                    <div class="card-footer bg-white text-center">
                                  <small class="text-muted">Kore ERP v<?= KORE_VERSION ?> &mdash; Setup Wizard</small>small>
                    </div>div>
          </div>div>
    </div>div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  </body>body>
</html>html>

                                </li>
                                </li>
    </style></title>
</head>
