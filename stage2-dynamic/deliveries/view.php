<?php
/**
 * SDPMS - Consignment Delivery Details & Audit History View
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

$id = trim($_GET['id'] ?? '');
$stmt = $db->prepare(
    "SELECT d.*, s.company_name AS supplier_name, s.category, po.description, po.total_amount, u.full_name AS updater_name
     FROM `deliveries` d
     JOIN `suppliers` s ON d.supplier_id = s.id
     JOIN `purchase_orders` po ON d.order_id = po.id
     LEFT JOIN `users` u ON d.updated_by = u.id
     WHERE d.id = :id LIMIT 1"
);
$stmt->execute([':id' => $id]);
$delivery = $stmt->fetch();

if (!$delivery) {
    set_flash_message('danger', "Delivery consignment '{$id}' does not exist.");
    header('Location: ' . BASE_URL . '/deliveries/index.php');
    exit;
}

$delayInfo = calculate_delay_days($delivery['expected_date'], $delivery['actual_date'], $delivery['status']);

// Status History
$histStmt = $db->prepare(
    "SELECT h.*, u.full_name AS changer_name 
     FROM `delivery_status_history` h
     LEFT JOIN `users` u ON h.changed_by = u.id
     WHERE h.delivery_id = :did 
     ORDER BY h.created_at DESC"
);
$histStmt->execute([':did' => $id]);
$history = $histStmt->fetchAll();

$pageTitle = 'Consignment: ' . $delivery['id'];
$pageSubtitle = 'Delivery Progress Tracking, Delay Metrics & Status Transition Audit Log';
$activePage = 'deliveries';

$headerActions = '';
if ($delivery['status'] !== 'Cancelled') {
    $headerActions .= '<a href="' . BASE_URL . '/deliveries/edit.php?id=' . urlencode($id) . '" class="btn btn-primary btn-sm">Update Delivery Status</a> ';
}
$headerActions .= '<a href="' . BASE_URL . '/purchase-orders/view.php?id=' . urlencode($delivery['order_id']) . '" class="btn btn-outline btn-sm">View PO</a>';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Consignment Card -->
<div class="card" style="margin-bottom: 2rem;">
  <div class="card-header">
    <div>
      <h3 class="card-title">Delivery Record: <span class="cell-code" style="color:var(--primary-600);"><?= sanitize_output($delivery['id']) ?></span></h3>
      <div class="card-subtitle">Linked PO: <?= sanitize_output($delivery['order_id']) ?> &bull; Supplier: <?= sanitize_output($delivery['supplier_name']) ?></div>
    </div>
    <div><?= render_status_badge($delivery['status']) ?></div>
  </div>
  <div class="card-body">
    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; font-size: 0.9rem;">
      <div>
        <span class="cell-muted">Purchase Order:</span>
        <div style="font-weight: 700;">
          <a href="<?= BASE_URL ?>/purchase-orders/view.php?id=<?= urlencode($delivery['order_id']) ?>" style="color:var(--primary-600); text-decoration:underline;">
            <?= sanitize_output($delivery['order_id']) ?>
          </a>
        </div>
        <div class="cell-muted"><?= format_currency_inr($delivery['total_amount']) ?></div>
      </div>

      <div>
        <span class="cell-muted">Supplier:</span>
        <div style="font-weight: 700;"><?= sanitize_output($delivery['supplier_name']) ?></div>
        <div class="cell-muted"><?= sanitize_output($delivery['category']) ?></div>
      </div>

      <div>
        <span class="cell-muted">Scheduled Expected Date:</span>
        <div style="font-weight: 700;"><?= format_date_in($delivery['expected_date']) ?></div>
      </div>

      <div>
        <span class="cell-muted">Actual Receipt Date:</span>
        <div style="font-weight: 700;"><?= format_date_in($delivery['actual_date']) ?></div>
      </div>

      <div>
        <span class="cell-muted">Delay Metric / Schedule Analysis:</span>
        <div>
          <?php if ($delayInfo['isLate']): ?>
            <span style="color:var(--danger-600); font-weight:700;">
              +<?= $delayInfo['delay'] ?> Days Late (<?= $delayInfo['isOverdue'] ? 'Overdue' : 'Late Delivery' ?>)
            </span>
          <?php elseif (!empty($delayInfo['earlyDays'])): ?>
            <span style="color:var(--success-600); font-weight:700;">
              <?= $delayInfo['earlyDays'] ?> Days Early (On Time)
            </span>
          <?php elseif ($delivery['status'] === 'Cancelled'): ?>
            <span class="cell-muted">Cancelled</span>
          <?php else: ?>
            <span style="color:var(--success-600); font-weight:700;">On Schedule</span>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <span class="cell-muted">Fulfillment Count:</span>
        <div style="font-weight: 700;">
          <?= number_format($delivery['received_qty']) ?> / <?= number_format($delivery['ordered_qty']) ?> units
          (<?= $delivery['ordered_qty'] > 0 ? round(($delivery['received_qty'] / $delivery['ordered_qty']) * 100) : 0 ?>%)
        </div>
      </div>
    </div>

    <div style="margin-top: 1.25rem; padding: 0.85rem; background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--border-radius-sm); font-size: 0.85rem;">
      <span class="cell-muted">Remarks &amp; Notes:</span>
      <div style="margin-top: 0.25rem; font-weight: 500;"><?= nl2br(sanitize_output($delivery['remarks'] ?: 'No consignment remarks recorded.')) ?></div>
    </div>
  </div>
</div>

<!-- Status Transition Audit History -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Delivery Status Transition Audit History</h3>
    <div class="card-subtitle">Chronological record of status changes and fulfillment updates</div>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Timestamp</th>
          <th>Prior Status</th>
          <th>New Status</th>
          <th>Event Description</th>
          <th>Recorded By</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($history)): ?>
          <tr><td colspan="5" style="text-align:center; padding:2rem; color:var(--slate-500);">Initial creation record.</td></tr>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
