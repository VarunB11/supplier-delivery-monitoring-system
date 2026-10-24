<?php
/**
 * SDPMS - Edit Purchase Order
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

$id = trim($_GET['id'] ?? '');
$stmt = $db->prepare("SELECT * FROM `purchase_orders` WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash_message('danger', "Purchase order '{$id}' does not exist.");
    header('Location: ' . BASE_URL . '/purchase-orders/index.php');
    exit;
}

if ($order['status'] === 'Completed' || $order['status'] === 'Cancelled') {
    set_flash_message('warning', "Purchase order {$id} is in '{$order['status']}' state and cannot be modified.");
    header('Location: ' . BASE_URL . '/purchase-orders/view.php?id=' . urlencode($id));
    exit;
}

$error = null;
$suppliers = $db->query("SELECT id, company_name FROM `suppliers` ORDER BY company_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $expected_date  = trim($_POST['expected_date'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $quantity       = (float)($_POST['quantity'] ?? 0);
    $unit_price     = (float)($_POST['unit_price'] ?? 0);
    $notes          = trim($_POST['notes'] ?? '');

    if (empty($expected_date) || empty($description)) {
        $error = 'Please fill in all mandatory fields.';
    } elseif ($quantity <= 0 || $unit_price < 0) {
        $error = 'Quantity must be positive and unit price non-negative.';
    } elseif (strtotime($expected_date) < strtotime($order['order_date'])) {
        $error = 'Expected delivery date cannot precede order date (' . format_date_in($order['order_date']) . ').';
    } else {
        $total_amount = $quantity * $unit_price;

        try {
            $db->beginTransaction();

            $upd = $db->prepare(
                "UPDATE `purchase_orders` 
                 SET `expected_date` = :expected,
                     `description` = :description,
                     `quantity` = :qty,
                     `unit_price` = :price,
                     `total_amount` = :total,
                     `notes` = :notes
                 WHERE `id` = :id"
            );
            $upd->execute([
                ':expected'    => $expected_date,
                ':description' => $description,
                ':qty'         => $quantity,
                ':price'       => $unit_price,
                ':total'       => $total_amount,
                ':notes'       => $notes,
                ':id'          => $id
            ]);

            // Sync delivery expected date & ordered quantity
            $delUpd = $db->prepare(
                "UPDATE `deliveries` 
                 SET `expected_date` = :expected, `ordered_qty` = :qty 
                 WHERE `order_id` = :id"
            );
            $delUpd->execute([
                ':expected' => $expected_date,
                ':qty'      => $quantity,
                ':id'       => $id
            ]);

            log_audit_event($db, 'UPDATE', 'purchase_orders', $id, "Updated details for PO {$id}.");
            $db->commit();

            set_flash_message('success', "Purchase Order {$id} updated successfully.");
            header('Location: ' . BASE_URL . '/purchase-orders/view.php?id=' . urlencode($id));
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Edit Purchase Order: ' . $order['id'];
$pageSubtitle = 'Modify order description, expected delivery date or quantities';
$activePage = 'purchase_orders';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Modify Purchase Order</h3>
      <a href="<?= BASE_URL ?>/purchase-orders/view.php?id=<?= urlencode($id) ?>" class="btn btn-outline btn-sm">Cancel</a>
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
            <label class="form-label">Purchase Order ID</label>
            <input type="text" class="form-control" value="<?= sanitize_output($order['id']) ?>" readonly>
          </div>

          <div class="form-group">
            <label class="form-label">Order Issue Date</label>
            <input type="date" class="form-control" value="<?= sanitize_output($order['order_date']) ?>" readonly>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="expected_date">Expected Delivery Date <span class="required">*</span></label>
            <input type="date" id="expected_date" name="expected_date" class="form-control" value="<?= sanitize_output($_POST['expected_date'] ?? $order['expected_date']) ?>" required>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="description">Material / Product Description <span class="required">*</span></label>
            <textarea id="description" name="description" class="form-control" rows="2" required><?= sanitize_output($_POST['description'] ?? $order['description']) ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label" for="calc-qty">Ordered Quantity <span class="required">*</span></label>
            <input type="number" id="calc-qty" name="quantity" class="form-control" step="any" min="0.01" value="<?= sanitize_output($_POST['quantity'] ?? $order['quantity']) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="calc-price">Unit Price (INR) <span class="required">*</span></label>
            <input type="number" id="calc-price" name="unit_price" class="form-control" step="0.01" min="0" value="<?= sanitize_output($_POST['unit_price'] ?? $order['unit_price']) ?>" required>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="calc-total">Total Order Value (Auto-Calculated)</label>
            <input type="text" id="calc-total" class="form-control" readonly style="font-weight:700; color:var(--primary-600); font-size:1.15rem; background:var(--slate-50);">
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="notes">Notes / Instructions</label>
            <input type="text" id="notes" name="notes" class="form-control" value="<?= sanitize_output($_POST['notes'] ?? $order['notes']) ?>">
          </div>
        </div>
      </div>

      <div class="card-footer" style="justify-content: flex-end; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/purchase-orders/view.php?id=<?= urlencode($id) ?>" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
