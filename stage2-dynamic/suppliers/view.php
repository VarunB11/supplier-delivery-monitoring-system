<?php
/**
 * SDPMS - Detailed Supplier Profile View
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

$id = trim($_GET['id'] ?? '');
$stmt = $db->prepare("SELECT * FROM `suppliers` WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $id]);
$supplier = $stmt->fetch();

if (!$supplier) {
    set_flash_message('danger', "Supplier '{$id}' does not exist.");
    header('Location: ' . BASE_URL . '/suppliers/index.php');
    exit;
}

// Supplier Metrics
$metrics = get_supplier_performance_metrics($db, $id);

// Linked Purchase Orders
$poStmt = $db->prepare(
    "SELECT po.*, d.status AS delivery_status, d.actual_date 
     FROM `purchase_orders` po
     LEFT JOIN `deliveries` d ON po.id = d.order_id
     WHERE po.supplier_id = :id 
     ORDER BY po.order_date DESC"
);
$poStmt->execute([':id' => $id]);
$linkedOrders = $poStmt->fetchAll();

$pageTitle = $supplier['company_name'] . ' (' . $supplier['id'] . ')';
$pageSubtitle = 'Master Vendor Profile, Contact Directory and Performance Summary';
$activePage = 'suppliers';

$headerActions = '
  <a href="' . BASE_URL . '/purchase-orders/create.php?supplier_id=' . urlencode($id) . '" class="btn btn-primary btn-sm">Issue Purchase Order</a>
  <a href="' . BASE_URL . '/suppliers/edit.php?id=' . urlencode($id) . '" class="btn btn-outline btn-sm">Edit Profile</a>
';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Profile & Metrics Card -->
<div class="card" style="margin-bottom: 2rem;">
  <div class="card-header">
    <div>
      <h3 class="card-title"><?= sanitize_output($supplier['company_name']) ?></h3>
      <div class="card-subtitle">Category: <?= sanitize_output($supplier['category']) ?> &bull; Reg: <?= format_date_in($supplier['registration_date']) ?></div>
    </div>
    <div><?= render_status_badge($supplier['status']) ?></div>
  </div>
  <div class="card-body">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; font-size: 0.9rem;">
      <div>
        <span class="cell-muted">Contact Person:</span>
        <div style="font-weight: 600; font-size: 1rem;"><?= sanitize_output($supplier['contact_person']) ?></div>
      </div>
      <div>
        <span class="cell-muted">Email Channel:</span>
        <div><a href="mailto:<?= sanitize_output($supplier['email']) ?>" style="color:var(--primary-600);"><?= sanitize_output($supplier['email']) ?></a></div>
      </div>
      <div>
        <span class="cell-muted">Phone Number:</span>
        <div><?= sanitize_output($supplier['phone'] ?: '—') ?></div>
      </div>
      <div>
        <span class="cell-muted">Location / City:</span>
        <div><?= sanitize_output($supplier['city']) ?></div>
      </div>
      <div style="grid-column: 1 / -1;">
        <span class="cell-muted">Works / Address:</span>
        <div><?= sanitize_output($supplier['address'] ?: 'Not specified') ?></div>
      </div>
    </div>

    <hr style="margin: 1.5rem 0; border: 0; border-top: 1px solid var(--slate-200);">

    <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--navy-900); margin-bottom: 1rem;">Calculated Delivery Performance (From MySQL Records)</h4>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; text-align: center;">
      <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--border-radius-sm); padding: 1rem;">
        <div style="font-size: 0.72rem; color: var(--slate-500); font-weight: 600; text-transform: uppercase;">Completed Orders</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--navy-900);"><?= $metrics['eligible_completed'] ?></div>
        <div style="font-size: 0.7rem; color: var(--slate-500);">of <?= $metrics['total_orders'] ?> total POs</div>
      </div>

      <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--border-radius-sm); padding: 1rem;">
        <div style="font-size: 0.72rem; color: var(--slate-500); font-weight: 600; text-transform: uppercase;">On-Time Delivery %</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--success-600);">
          <?= $metrics['on_time_percentage'] !== null ? $metrics['on_time_percentage'] . '%' : 'N/A' ?>
        </div>
        <div style="font-size: 0.7rem; color: var(--slate-500);"><?= $metrics['on_time_count'] ?> on-time, <?= $metrics['late_count'] ?> late</div>
      </div>

      <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--border-radius-sm); padding: 1rem;">
        <div style="font-size: 0.72rem; color: var(--slate-500); font-weight: 600; text-transform: uppercase;">Avg Delivery Delay</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: <?= $metrics['avg_delay_days'] > 0 ? 'var(--danger-600)' : 'var(--success-600)' ?>;">
          <?= $metrics['avg_delay_days'] ?> d
        </div>
        <div style="font-size: 0.7rem; color: var(--slate-500);">Positive delay avg</div>
      </div>

      <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--border-radius-sm); padding: 1rem;">
        <div style="font-size: 0.72rem; color: var(--slate-500); font-weight: 600; text-transform: uppercase;">Qty Fulfilment</div>
        <div style="font-size: 1.5rem; font-weight: 800; color: var(--navy-900);"><?= $metrics['qty_fulfilment_pct'] ?>%</div>
        <div style="font-size: 0.7rem; color: var(--slate-500);"><?= number_format($metrics['total_received_qty']) ?> / <?= number_format($metrics['total_ordered_qty']) ?> units</div>
      </div>

      <div style="background: var(--slate-50); border: 1px solid var(--slate-200); border-radius: var(--border-radius-sm); padding: 1rem;">
        <div style="font-size: 0.72rem; color: var(--slate-500); font-weight: 600; text-transform: uppercase;">Reliability Tier</div>
        <div style="margin-top: 0.5rem;"><?= render_rating_badge($metrics['rating']) ?></div>
        <div style="font-size: 0.7rem; color: var(--slate-500); margin-top: 0.35rem;">Score: <?= $metrics['overall_score'] !== null ? $metrics['overall_score'] . '/100' : 'Unrated' ?></div>
      </div>
    </div>
  </div>
</div>

<!-- Linked Purchase Orders Table -->
<div class="card">
  <div class="card-header">
    <h3 class="card-title">Commercial Purchase Orders Issued</h3>
    <a href="<?= BASE_URL ?>/purchase-orders/create.php?supplier_id=<?= urlencode($id) ?>" class="btn btn-outline btn-sm">Issue New PO</a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>PO Number</th>
          <th>Order Date</th>
          <th>Expected Date</th>
          <th>Material Description</th>
          <th>Quantity</th>
          <th>Total Value</th>
          <th>PO Status</th>
          <th>Delivery Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($linkedOrders)): ?>
          <tr>
            <td colspan="9" style="text-align: center; padding: 2.5rem; color: var(--slate-500);">
              No purchase orders have been issued to this vendor yet.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($linkedOrders as $po): ?>
            <tr>
              <td class="cell-code"><?= sanitize_output($po['id']) ?></td>
              <td><?= format_date_in($po['order_date']) ?></td>
              <td><?= format_date_in($po['expected_date']) ?></td>
              <td style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= sanitize_output($po['description']) ?>">
                <?= sanitize_output($po['description']) ?>
              </td>
              <td><?= number_format($po['quantity']) ?></td>
              <td class="cell-strong"><?= format_currency_inr($po['total_amount']) ?></td>
              <td><?= render_status_badge($po['status']) ?></td>
              <td><?= render_status_badge($po['delivery_status'] ?? 'Pending') ?></td>
              <td>
                <a href="<?= BASE_URL ?>/purchase-orders/view.php?id=<?= urlencode($po['id']) ?>" class="btn btn-outline btn-sm">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
