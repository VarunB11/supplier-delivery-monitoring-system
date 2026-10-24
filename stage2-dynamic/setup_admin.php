<?php
/**
 * SDPMS - Safe Administrator Setup & Account Initializer
 * Generates verified bcrypt hashes using native PHP password_hash()
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/functions.php';

$message = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? 'admin@procuretrack.example.com');
    $password = $_POST['password'] ?? 'password123';
    $fullName = trim($_POST['full_name'] ?? 'System Administrator');
    $username = trim($_POST['username'] ?? 'admin');

    if (empty($email) || empty($password)) {
        $error = 'Email and password cannot be empty.';
    } else {
        try {
            $db = Database::getConnection();
            $hash = password_hash($password, PASSWORD_BCRYPT);

            // Check if user exists
            $check = $db->prepare("SELECT id FROM `users` WHERE `email` = :email OR `username` = :username LIMIT 1");
            $check->execute([':email' => $email, ':username' => $username]);
            $existing = $check->fetch();

            if ($existing) {
                // Update existing
                $update = $db->prepare(
                    "UPDATE `users` SET `password_hash` = :hash, `full_name` = :name, `role` = 'Admin', `status` = 'Active' WHERE `id` = :id"
                );
                $update->execute([
                    ':hash' => $hash,
                    ':name' => $fullName,
                    ':id'   => $existing['id']
                ]);
                $message = "Admin user updated successfully! You can now log in with email: <strong>{$email}</strong> and your password.";
            } else {
                // Insert new admin
                $insert = $db->prepare(
                    "INSERT INTO `users` (`username`, `email`, `password_hash`, `full_name`, `role`, `status`)
                     VALUES (:username, :email, :hash, :name, 'Admin', 'Active')"
                );
                $insert->execute([
                    ':username' => $username,
                    ':email'    => $email,
                    ':hash'     => $hash,
                    ':name'     => $fullName
                ]);
                $message = "Admin user created successfully! You can now log in with email: <strong>{$email}</strong> and your password.";
            }
        } catch (Exception $e) {
            $error = 'Database setup error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Account Setup | <?= sanitize_output(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <style>
    body { background: #0f172a; color: #f8fafc; padding: 2rem; display: flex; align-items: center; justify-content: center; min-height: 90vh; }
    .setup-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 2rem; max-width: 500px; width: 100%; box-shadow: var(--shadow-xl); }
  </style>
</head>
<body>
  <div class="setup-card">
    <h2 style="font-size: 1.3rem; font-weight: 700; color: #38bdf8; margin-bottom: 0.5rem;">ProcureTrack Admin Setup</h2>
    <p style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 1.5rem;">Initialize or reset the default administrator account in your MySQL database.</p>

    <?php if ($message): ?>
      <div style="background: rgba(16, 185, 129, 0.15); border: 1px solid #10b981; color: #34d399; padding: 1rem; border-radius: 6px; font-size: 0.85rem; margin-bottom: 1.5rem;">
        <?= $message ?>
        <div style="margin-top: 0.85rem;">
          <a href="login.php" class="btn btn-primary btn-sm">Proceed to Login</a>
        </div>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #f87171; padding: 1rem; border-radius: 6px; font-size: 0.85rem; margin-bottom: 1.5rem;">
        <?= sanitize_output($error) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label" style="color:#cbd5e1;">Admin Username</label>
        <input type="text" name="username" class="form-control" value="admin" required>
      </div>

      <div class="form-group">
        <label class="form-label" style="color:#cbd5e1;">Full Name</label>
        <input type="text" name="full_name" class="form-control" value="System Administrator" required>
      </div>

      <div class="form-group">
        <label class="form-label" style="color:#cbd5e1;">Admin Email Address</label>
        <input type="email" name="email" class="form-control" value="admin@procuretrack.example.com" required>
      </div>

      <div class="form-group" style="margin-bottom: 1.5rem;">
        <label class="form-label" style="color:#cbd5e1;">New Password</label>
        <input type="text" name="password" class="form-control" value="password123" required>
        <div class="form-help" style="color:#94a3b8;">Will be hashed using native PHP password_hash() (Bcrypt)</div>
      </div>

      <button type="submit" class="btn btn-primary" style="width: 100%;">
        Save Administrator Account
      </button>

      <div style="margin-top: 1rem; text-align: center;">
        <a href="login.php" style="font-size: 0.85rem; color: #38bdf8;">Return to Login</a>
      </div>
    </form>
  </div>
</body>
</html>
