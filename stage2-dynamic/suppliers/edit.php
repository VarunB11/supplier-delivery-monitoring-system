<?php
/**
 * SDPMS - Edit Supplier Details
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

$id = trim($_GET['id'] ?? '');
if (empty($id)) {
    set_flash_message('danger', 'Supplier identifier was not provided.');
    header('Location: ' . BASE_URL . '/suppliers/index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM `suppliers` WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_flash_message('danger', "Supplier '{$id}' does not exist in the database.");
    header('Location: ' . BASE_URL . '/suppliers/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $company_name   = trim($_POST['company_name'] ?? '');
    $contact_person = trim($_POST['contact_person'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $phone          = trim($_POST['phone'] ?? '');
    $address        = trim($_POST['address'] ?? '');
    $city           = trim($_POST['city'] ?? '');
    $category       = trim($_POST['category'] ?? '');
    $status         = in_array($_POST['status'] ?? '', ['Active', 'Inactive']) ? $_POST['status'] : 'Active';

    if (empty($company_name) || empty($contact_person) || empty($email) || empty($city) || empty($category)) {
        $error = 'Please fill in all mandatory fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } else {
        try {
            $upd = $db->prepare(
                "UPDATE `suppliers` 
                 SET `company_name` = :company,
                     `contact_person` = :contact,
                     `email` = :email,
                     `phone` = :phone,
                     `address` = :address,
                     `city` = :city,
                     `category` = :category,
                     `status` = :status
                 WHERE `id` = :id"
            );
            $upd->execute([
                ':company'  => $company_name,
                ':contact'  => $contact_person,
                ':email'    => $email,
                ':phone'    => $phone,
                ':address'  => $address,
                ':city'     => $city,
                ':category' => $category,
                ':status'   => $status,
                ':id'       => $id
            ]);

            log_audit_event($db, 'UPDATE', 'suppliers', $id, "Updated vendor profile information for '{$company_name}'.");
            set_flash_message('success', "Supplier '{$company_name}' ({$id}) updated successfully.");
            header('Location: ' . BASE_URL . '/suppliers/index.php');
            exit;
        } catch (Exception $e) {
            $error = 'Update operation failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Supplier: ' . $supplier['id'];
$pageSubtitle = 'Update vendor contacts, compliance status and manufacturing address';
$activePage = 'suppliers';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 780px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Modify Supplier Information</h3>
      <a href="<?= BASE_URL ?>/suppliers/index.php" class="btn btn-outline btn-sm">Cancel</a>
    </div>

    <?php if ($error): ?>
      <div class="alert-box alert-danger" style="margin: 1.5rem 1.5rem 0;">
        <div><?= sanitize_output($error) ?></div>
      </div>
    <?php endif; ?>

    <form method="POST">
      <?= render_csrf_input() ?>
      <div class="card-body">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Supplier ID</label>
            <input type="text" class="form-control" value="<?= sanitize_output($supplier['id']) ?>" readonly>
            <div class="form-help">Primary key identifier cannot be modified</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="company_name">Company Name <span class="required">*</span></label>
            <input type="text" id="company_name" name="company_name" class="form-control" value="<?= sanitize_output($_POST['company_name'] ?? $supplier['company_name']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="contact_person">Contact Person <span class="required">*</span></label>
            <input type="text" id="contact_person" name="contact_person" class="form-control" value="<?= sanitize_output($_POST['contact_person'] ?? $supplier['contact_person']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="email">Official Email Address <span class="required">*</span></label>
            <input type="email" id="email" name="email" class="form-control" value="<?= sanitize_output($_POST['email'] ?? $supplier['email']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" class="form-control" value="<?= sanitize_output($_POST['phone'] ?? $supplier['phone']) ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="city">City / State <span class="required">*</span></label>
            <input type="text" id="city" name="city" class="form-control" value="<?= sanitize_output($_POST['city'] ?? $supplier['city']) ?>" required>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="category">Product / Material Category <span class="required">*</span></label>
            <input type="text" id="category" name="category" class="form-control" value="<?= sanitize_output($_POST['category'] ?? $supplier['category']) ?>" required>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="address">Industrial Address / Works</label>
            <input type="text" id="address" name="address" class="form-control" value="<?= sanitize_output($_POST['address'] ?? $supplier['address']) ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="status">Supplier Status</label>
            <select id="status" name="status" class="form-control">
              <option value="Active" <?= ($_POST['status'] ?? $supplier['status']) === 'Active' ? 'selected' : '' ?>>Active</option>
              <option value="Inactive" <?= ($_POST['status'] ?? $supplier['status']) === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
          </div>
        </div>
      </div>

      <div class="card-footer" style="justify-content: flex-end; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/suppliers/index.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
