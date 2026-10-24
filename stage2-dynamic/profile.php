<?php
/**
 * SDPMS - User Profile & Security Settings
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

require_login();
$db = Database::getConnection();

$userId = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM `users` WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $fullName = trim($_POST['full_name'] ?? '');
        if (empty($fullName)) {
            $error = 'Full name cannot be empty.';
        } else {
            $upd = $db->prepare("UPDATE `users` SET `full_name` = :name WHERE id = :id");
            $upd->execute([':name' => $fullName, ':id' => $userId]);
            $_SESSION['full_name'] = $fullName;
            $user['full_name'] = $fullName;
            $success = 'Profile details updated successfully.';
        }
    } elseif ($action === 'change_password') {
        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        if (empty($currentPass) || empty($newPass) || empty($confirmPass)) {
            $error = 'Please fill in all password fields.';
        } elseif (!password_verify($currentPass, $user['password_hash'])) {
            $error = 'The current password you entered is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $error = 'New password must be at least 6 characters in length.';
        } elseif ($newPass !== $confirmPass) {
            $error = 'New password and confirmation do not match.';
        } else {
            $newHash = password_hash($newPass, PASSWORD_BCRYPT);
            $upd = $db->prepare("UPDATE `users` SET `password_hash` = :hash WHERE id = :id");
            $upd->execute([':hash' => $newHash, ':id' => $userId]);

            log_audit_event($db, 'CHANGE_PASSWORD', 'users', (string)$userId, 'User changed security password.');
            $success = 'Your password has been changed successfully.';
        }
    }
}

// Fetch user audit history
$auditStmt = $db->prepare("SELECT * FROM `audit_logs` WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10");
$auditStmt->execute([':uid' => $userId]);
$recentAudits = $auditStmt->fetchAll();

$pageTitle = 'User Profile & Security';
$pageSubtitle = 'Manage your account settings, procurement role and authentication credentials';
$activePage = 'profile';

require_once __DIR__ . '/includes/header.php';
?>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; max-width: 900px; margin: 0 auto 2rem;">
  <!-- Profile Information -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Profile Information</h3>
    </div>
    <form method="POST">
      <?= render_csrf_input() ?>
      <input type="hidden" name="action" value="update_profile">
      <div class="card-body">
        <?php if ($success && ($_POST['action'] ?? '') === 'update_profile'): ?>
          <div class="alert-box alert-success" style="padding:0.75rem; margin-bottom:1rem; font-size:0.85rem;">
            <?= sanitize_output($success) ?>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" value="<?= sanitize_output($user['username']) ?>" readonly>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" class="form-control" value="<?= sanitize_output($user['email']) ?>" readonly>
        </div>

        <div class="form-group">
          <label class="form-label">Procurement Role</label>
          <input type="text" class="form-control" value="<?= sanitize_output($user['role']) ?>" readonly>
        </div>

        <div class="form-group">
          <label class="form-label" for="full_name">Full Name</label>
          <input type="text" id="full_name" name="full_name" class="form-control" value="<?= sanitize_output($user['full_name']) ?>" required>
        </div>
      </div>
      <div class="card-footer" style="justify-content: flex-end;">
        <button type="submit" class="btn btn-primary btn-sm">Update Name</button>
      </div>
    </form>
  </div>

  <!-- Change Password -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Change Password</h3>
    </div>
    <form method="POST">
      <?= render_csrf_input() ?>
      <input type="hidden" name="action" value="change_password">
      <div class="card-body">
        <?php if ($error && ($_POST['action'] ?? '') === 'change_password'): ?>
          <div class="alert-box alert-danger" style="padding:0.75rem; margin-bottom:1rem; font-size:0.85rem;">
            <?= sanitize_output($error) ?>
          </div>
        <?php elseif ($success && ($_POST['action'] ?? '') === 'change_password'): ?>
          <div class="alert-box alert-success" style="padding:0.75rem; margin-bottom:1rem; font-size:0.85rem;">
            <?= sanitize_output($success) ?>
          </div>
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label" for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" class="form-control" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" class="form-control" required minlength="6">
          <div class="form-help">Minimum 6 characters</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
        </div>
      </div>
      <div class="card-footer" style="justify-content: flex-end;">
        <button type="submit" class="btn btn-navy btn-sm">Update Password</button>
      </div>
    </form>
  </div>
</div>

<!-- Recent Security Audit History -->
<div class="card" style="max-width: 900px; margin: 0 auto;">
  <div class="card-header">
    <h3 class="card-title">Your Recent Activity Audit Log</h3>
    <div class="card-subtitle">Immutable security log of changes performed under your session</div>
  </div>
  <div class="table-responsive">
    <table class="data-table" style="font-size:0.82rem;">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Action</th>
          <th>Affected Module</th>
          <th>Record Code</th>
          <th>Activity Description</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($recentAudits)): ?>
          <tr><td colspan="5" style="text-align:center; padding:1.5rem; color:var(--slate-500);">No recent audit activities logged.</td></tr>
        <?php else: ?>
          <?php foreach ($recentAudits as $a): ?>
            <tr>
              <td class="cell-muted" style="white-space:nowrap;"><?= sanitize_output($a['created_at']) ?></td>
              <td><span class="cell-code"><?= sanitize_output($a['action']) ?></span></td>
              <td><?= sanitize_output($a['table_name']) ?></td>
              <td class="cell-code"><?= sanitize_output($a['record_id']) ?></td>
              <td><?= sanitize_output($a['details']) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
