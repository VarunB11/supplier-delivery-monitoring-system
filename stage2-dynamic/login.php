<?php
/**
 * SDPMS - User Authentication & Login Portal
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already authenticated
if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/dashboard.php');
    exit;
}

$error = null;
$notice = $_GET['notice'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both your email address and password.';
    } else {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM `users` WHERE `email` = :email AND `status` = 'Active' LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Successful verification
                login_user($user);
                log_audit_event($db, 'LOGIN', 'users', (string)$user['id'], 'User signed in successfully.');
                header('Location: ' . BASE_URL . '/dashboard.php');
                exit;
            } else {
                $error = 'Invalid email address or password. Please verify credentials.';
            }
        } catch (Exception $e) {
            $error = 'Database authentication error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | <?= sanitize_output(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    body {
      background: linear-gradient(135deg, var(--navy-950) 0%, var(--navy-900) 50%, var(--navy-850) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 1.5rem;
    }
    .login-container {
      max-width: 440px;
      width: 100%;
    }
    .login-brand {
      text-align: center;
      margin-bottom: 2rem;
    }
    .login-brand .brand-icon {
      margin: 0 auto 0.75rem auto;
      width: 48px;
      height: 48px;
    }
    .login-brand h1 {
      font-size: 1.4rem;
      color: var(--white);
      font-weight: 700;
    }
    .login-brand p {
      font-size: 0.82rem;
      color: var(--slate-400);
      margin-top: 0.25rem;
    }
    .login-card {
      background: var(--white);
      border-radius: var(--border-radius-lg);
      padding: 2.25rem;
      box-shadow: 0 20px 35px -5px rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(255, 255, 255, 0.1);
    }
    .default-credentials-box {
      background: var(--navy-50);
      border: 1px dashed var(--navy-600);
      border-radius: var(--border-radius-sm);
      padding: 0.85rem;
      font-size: 0.78rem;
      margin-top: 1.5rem;
      color: var(--navy-900);
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-brand">
      <div class="brand-icon">
        <svg width="28" height="28" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
      </div>
      <h1><?= sanitize_output(APP_NAME) ?></h1>
      <p><?= sanitize_output(APP_SUBTITLE) ?></p>
    </div>

    <div class="login-card">
      <h2 style="font-size: 1.15rem; font-weight: 700; color: var(--navy-900); margin-bottom: 0.5rem;">Procurement Portal Sign In</h2>
      <p style="font-size: 0.82rem; color: var(--slate-500); margin-bottom: 1.25rem;">Choose a role or enter authorized account credentials:</p>

      <!-- 1-Click Role Switcher -->
      <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:0.5rem; margin-bottom:1.5rem;">
        <button type="button" class="btn btn-outline btn-sm" onclick="setCredentials('admin@procuretrack.example.com', 'password123', this)" style="display:flex; flex-direction:column; padding:0.6rem 0.3rem;">
          <strong style="font-size:0.75rem; color:var(--navy-900);">👑 Admin</strong>
          <span style="font-size:0.68rem; color:var(--slate-500);">Full Access</span>
        </button>
        <button type="button" class="btn btn-outline btn-sm" onclick="setCredentials('buyer@procuretrack.example.com', 'password123', this)" style="display:flex; flex-direction:column; padding:0.6rem 0.3rem;">
          <strong style="font-size:0.75rem; color:var(--navy-900);">👤 Normal User</strong>
          <span style="font-size:0.68rem; color:var(--slate-500);">Purchasing Officer</span>
        </button>
        <button type="button" class="btn btn-outline btn-sm" onclick="setCredentials('auditor@procuretrack.example.com', 'password123', this)" style="display:flex; flex-direction:column; padding:0.6rem 0.3rem;">
          <strong style="font-size:0.75rem; color:var(--navy-900);">🔍 Auditor</strong>
          <span style="font-size:0.68rem; color:var(--slate-500);">Reports Only</span>
        </button>
      </div>

      <?php if ($notice === 'session_required'): ?>
        <div class="alert-box alert-warning">
          <div>Please sign in to access that protected procurement module.</div>
        </div>
      <?php elseif ($notice === 'logged_out'): ?>
        <div class="alert-box alert-info">
          <div>You have been safely signed out.</div>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert-box alert-danger">
          <div><?= sanitize_output($error) ?></div>
        </div>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>/login.php">
        <?= render_csrf_input() ?>

        <div class="form-group">
          <label class="form-label" for="email">Official Email Address</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="admin@procuretrack.example.com" value="<?= sanitize_output($_POST['email'] ?? 'admin@procuretrack.example.com') ?>" required autofocus>
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
          <label class="form-label" for="password">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="••••••••••••" value="password123" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 0.95rem;">
          Sign In to Dashboard
        </button>
      </form>

      <div class="default-credentials-box">
        <div style="font-weight: 700; margin-bottom: 0.35rem;">Configured Demonstration Accounts:</div>
        <div style="margin-bottom:0.25rem;">&bull; <strong>Admin:</strong> <code>admin@procuretrack.example.com</code> / <code>password123</code></div>
        <div style="margin-bottom:0.25rem;">&bull; <strong>Normal User (Buyer):</strong> <code>buyer@procuretrack.example.com</code> / <code>password123</code></div>
        <div>&bull; <strong>Auditor:</strong> <code>auditor@procuretrack.example.com</code> / <code>password123</code></div>
        <div style="margin-top: 0.5rem; font-size: 0.72rem; color: var(--slate-500);">
          Need to initialize or reset custom credentials? Run <a href="setup_admin.php" style="color:var(--primary-600); text-decoration:underline;">setup_admin.php</a>.
        </div>
      </div>
    </div>
  </div>

  <script>
    function setCredentials(email, pass, btn) {
      document.getElementById('email').value = email;
      document.getElementById('password').value = pass;
    }
  </script>
</body>
</html>
