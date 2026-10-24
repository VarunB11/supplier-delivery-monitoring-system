<?php
/**
 * SDPMS - Register New Supplier
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_not_auditor();
$db = Database::getConnection();

$error = null;

// Suggest next ID
$lastIdStmt = $db->query("SELECT id FROM `suppliers` WHERE id LIKE 'SUP-%' ORDER BY id DESC LIMIT 1");
$lastId = $lastIdStmt->fetchColumn();
$nextNum = 1001;
if ($lastId && preg_match('/SUP-(\d+)/', $lastId, $m)) {
    $nextNum = (int)$m[1] + 1;
}
$suggestedId = 'SUP-' . $nextNum;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $id             = strtoupper(trim($_POST['id'] ?? ''));
    $company_name   = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $category       = trim($_POST['category'] ?? '');
    $status         = in_array($_POST['status'] ?? '', ['Active', 'Inactive']) ? $_POST['status'] : 'Active';

    // Validation
    if (empty($id) || empty($company_name) || empty($contact_person) || empty($email) || empty($city) || empty($category)) {
        $error = 'Please fill in all mandatory fields marked with an asterisk (*).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid official email address.';
    } else {
        // Check for duplicate ID
        $chk = $db->prepare("SELECT id FROM `suppliers` WHERE id = :id LIMIT 1");
        $chk->execute([':id' => $id]);
        if ($chk->fetch()) {
            $error = "Supplier ID '{$id}' already exists. Please assign a unique identifier.";
        } else {
            try {
                $stmt = $db->prepare(
                    "INSERT INTO `suppliers` (`id`, `company_name`, `contact_person`, `email`, `phone`, `address`, `city`, `category`, `registration_date`, `status`)
                     VALUES (:id, :company, :contact, :email, :phone, :address, :city, :category, CURDATE(), :status)"
                );
                $stmt->execute([
                    ':id'       => $id,
                    ':company'  => $company_name,
                    ':contact'  => $contact_person,
                    ':email'    => $email,
                    ':phone'    => $phone,
                    ':address'  => $address,
                    ':city'     => $city,
                    ':category' => $category,
                    ':status'   => $status
                ]);

                log_audit_event($db, 'CREATE', 'suppliers', $id, "Registered new supplier '{$company_name}' in category '{$category}'.");
                set_flash_message('success', "Supplier '{$company_name}' ({$id}) was successfully registered.");
                header('Location: ' . BASE_URL . '/suppliers/index.php');
                exit;
            } catch (Exception $e) {
                $error = 'Database insertion failure: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Register New Supplier';
$pageSubtitle = 'Onboard an approved industrial vendor into the procurement system';
$activePage = 'suppliers';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 780px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Vendor Registration Form</h3>
      <a href="<?= BASE_URL ?>/suppliers/index.php" class="btn btn-outline btn-sm">Cancel &amp; Return</a>
    </div>

    <?php if ($error): ?>
      <div class="alert-box alert-danger" style="margin: 1.5rem 1.5rem 0;">
        <div><?= sanitize_output($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/suppliers/create.php">
      <?= render_csrf_input() ?>
      <div class="card-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label" for="id">Supplier ID <span class="required">*</span></label>
            <input type="text" id="id" name="id" class="form-control" value="<?= sanitize_output($_POST['id'] ?? $suggestedId) ?>" required>
            <div class="form-help">Unique uppercase code (e.g. SUP-1009)</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="company_name">Company Name <span class="required">*</span></label>
            <input type="text" id="company_name" name="company_name" class="form-control" value="<?= sanitize_output($_POST['company_name'] ?? '') ?>" placeholder="Apex Precision Steel Ltd." required>
          </div>

          <div class="form-group">
            <label class="form-label" for="contact_person">Contact Person <span class="required">*</span></label>
            <input type="text" id="contact_person" name="contact_person" class="form-control" value="<?= sanitize_output($_POST['contact_person'] ?? '') ?>" placeholder="Rajesh Sharma" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Official Email Address <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="form-control" value="<?= sanitize_output($_POST['email'] ?? '') ?>" placeholder="contact@company.example.com" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?= sanitize_output($_POST['phone'] ?? '') ?>" placeholder="+91 98XXX XXXXX">
          </div>

          <div class="form-group">
            <label class="form-label" for="city">City / State <span class="required">*</span></label>
            <input type="text" id="city" name="city" class="form-control" value="<?= sanitize_output($_POST['city'] ?? '') ?>" placeholder="e.g. Pune, Maharashtra" required>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="category">Product / Material Category <span class="required">*</span></label>
            <input type="text" id="category" name="category" class="form-control" value="<?= sanitize_output($_POST['category'] ?? '') ?>" placeholder="e.g. Metals &amp; Alloys, Electronic Components" required>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="address">Industrial Address / Works</label>
            <input type="text" id="address" name="address" class="form-control" value="<?= sanitize_output($_POST['address'] ?? '') ?>" placeholder="Plot 45, Phase II, Industrial Estate">
          </div>

          <div class="form-group">
            <label class="form-label" for="status">Initial Status</label>
            <select id="status" name="status" class="form-control">
              <option value="Active" <?= ($_POST['status'] ?? 'Active') === 'Active' ? 'selected' : '' ?>>Active (Approved for POs)</option>
              <option value="Inactive" <?= ($_POST['status'] ?? '') === 'Inactive' ? 'selected' : '' ?>>Inactive (Suspended)</option>
            </select>
          </div>
        </div>
      </div>

      <div class="card-footer" style="justify-content: flex-end; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/suppliers/index.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">Save &amp; Register Supplier</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
