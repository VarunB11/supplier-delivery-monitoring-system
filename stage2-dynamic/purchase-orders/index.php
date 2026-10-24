<?php
/**
 * SDPMS - Purchase Orders Management & Directory
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

// Filters
$search = trim($_GET['search'] ?? '');
$supplierFilter = trim($_GET['supplier'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(po.id LIKE :s1 OR s.company_name LIKE :s2 OR po.description LIKE :s3)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}

if ($supplierFilter !== '') {
    $whereClauses[] = "po.supplier_id = :sup";
    $params[':sup'] = $supplierFilter;
}

if ($statusFilter !== '') {
    $whereClauses[] = "po.status = :status";
    $params[':status'] = $statusFilter;
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Active suppliers for filter dropdown
$supListStmt = $db->query("SELECT id, company_name FROM `suppliers` ORDER BY company_name ASC");
$allSuppliers = $supListStmt->fetchAll();

// Pagination
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM `purchase_orders` po JOIN `suppliers` s ON po.supplier_id = s.id {$whereSQL}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$sql = "SELECT po.*, s.company_name AS supplier_name, d.status AS delivery_status, d.id AS delivery_id
        FROM `purchase_orders` po
        JOIN `suppliers` s ON po.supplier_id = s.id
        LEFT JOIN `deliveries` d ON po.id = d.order_id
        {$whereSQL}
        ORDER BY po.order_date DESC, po.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll();

$pageTitle = 'Purchase Order Management';
$pageSubtitle = 'Authorize requisitions, validate item pricing, and schedule supplier fulfillment';
$activePage = 'purchase_orders';

$headerActions = '<a href="' . BASE_URL . '/purchase-orders/create.php" class="btn btn-primary btn-sm">
  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  Create Purchase Order
</a>';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Toolbar -->
<div class="toolbar-container">
  <form method="GET" action="<?= BASE_URL ?>/purchase-orders/index.php" class="toolbar-filters">
    <div class="search-input-group">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="search" value="<?= sanitize_output($search) ?>" placeholder="Search by PO ID, supplier or item...">
    </div>

    <select name="supplier" class="filter-select" onchange="this.form.submit()">
      <option value="">All Suppliers</option>
      <?php foreach ($allSuppliers as $sup): ?>
        <option value="<?= sanitize_output($sup['id']) ?>" <?= $supplierFilter === $sup['id'] ? 'selected' : '' ?>>
          <?= sanitize_output($sup['company_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="status" class="filter-select" onchange="this.form.submit()">
      <option value="">All Statuses</option>
      <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
      <option value="Partially Delivered" <?= $statusFilter === 'Partially Delivered' ? 'selected' : '' ?>>Partially Delivered</option>
      <option value="Completed" <?= $statusFilter === 'Completed' ? 'selected' : '' ?>>Completed</option>
      <option value="Delayed" <?= $statusFilter === 'Delayed' ? 'selected' : '' ?>>Delayed</option>
      <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>

    <button type="submit" class="btn btn-navy btn-sm">Filter</button>
    <?php if ($search || $supplierFilter || $statusFilter): ?>
      <a href="<?= BASE_URL ?>/purchase-orders/index.php" class="btn btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <div style="font-size:0.85rem; font-weight:600; color:var(--slate-500);">
    <?= $totalRecords ?> purchase order(s)
  </div>
</div>

<!-- PO Data Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>PO Number</th>
          <th>Supplier Company</th>
          <th>Material Description</th>
          <th>Quantity</th>
          <th>Total Valuation</th>
          <th>Key Dates</th>
          <th>Order Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($orders)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:3rem; color:var(--slate-500);">
              No purchase orders found matching your filter selection.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($orders as $o): ?>
            <?php $isDelayed = $o['status'] === 'Delayed'; ?>
            <tr class="<?= $isDelayed ? 'row-delayed' : '' ?>">
              <td class="cell-code"><?= sanitize_output($o['id']) ?></td>
              <td>
                <div class="cell-strong"><?= sanitize_output($o['supplier_name']) ?></div>
                <div class="cell-muted"><?= sanitize_output($o['supplier_id']) ?></div>
              </td>
              <td style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= sanitize_output($o['description']) ?>">
                <?= sanitize_output($o['description']) ?>
              </td>
              <td><?= number_format($o['quantity']) ?></td>
              <td class="cell-strong"><?= format_currency_inr($o['total_amount']) ?></td>
              <td>
                <div>Exp: <?= format_date_in($o['expected_date']) ?></div>
                <div class="cell-muted">Ord: <?= format_date_in($o['order_date']) ?></div>
              </td>
              <td><?= render_status_badge($o['status']) ?></td>
              <td>
                <div style="display:flex; gap:0.4rem;">
                  <a href="<?= BASE_URL ?>/purchase-orders/view.php?id=<?= urlencode($o['id']) ?>" class="btn btn-icon btn-sm" title="View Order Details">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </a>
                  <?php if ($o['status'] !== 'Completed' && $o['status'] !== 'Cancelled'): ?>
                    <a href="<?= BASE_URL ?>/purchase-orders/edit.php?id=<?= urlencode($o['id']) ?>" class="btn btn-icon btn-sm" title="Edit Order Details">
                      <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </a>
                    <a href="<?= BASE_URL ?>/purchase-orders/cancel.php?id=<?= urlencode($o['id']) ?>" 
                       class="btn btn-icon btn-sm" 
                       title="Cancel Order"
                       data-confirm="Are you sure you want to cancel purchase order <?= sanitize_output($o['id']) ?>? This will also cancel the linked delivery tracking record.">
                      <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($totalPages > 1): ?>
    <div class="card-footer">
      <div>Page <?= $page ?> of <?= $totalPages ?></div>
      <div style="display:flex; gap:0.5rem;">
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&supplier=<?= urlencode($supplierFilter) ?>&status=<?= urlencode($statusFilter) ?>" class="btn btn-outline btn-sm">&laquo; Prev</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&supplier=<?= urlencode($supplierFilter) ?>&status=<?= urlencode($statusFilter) ?>" class="btn btn-outline btn-sm">Next &raquo;</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
