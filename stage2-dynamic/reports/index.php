<?php
/**
 * SDPMS - Reports & Analytics Generation Center
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

// Filter parameters
$reportType = trim($_GET['type'] ?? 'orders');
$supplierId = trim($_GET['supplier'] ?? '');
$status     = trim($_GET['status'] ?? '');
$startDate  = trim($_GET['start_date'] ?? '');
$endDate    = trim($_GET['end_date'] ?? '');

$suppliers = $db->query("SELECT id, company_name FROM `suppliers` ORDER BY company_name ASC")->fetchAll();

$headers = [];
$rows = [];
$totalValuation = 0.0;
$delayedCount = 0;
$onTimeCount = 0;

if ($reportType === 'orders') {
    $headers = ['PO Number', 'Supplier Organization', 'Order Date', 'Expected Date', 'Material Description', 'Quantity', 'Total Amount', 'Status'];

    $where = [];
    $params = [];

    if ($supplierId !== '') { $where[] = "po.supplier_id = :sup"; $params[':sup'] = $supplierId; }
    if ($status !== '') { $where[] = "po.status = :st"; $params[':st'] = $status; }
    if ($startDate !== '') { $where[] = "po.order_date >= :sdate"; $params[':sdate'] = $startDate; }
    if ($endDate !== '') { $where[] = "po.order_date <= :edate"; $params[':edate'] = $endDate; }

    $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT po.*, s.company_name FROM `purchase_orders` po JOIN `suppliers` s ON po.supplier_id = s.id {$whereSQL} ORDER BY po.order_date DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    foreach ($records as $r) {
        $totalValuation += (float)$r['total_amount'];
        if ($r['status'] === 'Delayed') $delayedCount++;
        if ($r['status'] === 'Completed') $onTimeCount++;

        $rows[] = [
            '<span class="cell-code">' . sanitize_output($r['id']) . '</span>',
            '<div class="cell-strong">' . sanitize_output($r['company_name']) . '</div><div class="cell-muted">' . sanitize_output($r['supplier_id']) . '</div>',
            format_date_in($r['order_date']),
            format_date_in($r['expected_date']),
            '<span title="' . sanitize_output($r['description']) . '">' . sanitize_output(mb_strimwidth($r['description'], 0, 35, '...')) . '</span>',
            number_format($r['quantity']),
            '<span class="cell-strong">' . format_currency_inr($r['total_amount']) . '</span>',
            render_status_badge($r['status'])
        ];
    }

} elseif ($reportType === 'deliveries') {
    $headers = ['Delivery ID', 'PO Number', 'Supplier Organization', 'Expected Date', 'Actual Date', 'Received / Ordered', 'Delay Metric', 'Delivery Status'];

    $where = [];
    $params = [];

    if ($supplierId !== '') { $where[] = "d.supplier_id = :sup"; $params[':sup'] = $supplierId; }
    if ($status !== '') { $where[] = "d.status = :st"; $params[':st'] = $status; }
    if ($startDate !== '') { $where[] = "d.expected_date >= :sdate"; $params[':sdate'] = $startDate; }
    if ($endDate !== '') { $where[] = "d.expected_date <= :edate"; $params[':edate'] = $endDate; }

    $whereSQL = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
    $sql = "SELECT d.*, s.company_name, po.total_amount 
            FROM `deliveries` d 
            JOIN `suppliers` s ON d.supplier_id = s.id 
            JOIN `purchase_orders` po ON d.order_id = po.id
            {$whereSQL} 
            ORDER BY d.expected_date ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    foreach ($records as $r) {
        $totalValuation += (float)$r['total_amount'];
        $info = calculate_delay_days($r['expected_date'], $r['actual_date'], $r['status']);
        if ($info['isLate']) $delayedCount++;
        if ($r['status'] === 'Delivered' && !$info['isLate']) $onTimeCount++;

        $delayBadge = $info['isLate'] 
            ? '<span style="color:var(--danger-600); font-weight:700;">+' . $info['delay'] . 'd late</span>'
            : (!empty($info['earlyDays']) ? '<span style="color:var(--success-600);">' . $info['earlyDays'] . 'd early</span>' : '<span style="color:var(--success-600);">On Time</span>');

        $rows[] = [
            '<span class="cell-code">' . sanitize_output($r['id']) . '</span>',
            '<span class="cell-code" style="color:var(--primary-600);">' . sanitize_output($r['order_id']) . '</span>',
            '<div class="cell-strong">' . sanitize_output($r['company_name']) . '</div>',
            format_date_in($r['expected_date']),
            format_date_in($r['actual_date']),
            number_format($r['received_qty']) . ' / ' . number_format($r['ordered_qty']),
            $delayBadge,
            render_status_badge($r['status'])
        ];
    }

} elseif ($reportType === 'delayed') {
    $headers = ['PO Number', 'Delivery ID', 'Supplier Organization', 'Expected Date', 'Days Overdue/Delay', 'Ordered Qty', 'Status', 'Remarks / Delay Reason'];

    $where = ["(d.status = 'Delayed' OR (d.status = 'Pending' AND d.expected_date < CURDATE()))"];
    $params = [];

    if ($supplierId !== '') { $where[] = "d.supplier_id = :sup"; $params[':sup'] = $supplierId; }

    $whereSQL = "WHERE " . implode(" AND ", $where);
    $sql = "SELECT d.*, s.company_name, po.total_amount 
            FROM `deliveries` d 
            JOIN `suppliers` s ON d.supplier_id = s.id 
            JOIN `purchase_orders` po ON d.order_id = po.id
            {$whereSQL} 
            ORDER BY d.expected_date ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll();

    foreach ($records as $r) {
        $totalValuation += (float)$r['total_amount'];
        $info = calculate_delay_days($r['expected_date'], $r['actual_date'], $r['status']);
        $delayedCount++;

        $rows[] = [
            '<span class="cell-code">' . sanitize_output($r['order_id']) . '</span>',
            '<span class="cell-code">' . sanitize_output($r['id']) . '</span>',
            '<div class="cell-strong">' . sanitize_output($r['company_name']) . '</div>',
            format_date_in($r['expected_date']),
            '<span style="color:var(--danger-600); font-weight:700;">+' . $info['delay'] . ' Days</span>',
            number_format($r['ordered_qty']),
            render_status_badge($r['status']),
            '<span title="' . sanitize_output($r['remarks']) . '">' . sanitize_output($r['remarks'] ?: '—') . '</span>'
        ];
    }

} elseif ($reportType === 'performance') {
    $headers = ['Supplier ID', 'Company Name', 'Category', 'Eligible Completed', 'On-Time Count', 'On-Time %', 'Avg Delay', 'Composite Score', 'Rating Tier'];

    $perfList = get_supplier_performance_metrics($db);
    foreach ($perfList as $m) {
        if ($supplierId !== '' && $m['supplier_id'] !== $supplierId) continue;
        if ($status !== '' && $m['supplier_status'] !== $status) continue;

        if ($m['late_count'] > 0) $delayedCount += $m['late_count'];
        $onTimeCount += $m['on_time_count'];

        $rows[] = [
            '<span class="cell-code">' . sanitize_output($m['supplier_id']) . '</span>',
            '<div class="cell-strong">' . sanitize_output($m['company_name']) . '</div>',
            sanitize_output($m['category']),
            $m['eligible_completed'],
            $m['on_time_count'],
            ($m['on_time_percentage'] !== null ? $m['on_time_percentage'] . '%' : 'N/A'),
            $m['avg_delay_days'] . ' d',
            '<span style="font-weight:800; font-size:1.05rem;">' . ($m['overall_score'] !== null ? $m['overall_score'] . '/100' : '—') . '</span>',
            render_rating_badge($m['rating'])
        ];
    }
}

// Build query string for CSV export
$exportQuery = http_build_query([
    'type'       => $reportType,
    'supplier'   => $supplierId,
    'status'     => $status,
    'start_date' => $startDate,
    'end_date'   => $endDate
]);

$pageTitle = 'Procurement Reports & Analytics';
$pageSubtitle = 'Generate filtered audit reports, print summaries and export CSV tables from MySQL';
$activePage = 'reports';

$headerActions = '
  <button onclick="window.print()" class="btn btn-outline btn-sm">
    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
    Print Report
  </button>
  <a href="' . BASE_URL . '/reports/export_csv.php?' . $exportQuery . '" class="btn btn-primary btn-sm">
    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
    Export CSV
  </a>
';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Filter Panel -->
<div class="card" style="margin-bottom:1.5rem;">
  <div class="card-header">
    <h3 class="card-title">
      <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
      Report Generator Parameters
    </h3>
  </div>
  <div class="card-body">
    <form method="GET" action="<?= BASE_URL ?>/reports/index.php">
      <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:1rem; align-items:end;">
        <div>
          <label class="form-label" for="type">Report Module Type</label>
          <select id="type" name="type" class="form-control" onchange="this.form.submit()">
            <option value="orders" <?= $reportType === 'orders' ? 'selected' : '' ?>>All Purchase Orders</option>
            <option value="deliveries" <?= $reportType === 'deliveries' ? 'selected' : '' ?>>Consignment Deliveries</option>
            <option value="delayed" <?= $reportType === 'delayed' ? 'selected' : '' ?>>Delayed &amp; Overdue Deliveries</option>
            <option value="performance" <?= $reportType === 'performance' ? 'selected' : '' ?>>Supplier Performance Scorecards</option>
          </select>
        </div>

        <div>
          <label class="form-label" for="supplier">Filter Supplier</label>
          <select id="supplier" name="supplier" class="form-control">
            <option value="">All Suppliers</option>
            <?php foreach ($suppliers as $s): ?>
              <option value="<?= sanitize_output($s['id']) ?>" <?= $supplierId === $s['id'] ? 'selected' : '' ?>>
                <?= sanitize_output($s['company_name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="form-label" for="status">Filter Status</label>
          <select id="status" name="status" class="form-control">
            <option value="">All Statuses</option>
            <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
            <option value="Delivered" <?= $status === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
            <option value="Partially Delivered" <?= $status === 'Partially Delivered' ? 'selected' : '' ?>>Partially Delivered</option>
            <option value="Delayed" <?= $status === 'Delayed' ? 'selected' : '' ?>>Delayed</option>
            <option value="Pending" <?= $status === 'Pending' ? 'selected' : '' ?>>Pending</option>
            <option value="Cancelled" <?= $status === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
          </select>
        </div>

        <div>
          <label class="form-label" for="start_date">From Date</label>
          <input type="date" id="start_date" name="start_date" class="form-control" value="<?= sanitize_output($startDate) ?>">
        </div>

        <div>
          <label class="form-label" for="end_date">To Date</label>
          <input type="date" id="end_date" name="end_date" class="form-control" value="<?= sanitize_output($endDate) ?>">
        </div>

        <div style="display:flex; gap:0.5rem;">
          <button type="submit" class="btn btn-navy" style="flex:1;">Apply Filters</button>
          <a href="<?= BASE_URL ?>/reports/index.php" class="btn btn-outline" style="flex:1; text-align:center;">Reset</a>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Summary KPIs -->
<div class="kpi-grid">
  <div class="kpi-card accent-primary">
    <div>
      <div class="kpi-label">Filtered Records</div>
      <div class="kpi-value"><?= count($rows) ?></div>
      <div class="kpi-subtext">Rows matching criteria</div>
    </div>
  </div>

  <div class="kpi-card accent-info">
    <div>
      <div class="kpi-label">Cumulative Value</div>
      <div class="kpi-value"><?= format_currency_inr($totalValuation) ?></div>
      <div class="kpi-subtext">Sum of filtered orders</div>
    </div>
  </div>

  <div class="kpi-card accent-danger">
    <div>
      <div class="kpi-label">Delayed Consignments</div>
      <div class="kpi-value"><?= $delayedCount ?></div>
      <div class="kpi-subtext negative">Late or overdue count</div>
    </div>
  </div>

  <div class="kpi-card accent-success">
    <div>
      <div class="kpi-label">On-Time Deliveries</div>
      <div class="kpi-value"><?= $onTimeCount ?></div>
      <div class="kpi-subtext positive">Compliant records</div>
    </div>
  </div>
</div>

<!-- Printable Header -->
<div class="print-header">
  <h1 style="font-size:1.4rem; color:var(--navy-950); margin-bottom:0.25rem;">PROCURETRACK &bull; AUDITED PROCUREMENT REPORT</h1>
  <p style="font-size:0.85rem; color:var(--slate-600);">Generated on <?= date('d M Y H:i:s') ?> &bull; Internal Manufacturing Audit Document</p>
  <hr style="margin:1rem 0; border:0; border-top:1px solid #ccc;">
</div>

<!-- Report Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <?php foreach ($headers as $h): ?>
            <th><?= $h ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr>
            <td colspan="<?= count($headers) ?>" style="text-align:center; padding:3rem; color:var(--slate-500);">
              No records found for the specified report filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($rows as $rowCols): ?>
            <tr>
              <?php foreach ($rowCols as $col): ?>
                <td><?= $col ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
