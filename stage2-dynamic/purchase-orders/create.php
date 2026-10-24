<?php
/**
 * SDPMS - Create Purchase Order
 * Multi-record atomic transaction: creates PO, initializes Delivery tracking & history
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
$preselectedSupplier = trim($_GET['supplier_id'] ?? '');

// Auto-generate next PO ID (e.g. PO-2026-015)
$currentYear = date('Y');
$lastPoStmt = $db->query("SELECT id FROM `purchase_orders` WHERE id LIKE 'PO-{$currentYear}-%' ORDER BY id DESC LIMIT 1");
$lastPoId = $lastPoStmt->fetchColumn();
$nextNum = 1;
if ($lastPoId && preg_match('/PO-\d{4}-(\d+)/', $lastPoId, $m)) {
    $nextNum = (int)$m[1] + 1;
}
$suggestedPoId = sprintf("PO-%s-%03d", $currentYear, $nextNum);

// Fetch active suppliers
$activeSuppliers = $db->query("SELECT id, company_name, category FROM `suppliers` WHERE `status` = 'Active' ORDER BY company_name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $id             = strtoupper(trim($_POST['id'] ?? ''));
    $supplier_id    = trim($_POST['supplier_id'] ?? '');
    $order_date     = trim($_POST['order_date'] ?? '');
    $expected_date  = trim($_POST['expected_date'] ?? '');
    $description    = trim($_POST['description'] ?? '');
    $quantity       = (float)($_POST['quantity'] ?? 0);
    $unit_price     = (float)($_POST['unit_price'] ?? 0);
    $notes          = trim($_POST['notes'] ?? '');

    if (empty($id) || empty($supplier_id) || empty($order_date) || empty($expected_date) || empty($description)) {
        $error = 'Please fill in all mandatory purchase order fields.';
    } elseif ($quantity <= 0) {
        $error = 'Ordered quantity must be greater than zero.';
    } elseif ($unit_price < 0) {
        $error = 'Unit price cannot be a negative value.';
    } elseif (strtotime($expected_date) < strtotime($order_date)) {
        $error = 'Expected delivery date cannot precede the purchase order issue date.';
    } else {
        // Check duplicate PO ID
        $chk = $db->prepare("SELECT id FROM `purchase_orders` WHERE id = :id LIMIT 1");
        $chk->execute([':id' => $id]);
        if ($chk->fetch()) {
            $error = "Purchase order identifier '{$id}' already exists in database.";
        } else {
            $total_amount = $quantity * $unit_price;

            // Generate next Delivery ID
            $delCountStmt = $db->query("SELECT COUNT(*) FROM `deliveries`");
            $delCount = (int)$delCountStmt->fetchColumn();
            $delId = 'DEL-' . (3001 + $delCount);

            // Execute Transaction
            try {
                $db->beginTransaction();

                // 1. Insert Purchase Order
                $poInsert = $db->prepare(
                    "INSERT INTO `purchase_orders` (`id`, `supplier_id`, `order_date`, `expected_date`, `description`, `quantity`, `unit_price`, `total_amount`, `status`, `notes`, `created_by`)
                     VALUES (:id, :supplier_id, :order_date, :expected_date, :description, :quantity, :unit_price, :total_amount, 'Pending', :notes, :user_id)"
                );
                $poInsert->execute([
                    ':id'            => $id,
                    ':supplier_id'   => $supplier_id,
                    ':order_date'    => $order_date,
                    ':expected_date' => $expected_date,
                    ':description'   => $description,
                    ':quantity'      => $quantity,
                    ':unit_price'    => $unit_price,
                    ':total_amount'  => $total_amount,
                    ':notes'         => $notes,
                    ':user_id'       => $_SESSION['user_id'] ?? null
                ]);

                // 2. Initialize Delivery Consignment
                $delInsert = $db->prepare(
                    "INSERT INTO `deliveries` (`id`, `order_id`, `supplier_id`, `expected_date`, `actual_date`, `ordered_qty`, `received_qty`, `status`, `remarks`, `updated_by`)
                     VALUES (:del_id, :order_id, :supplier_id, :expected_date, NULL, :ordered_qty, 0.00, 'Pending', 'New purchase order issued; awaiting dispatch.', :user_id)"
                );
                $delInsert->execute([
                    ':del_id'        => $delId,
                    ':order_id'      => $id,
                    ':supplier_id'   => $supplier_id,
                    ':expected_date' => $expected_date,
                    ':ordered_qty'   => $quantity,
                    ':user_id'       => $_SESSION['user_id'] ?? null
                ]);

                // 3. Log initial status transition
                $histInsert = $db->prepare(
                    "INSERT INTO `delivery_status_history` (`delivery_id`, `previous_status`, `new_status`, `notes`, `changed_by`)
                     VALUES (:del_id, 'None', 'Pending', 'Initial delivery consignment created via PO requisition.', :user_id)"
                );
                $histInsert->execute([
                    ':del_id'  => $delId,
                    ':user_id' => $_SESSION['user_id'] ?? null
                ]);

                // 4. Audit Log
                log_audit_event(
                    $db,
                    'CREATE',
                    'purchase_orders',
                    $id,
                    "Issued PO {$id} to supplier {$supplier_id} for amount ₹" . number_format($total_amount, 2)
                );

                $db->commit();

                set_flash_message('success', "Purchase Order {$id} successfully created and delivery consignment {$delId} scheduled.");
                header('Location: ' . BASE_URL . '/purchase-orders/index.php');
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Transaction failed: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Create Purchase Order';
$pageSubtitle = 'Issue formal commercial purchase order and initialize consignment delivery tracking';
$activePage = 'purchase_orders';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 800px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Commercial Purchase Requisition</h3>
      <a href="<?= BASE_URL ?>/purchase-orders/index.php" class="btn btn-outline btn-sm">Cancel</a>
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
            <label class="form-label" for="id">Purchase Order ID <span class="required">*</span></label>
            <input type="text" id="id" name="id" class="form-control" value="<?= sanitize_output($_POST['id'] ?? $suggestedPoId) ?>" required>
            <div class="form-help">Format: PO-YYYY-XXX</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="supplier_id">Approved Supplier <span class="required">*</span></label>
            <select id="supplier_id" name="supplier_id" class="form-control" required>
              <option value="">Select an Approved Vendor...</option>
              <?php foreach ($activeSuppliers as $sup): ?>
                <?php $sel = (($_POST['supplier_id'] ?? $preselectedSupplier) === $sup['id']) ? 'selected' : ''; ?>
                <option value="<?= sanitize_output($sup['id']) ?>" <?= $sel ?>>
                  <?= sanitize_output($sup['company_name']) ?> (<?= sanitize_output($sup['category']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="order_date">Order Issue Date <span class="required">*</span></label>
            <input type="date" id="order_date" name="order_date" class="form-control" value="<?= sanitize_output($_POST['order_date'] ?? date('Y-m-d')) ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="expected_date">Expected Delivery Date <span class="required">*</span></label>
            <input type="date" id="expected_date" name="expected_date" class="form-control" value="<?= sanitize_output($_POST['expected_date'] ?? date('Y-m-d', strtotime('+14 days'))) ?>" required>
            <div class="form-help">Must be on or after order date</div>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="description">Material / Product Description <span class="required">*</span></label>
            <textarea id="description" name="description" class="form-control" rows="2" placeholder="Specify technical dimensions, material grade, or DIN standards..." required><?= sanitize_output($_POST['description'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label" for="calc-qty">Ordered Quantity <span class="required">*</span></label>
            <input type="number" id="calc-qty" name="quantity" class="form-control" step="any" min="0.01" value="<?= sanitize_output($_POST['quantity'] ?? '') ?>" placeholder="e.g. 500" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="calc-price">Unit Price (INR) <span class="required">*</span></label>
            <input type="number" id="calc-price" name="unit_price" class="form-control" step="0.01" min="0" value="<?= sanitize_output($_POST['unit_price'] ?? '') ?>" placeholder="e.g. 150.00" required>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="calc-total">Total Order Value (Auto-Calculated)</label>
            <input type="text" id="calc-total" class="form-control" readonly style="font-weight:700; color:var(--primary-600); font-size:1.15rem; background:var(--slate-50);">
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="notes">Procurement Notes / Logistics Instructions</label>
            <input type="text" id="notes" name="notes" class="form-control" value="<?= sanitize_output($_POST['notes'] ?? '') ?>" placeholder="Inspection requirements, gate delivery timings...">
          </div>
        </div>
      </div>

      <div class="card-footer" style="justify-content: flex-end; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/purchase-orders/index.php" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">Save &amp; Issue Purchase Order</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
