<?php
/**
 * SDPMS - Update Delivery Consignment Status & Receipt
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_not_auditor();
$db = Database::getConnection();

$id = trim($_GET['id'] ?? '');
$stmt = $db->prepare(
    "SELECT d.*, s.company_name AS supplier_name, po.description 
     FROM `deliveries` d
     JOIN `suppliers` s ON d.supplier_id = s.id
     JOIN `purchase_orders` po ON d.order_id = po.id
     WHERE d.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$delivery = $stmt->fetch();

if (!$delivery) {
    set_flash_message('danger', "Delivery consignment '{$id}' does not exist.");
    header('Location: ' . BASE_URL . '/deliveries/index.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf_token();

    $actual_date  = trim($_POST['actual_date'] ?? '');
    $received_qty = (float)($_POST['received_qty'] ?? 0);
    $chosenStatus = trim($_POST['status'] ?? 'Pending');
    $remarks      = trim($_POST['remarks'] ?? '');

    $orderedQty = (float)$delivery['ordered_qty'];

    // Validation 1: Negative received count
    if ($received_qty < 0) {
        $error = 'Received quantity cannot be negative.';
    }
    // Validation 2: Quantity exceeding ordered count
    elseif ($received_qty > $orderedQty) {
        $error = "Received quantity ({$received_qty}) cannot exceed ordered quantity ({$orderedQty}) without documented over-delivery authorization.";
    }
    // Validation 3: Logical consistency between count and status
    elseif ($received_qty == 0 && $chosenStatus === 'Delivered') {
        $error = 'Cannot mark consignment as Delivered when received quantity is 0.';
    }
    // Validation 4: Actual date required for physical arrival
    elseif (($chosenStatus === 'Delivered' || $chosenStatus === 'Partially Delivered') && empty($actual_date)) {
        $error = 'Please provide the physical goods receipt date (Actual Delivery Date).';
    } else {
        // Automatically determine status if full count or partial count received
        $finalStatus = $chosenStatus;
        if ($received_qty == $orderedQty && $received_qty > 0) {
            $finalStatus = 'Delivered';
        } elseif ($received_qty > 0 && $received_qty < $orderedQty) {
            $finalStatus = 'Partially Delivered';
        }

        $previousStatus = $delivery['status'];
        $actualDateVal = !empty($actual_date) ? $actual_date : null;

        try {
            $db->beginTransaction();

            // 1. Update delivery record
            $updDel = $db->prepare(
                "UPDATE `deliveries` 
                 SET `actual_date` = :act, 
                     `received_qty` = :rec, 
                     `status` = :st, 
                     `remarks` = :rem,
                     `updated_by` = :uid
                 WHERE `id` = :id"
            );
            $updDel->execute([
                ':act' => $actualDateVal,
                ':rec' => $received_qty,
                ':st'  => $finalStatus,
                ':rem' => $remarks,
                ':uid' => $_SESSION['user_id'] ?? null,
                ':id'  => $id
            ]);

            // 2. Synchronize Purchase Order Status
            $poStatus = 'Pending';
            if ($finalStatus === 'Delivered') {
                $poStatus = 'Completed';
            } elseif ($finalStatus === 'Partially Delivered') {
                $poStatus = 'Partially Delivered';
            } elseif ($finalStatus === 'Delayed') {
                $poStatus = 'Delayed';
            } elseif ($finalStatus === 'Cancelled') {
                $poStatus = 'Cancelled';
            }

            $updPO = $db->prepare("UPDATE `purchase_orders` SET `status` = :st WHERE `id` = :poid");
            $updPO->execute([
                ':st'   => $poStatus,
                ':poid' => $delivery['order_id']
            ]);

            // 3. Append to Delivery Status History
            $hist = $db->prepare(
                "INSERT INTO `delivery_status_history` (`delivery_id`, `previous_status`, `new_status`, `notes`, `changed_by`)
                 VALUES (:did, :prev, :new, :notes, :uid)"
            );
            $hist->execute([
                ':did'   => $id,
                ':prev'  => $previousStatus,
                ':new'   => $finalStatus,
                ':notes' => "Received qty updated to {$received_qty}/{$orderedQty}. Status: {$finalStatus}. Remarks: " . ($remarks ?: 'None'),
                ':uid'   => $_SESSION['user_id'] ?? null
            ]);

            // 4. Audit Log
            log_audit_event(
                $db,
                'UPDATE_DELIVERY',
                'deliveries',
                $id,
                "Updated consignment {$id}: Status {$previousStatus} -> {$finalStatus}, Received {$received_qty}/{$orderedQty}."
            );

            $db->commit();
            set_flash_message('success', "Delivery {$id} updated successfully. Status transitioned to '{$finalStatus}'.");
            header('Location: ' . BASE_URL . '/deliveries/view.php?id=' . urlencode($id));
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Update failed: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Update Delivery: ' . $delivery['id'];
$pageSubtitle = 'Record physical goods receipt notes (GRN), actual dates and consignment remarks';
$activePage = 'deliveries';

require_once __DIR__ . '/../includes/header.php';
?>

<div style="max-width: 780px; margin: 0 auto;">
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Consignment Receipt Update</h3>
      <a href="<?= BASE_URL ?>/deliveries/view.php?id=<?= urlencode($id) ?>" class="btn btn-outline btn-sm">Cancel</a>
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
            <label class="form-label">Delivery Consignment ID</label>
            <input type="text" class="form-control" value="<?= sanitize_output($delivery['id']) ?>" readonly>
          </div>

          <div class="form-group">
            <label class="form-label">Purchase Order ID</label>
            <input type="text" class="form-control" value="<?= sanitize_output($delivery['order_id']) ?>" readonly>
          </div>

          <div class="form-group">
            <label class="form-label">Supplier Organization</label>
            <input type="text" class="form-control" value="<?= sanitize_output($delivery['supplier_name']) ?>" readonly>
          </div>

          <div class="form-group">
            <label class="form-label">Scheduled Expected Date</label>
            <input type="date" class="form-control" value="<?= sanitize_output($delivery['expected_date']) ?>" readonly>
          </div>

          <div class="form-group">
            <label class="form-label" for="actual_date">Actual Receipt Date <span class="required">*</span></label>
            <input type="date" id="actual_date" name="actual_date" class="form-control" value="<?= sanitize_output($_POST['actual_date'] ?? $delivery['actual_date'] ?? date('Y-m-d')) ?>">
            <div class="form-help">Date goods arrived at warehouse gate</div>
          </div>

          <div class="form-group">
            <label class="form-label">Contract Ordered Quantity</label>
            <input type="text" class="form-control" value="<?= number_format($delivery['ordered_qty']) ?> units" readonly>
          </div>

          <div class="form-group">
            <label class="form-label" for="received_qty">Received Quantity <span class="required">*</span></label>
            <input type="number" id="received_qty" name="received_qty" class="form-control" min="0" max="<?= (float)$delivery['ordered_qty'] ?>" step="any" value="<?= sanitize_output($_POST['received_qty'] ?? $delivery['received_qty']) ?>" required>
            <div class="form-help">Cannot exceed <?= number_format($delivery['ordered_qty']) ?> units</div>
          </div>

          <div class="form-group">
            <label class="form-label" for="status">Consignment Status</label>
            <select id="status" name="status" class="form-control">
              <option value="Pending" <?= ($_POST['status'] ?? $delivery['status']) === 'Pending' ? 'selected' : '' ?>>Pending</option>
              <option value="Partially Delivered" <?= ($_POST['status'] ?? $delivery['status']) === 'Partially Delivered' ? 'selected' : '' ?>>Partially Delivered</option>
              <option value="Delivered" <?= ($_POST['status'] ?? $delivery['status']) === 'Delivered' ? 'selected' : '' ?>>Delivered (Completed)</option>
              <option value="Delayed" <?= ($_POST['status'] ?? $delivery['status']) === 'Delayed' ? 'selected' : '' ?>>Delayed</option>
              <option value="Cancelled" <?= ($_POST['status'] ?? $delivery['status']) === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
          </div>

          <div class="form-group span-full">
            <label class="form-label" for="remarks">Inspection Remarks / Delay Notes</label>
            <textarea id="remarks" name="remarks" class="form-control" rows="3" placeholder="Enter goods receipt note (GRN), QA defect report or cause of delivery delay..."><?= sanitize_output($_POST['remarks'] ?? $delivery['remarks']) ?></textarea>
          </div>
        </div>
      </div>

      <div class="card-footer" style="justify-content: flex-end; gap: 0.75rem;">
        <a href="<?= BASE_URL ?>/deliveries/view.php?id=<?= urlencode($id) ?>" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Consignment</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
