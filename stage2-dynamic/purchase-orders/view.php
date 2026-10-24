<?php
/**
 * SDPMS - Detailed Purchase Order & Delivery Consignment View
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

$id = trim($_GET['id'] ?? '');
$stmt = $db->prepare(
    "SELECT po.*, s.company_name, s.category, s.email, s.phone, s.contact_person, u.full_name AS creator_name
     FROM `purchase_orders` po
     JOIN `suppliers` s ON po.supplier_id = s.id
     LEFT JOIN `users` u ON po.created_by = u.id
     WHERE po.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$order = $stmt->fetch();

if (!$order) {
    set_flash_message('danger', "Purchase order '{$id}' does not exist.");
    header('Location: ' . BASE_URL . '/purchase-orders/index.php');
    exit;
}

// Linked delivery record
$delStmt = $db->prepare("SELECT * FROM `deliveries` WHERE `order_id` = :id LIMIT 1");
$delStmt->execute([':id' => $id]);
$delivery = $delStmt->fetch();

$delayInfo = $delivery ? calculate_delay_days($delivery['expected_date'], $delivery['actual_date'], $delivery['status']) : null;

// History of delivery status changes
$history = [];
if ($delivery) {
    $histStmt = $db->prepare(
        "SELECT h.*, u.full_name AS changer_name 
         FROM `delivery_status_history` h
         LEFT JOIN `users` u ON h.changed_by = u.id
         WHERE h.delivery_id = :did 
         ORDER BY h.created_at DESC"
    );
    $histStmt->execute([':did' => $delivery['id']]);
    $history = $histStmt->fetchAll();
}

$pageTitle = 'Purchase Order: ' . $order['id'];
$pageSubtitle = 'Order Requisition Specifications & Synchronized Consignment Status';
$activePage = 'purchase_orders';

$headerActions = '';
if ($order['status'] !== 'Completed' && $order['status'] !== 'Cancelled') {
    $headerActions .= '<a href="' . BASE_URL . '/purchase-orders/edit.php?id=' . urlencode($id) . '" class="btn btn-outline btn-sm">Edit PO</a> ';
    $headerActions .= '<a href="' . BASE_URL . '/deliveries/edit.php?id=' . urlencode($delivery['id']) . '" class="btn btn-primary btn-sm">Update Delivery</a> ';
    $headerActions .= '<a href="' . BASE_URL . '/purchase-orders/cancel.php?id=' . urlencode($id) . '" class="btn btn-outline-danger btn-sm" data-confirm="Cancel this purchase order and all tracking?">Cancel PO</a>';
}

require_once __DIR__ . '/../includes/header.php';
?>

<!-- PO Overview -->
<div class="card" style="margin-bottom: 2rem;">
  <div class="card-header">
    <div>
      <h3 class="card-title">PO Overview: <span class="cell-code" style="color:var(--primary-600);"><?= sanitize_output($order['id']) ?></span></h3>
      <div class="card-subtitle">Issued on <?= format_date_in($order['order_date']) ?> &bull; Created by <?= sanitize_output($order['creator_name'] ?? 'Procurement Staff') ?></div>
    </div>
    <div><?= render_status_badge($order['status']) ?></div>
  </div>
  <div class="card-body">
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; font-size: 0.9rem;">
      <div>
        <span class="cell-muted">Supplier Organization:</span>
        <div style="font-weight: 700; font-size: 1.05rem;">
          <a href="<?= BASE_URL ?>/suppliers/view.php?id=<?= urlencode($order['supplier_id']) ?>" style="color:var(--navy-900); text-decoration:underline;">
            <?= sanitize_output($order['company_name']) ?>
          </a>
        </div>
        <div class="cell-muted"><?= sanitize_output($order['supplier_id']) ?> &bull; <?= sanitize_output($order['category']) ?></div>
      </div>

      <div>
        <span class="cell-muted">Contact Person:</span>
        <div style="font-weight: 600;"><?= sanitize_output($order['contact_person']) ?></div>
        <div class="cell-muted"><?= sanitize_output($order['email']) ?></div>
      </div>

      <div>
        <span class="cell-muted">Expected Delivery Date:</span>
        <div style="font-weight: 700; font-size: 1.05rem; color:var(--primary-600);"><?= format_date_in($order['expected_date']) ?></div>
      </div>
    </div>

    <div style="margin-top: 1.25rem; padding: 1rem; background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--border-radius-sm);">
      <div class="cell-muted" style="font-size: 0.78rem;">Technical Material Description:</div>
      <div style="font-size: 0.95rem; font-weight: 500; margin-top: 0.25rem;"><?= nl2br(sanitize_output($order['description'])) ?></div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1.25rem; text-align: center;">
      <div style="background: var(--white); border: 1px solid var(--slate-200); padding: 1rem; border-radius: var(--border-radius-sm);">
        <div class="cell-muted" style="font-size: 0.75rem;">Ordered Quantity</div>
        <div style="font-size: 1.4rem; font-weight: 800; color: var(--navy-900);"><?= number_format($order['quantity']) ?></div>
      </div>
      <div style="background: var(--white); border: 1px solid var(--slate-200); padding: 1rem; border-radius: var(--border-radius-sm);">
        <div class="cell-muted" style="font-size: 0.75rem;">Unit Rate</div>
        <div style="font-size: 1.4rem; font-weight: 800; color: var(--navy-900);"><?= format_currency_inr($order['unit_price']) ?></div>
      </div>
      <div style="background: var(--navy-50); border: 1px solid var(--navy-100); padding: 1rem; border-radius: var(--border-radius-sm);">
        <div class="cell-muted" style="font-size: 0.75rem; color:var(--navy-700);">Total Valuation</div>
        <div style="font-size: 1.4rem; font-weight: 800; color: var(--primary-600);"><?= format_currency_inr($order['total_amount']) ?></div>
      </div>
    </div>

    <?php if (!empty($order['notes'])): ?>
      <div style="margin-top: 1rem; font-size: 0.85rem;">
        <span class="cell-muted">Notes:</span> <?= sanitize_output($order['notes']) ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Synchronized Consignment Delivery Tracking -->
<?php if ($delivery): ?>
  <div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
      <div>
        <h3 class="card-title">Synchronized Consignment Delivery: <span class="cell-code"><?= sanitize_output($delivery['id']) ?></span></h3>
        <div class="card-subtitle">Tracking receipt status and physical quantity arrival</div>
      </div>
      <div style="display:flex; gap:0.5rem; align-items:center;">
        <?= render_status_badge($delivery['status']) ?>
        <a href="<?= BASE_URL ?>/deliveries/edit.php?id=<?= urlencode($delivery['id']) ?>" class="btn btn-primary btn-sm">Update Delivery</a>
      </div>
    </div>
    <div class="card-body">
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.25rem; font-size: 0.9rem;">
        <div>
          <span class="cell-muted">Scheduled Expected Date:</span>
          <div style="font-weight: 600;"><?= format_date_in($delivery['expected_date']) ?></div>
        </div>
        <div>
          <span class="cell-muted">Actual Date Received:</span>
          <div style="font-weight: 600;"><?= format_date_in($delivery['actual_date']) ?></div>
        </div>
        <div>
          <span class="cell-muted">Physical Fulfillment:</span>
          <div style="font-weight: 700;">
            <?= number_format($delivery['received_qty']) ?> / <?= number_format($delivery['ordered_qty']) ?> units
            (<?= $delivery['ordered_qty'] > 0 ? round(($delivery['received_qty'] / $delivery['ordered_qty']) * 100) : 0 ?>%)
          </div>
        </div>
        <div>
          <span class="cell-muted">Delay Metric:</span>
          <div>
            <?php if ($delayInfo['isLate']): ?>
              <span style="color:var(--danger-600); font-weight:700;">
                +<?= $delayInfo['delay'] ?> Days (<?= $delayInfo['isOverdue'] ? 'Overdue' : 'Late' ?>)
              </span>
            <?php elseif (!empty($delayInfo['earlyDays'])): ?>
              <span style="color:var(--success-600); font-weight:700;">
                <?= $delayInfo['earlyDays'] ?> Days Early (On Time)
              </span>
            <?php elseif ($delivery['status'] === 'Cancelled'): ?>
              <span class="cell-muted">—</span>
            <?php else: ?>
              <span style="color:var(--success-600); font-weight:700;">On Schedule</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if (!empty($delivery['remarks'])): ?>
        <div style="margin-top: 1rem; padding: 0.75rem; background: var(--slate-50); border-radius: var(--border-radius-sm); font-size: 0.85rem;">
          <span class="cell-muted">Consignment Remarks:</span> <?= sanitize_output($delivery['remarks']) ?>
        </div>
      <?php endif; ?>

      <!-- Status History -->
      <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--navy-900); margin: 1.5rem 0 0.75rem;">Status Progression History Log</h4>
      <div class="table-responsive" style="border: 1px solid var(--slate-200); border-radius: var(--border-radius-sm);">
        <table class="data-table" style="font-size: 0.82rem;">
          <thead>
            <tr>
              <th>Timestamp</th>
              <th>Prior Status</th>
              <th>New Status</th>
              <th>Audit Description</th>
              <th>Authorized By</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($history)): ?>
              <tr><td colspan="5" style="text-align:center; padding:1rem; color:var(--slate-500);">Initial creation state.</td></tr>
            <?php else: ?>
              <?php foreach ($history as $h): ?>
                <tr>
                  <td class="cell-muted" style="white-space:nowrap;"><?= sanitize_output($h['created_at']) ?></td>
                  <td><?= render_status_badge($h['previous_status'] ?: 'None') ?></td>
                  <td><?= render_status_badge($h['new_status']) ?></td>
                  <td><?= sanitize_output($h['notes']) ?></td>
                  <td><?= sanitize_output($h['changer_name'] ?? 'System') ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
