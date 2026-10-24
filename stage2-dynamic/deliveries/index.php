<?php
/**
 * SDPMS - Delivery Consignment Tracking Directory
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
    $whereClauses[] = "(d.id LIKE :s1 OR d.order_id LIKE :s2 OR s.company_name LIKE :s3)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
}

if ($supplierFilter !== '') {
    $whereClauses[] = "d.supplier_id = :sup";
    $params[':sup'] = $supplierFilter;
}

if ($statusFilter !== '') {
    $whereClauses[] = "d.status = :status";
    $params[':status'] = $statusFilter;
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Suppliers for filter dropdown
$suppliers = $db->query("SELECT id, company_name FROM `suppliers` ORDER BY company_name ASC")->fetchAll();

// Pagination
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countSql = "SELECT COUNT(*) FROM `deliveries` d JOIN `suppliers` s ON d.supplier_id = s.id {$whereSQL}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

$sql = "SELECT d.*, s.company_name AS supplier_name, po.description 
        FROM `deliveries` d
        JOIN `suppliers` s ON d.supplier_id = s.id
        JOIN `purchase_orders` po ON d.order_id = po.id
        {$whereSQL}
        ORDER BY d.expected_date ASC, d.id DESC
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$deliveries = $stmt->fetchAll();

$pageTitle = 'Consignment Delivery Tracking';
$pageSubtitle = 'Monitor physical goods receipts against expected schedules and track delay days';
$activePage = 'deliveries';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Toolbar -->
<div class="toolbar-container">
  <form method="GET" action="<?= BASE_URL ?>/deliveries/index.php" class="toolbar-filters">
    <div class="search-input-group">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="search" value="<?= sanitize_output($search) ?>" placeholder="Search Delivery ID, PO number or supplier...">
    </div>

    <select name="supplier" class="filter-select" onchange="this.form.submit()">
      <option value="">All Suppliers</option>
      <?php foreach ($suppliers as $sup): ?>
        <option value="<?= sanitize_output($sup['id']) ?>" <?= $supplierFilter === $sup['id'] ? 'selected' : '' ?>>
          <?= sanitize_output($sup['company_name']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="status" class="filter-select" onchange="this.form.submit()">
      <option value="">All Statuses</option>
      <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
      <option value="Partially Delivered" <?= $statusFilter === 'Partially Delivered' ? 'selected' : '' ?>>Partially Delivered</option>
      <option value="Delivered" <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
      <option value="Delayed" <?= $statusFilter === 'Delayed' ? 'selected' : '' ?>>Delayed</option>
      <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
    </select>

    <button type="submit" class="btn btn-navy btn-sm">Filter</button>
    <?php if ($search || $supplierFilter || $statusFilter): ?>
      <a href="<?= BASE_URL ?>/deliveries/index.php" class="btn btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <div style="font-size:0.85rem; font-weight:600; color:var(--slate-500);">
    <?= $totalRecords ?> consignment record(s)
  </div>
</div>

<!-- Deliveries Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Delivery ID</th>
          <th>PO &amp; Supplier</th>
          <th>Delivery Schedule</th>
          <th>Fulfillment Progress</th>
          <th>Delay / Lead Status</th>
          <th>Status</th>
          <th>Remarks</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($deliveries)): ?>
          <tr>
            <td colspan="8" style="text-align:center; padding:3rem; color:var(--slate-500);">
              No delivery records found matching your filter selection.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($deliveries as $d): ?>
            <?php 
              $delayInfo = calculate_delay_days($d['expected_date'], $d['actual_date'], $d['status']);
              $isLate = $delayInfo['isLate'];
              $qtyPct = $d['ordered_qty'] > 0 ? min(100, round(($d['received_qty'] / $d['ordered_qty']) * 100)) : 0;
            ?>
            <tr class="<?= $isLate ? 'row-delayed' : '' ?>">
              <td class="cell-code"><?= sanitize_output($d['id']) ?></td>
              <td>
                <div class="cell-code" style="color:var(--primary-600);">
                  <a href="<?= BASE_URL ?>/purchase-orders/view.php?id=<?= urlencode($d['order_id']) ?>">
                    <?= sanitize_output($d['order_id']) ?>
                  </a>
                </div>
                <div class="cell-muted"><?= sanitize_output($d['supplier_name']) ?></div>
              </td>
              <td>
                <div>Exp: <?= format_date_in($d['expected_date']) ?></div>
                <div class="cell-muted">Act: <?= format_date_in($d['actual_date']) ?></div>
              </td>
              <td>
                <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.2rem;">
                  <span><?= number_format($d['received_qty']) ?> / <?= number_format($d['ordered_qty']) ?></span>
                  <span style="font-weight:600;"><?= $qtyPct ?>%</span>
                </div>
                <div class="progress-container" style="height:6px;">
                  <div class="progress-bar <?= $qtyPct === 100 ? 'progress-success' : ($qtyPct > 0 ? 'progress-warning' : 'progress-primary') ?>" style="width:<?= $qtyPct ?>%;"></div>
                </div>
              </td>
              <td>
                <?php if ($isLate): ?>
                  <span style="color:var(--danger-600); font-weight:600; display:inline-flex; align-items:center; gap:0.25rem;">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    +<?= $delayInfo['delay'] ?>d <?= $delayInfo['isOverdue'] ? 'overdue' : 'late' ?>
                  </span>
                <?php elseif (!empty($delayInfo['earlyDays'])): ?>
                  <span style="color:var(--success-600); font-weight:600;">
                    <?= $delayInfo['earlyDays'] ?>d early
                  </span>
                <?php elseif ($d['status'] === 'Cancelled'): ?>
                  <span class="cell-muted">—</span>
                <?php else: ?>
                  <span style="color:var(--success-600); font-weight:600;">On Schedule</span>
                <?php endif; ?>
              </td>
              <td><?= render_status_badge($d['status']) ?></td>
              <td style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= sanitize_output($d['remarks']) ?>">
                <?= sanitize_output($d['remarks'] ?: '—') ?>
              </td>
              <td>
                <div style="display:flex; gap:0.4rem;">
                  <a href="<?= BASE_URL ?>/deliveries/view.php?id=<?= urlencode($d['id']) ?>" class="btn btn-icon btn-sm" title="View Details &amp; History">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </a>
                  <?php if ($d['status'] !== 'Cancelled'): ?>
                    <a href="<?= BASE_URL ?>/deliveries/edit.php?id=<?= urlencode($d['id']) ?>" class="btn btn-icon btn-sm" title="Update Delivery Status">
                      <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
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
