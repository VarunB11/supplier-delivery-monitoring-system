<?php
/**
 * SDPMS - Dynamic Executive Procurement Dashboard
 * Real-time database metrics, Chart.js integrations & operational alerts
 */
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

require_login();

$db = Database::getConnection();
$kpis = get_system_dashboard_kpis($db);

// Fetch Delayed Deliveries (status Delayed or Overdue Pending)
$delayedStmt = $db->query(
    "SELECT d.*, s.company_name AS supplier_name, po.description 
     FROM `deliveries` d
     JOIN `suppliers` s ON d.supplier_id = s.id
     JOIN `purchase_orders` po ON d.order_id = po.id
     WHERE d.status = 'Delayed' 
        OR (d.status = 'Pending' AND d.expected_date < CURDATE())
     ORDER BY d.expected_date ASC"
);
$delayedDeliveries = $delayedStmt->fetchAll();

// Fetch Recent Purchase Orders (Top 5)
$recentOrdersStmt = $db->query(
    "SELECT po.*, s.company_name AS supplier_name 
     FROM `purchase_orders` po
     JOIN `suppliers` s ON po.supplier_id = s.id
     ORDER BY po.order_date DESC, po.created_at DESC 
     LIMIT 5"
);
$recentOrders = $recentOrdersStmt->fetchAll();

// Fetch Upcoming Deliveries (Top 5 Pending / Partially Delivered)
$upcomingStmt = $db->query(
    "SELECT d.*, s.company_name AS supplier_name 
     FROM `deliveries` d
     JOIN `suppliers` s ON d.supplier_id = s.id
     WHERE d.status IN ('Pending', 'Partially Delivered')
     ORDER BY d.expected_date ASC 
     LIMIT 5"
);
$upcomingDeliveries = $upcomingStmt->fetchAll();

// Monthly Order Trend Data (last 6 months)
$trendStmt = $db->query(
    "SELECT DATE_FORMAT(order_date, '%b %Y') AS month_label,
            COUNT(*) AS total_orders,
            SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) AS completed_orders
     FROM `purchase_orders`
     WHERE status != 'Cancelled'
     GROUP BY DATE_FORMAT(order_date, '%Y-%m'), month_label
     ORDER BY MIN(order_date) ASC
     LIMIT 6"
);
$trendRows = $trendStmt->fetchAll();
$trendLabels = array_column($trendRows, 'month_label');
$trendTotalOrders = array_map('intval', array_column($trendRows, 'total_orders'));
$trendCompletedOrders = array_map('intval', array_column($trendRows, 'completed_orders'));

// Status distribution for Doughnut chart
$statusStmt = $db->query(
    "SELECT status, COUNT(*) AS count 
     FROM `deliveries` 
     GROUP BY status"
);
$statusRows = $statusStmt->fetchAll();
$statusMap = array_column($statusRows, 'count', 'status');

$pageTitle = 'Procurement Executive Dashboard';
$pageSubtitle = 'Real-time database statistics, delivery tracking and supplier reliability metrics';
$activePage = 'dashboard';

require_once __DIR__ . '/includes/header.php';
?>

<!-- In-app Alert Notifications Container -->
<?php if ($kpis['delayedOrders'] > 0): ?>
  <div class="alert-box alert-danger">
    <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <div>
      <div class="alert-title">Procurement Notice: <?= $kpis['delayedOrders'] ?> Delayed Deliveries Detected in Database</div>
      <div>Immediate follow-up required with affected suppliers to prevent manufacturing assembly line stoppages.</div>
    </div>
  </div>
<?php endif; ?>

<!-- KPI Summary Cards Grid -->
<div class="kpi-grid">
  <div class="kpi-card accent-primary">
    <div>
      <div class="kpi-label">Active Suppliers</div>
      <div class="kpi-value"><?= $kpis['activeSuppliers'] ?></div>
      <div class="kpi-subtext"><?= $kpis['totalSuppliers'] ?> registered vendors</div>
    </div>
    <div class="kpi-icon-wrap">
      <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
    </div>
  </div>

  <div class="kpi-card accent-info">
    <div>
      <div class="kpi-label">Total Purchase Orders</div>
      <div class="kpi-value"><?= $kpis['totalOrders'] ?></div>
      <div class="kpi-subtext">Active procurements in MySQL</div>
    </div>
    <div class="kpi-icon-wrap">
      <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
    </div>
  </div>

  <div class="kpi-card accent-warning">
    <div>
      <div class="kpi-label">Awaiting Delivery</div>
      <div class="kpi-value"><?= $kpis['awaitingDelivery'] ?></div>
      <div class="kpi-subtext">Pending / partial consignments</div>
    </div>
    <div class="kpi-icon-wrap">
      <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
  </div>

  <div class="kpi-card accent-danger">
    <div>
      <div class="kpi-label">Delayed Orders</div>
      <div class="kpi-value"><?= $kpis['delayedOrders'] ?></div>
      <div class="kpi-subtext negative">Requires intervention</div>
    </div>
    <div class="kpi-icon-wrap">
      <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    </div>
  </div>

  <div class="kpi-card accent-success">
    <div>
      <div class="kpi-label">Delivered On Time</div>
      <div class="kpi-value"><?= $kpis['onTimeDeliveries'] ?></div>
      <div class="kpi-subtext positive">Zero delay consignments</div>
    </div>
    <div class="kpi-icon-wrap">
      <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </div>
  </div>

  <div class="kpi-card accent-primary">
    <div>
      <div class="kpi-label">On-Time Delivery %</div>
      <div class="kpi-value"><?= $kpis['onTimePercentage'] ?>%</div>
      <div class="kpi-subtext">Overall benchmark</div>
    </div>
    <div class="kpi-icon-wrap">
      <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
    </div>
  </div>
</div>

<!-- Charts Grid -->
<div class="grid-2col">
  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
          Monthly Purchase Order &amp; Delivery Trends
        </h3>
        <div class="card-subtitle">Monthly distribution of order placement vs on-time fulfillment</div>
      </div>
    </div>
    <div class="card-body">
      <div style="height: 280px; position:relative;">
        <canvas id="chart-monthly-trend"></canvas>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div>
        <h3 class="card-title">
          <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
          Consignment Status Breakdown
        </h3>
        <div class="card-subtitle">Distribution across active records in MySQL</div>
      </div>
    </div>
    <div class="card-body">
      <div style="height: 280px; position:relative;">
        <canvas id="chart-status-breakdown"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- Delayed Deliveries Requiring Attention -->
<div class="card" style="margin-bottom: 2rem;">
  <div class="card-header" style="background:#fff8f8;">
    <div>
      <h3 class="card-title" style="color:var(--danger-600);">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        Delayed Deliveries Requiring Urgent Procurement Attention
      </h3>
      <div class="card-subtitle">Orders past expected delivery date or flagged late by supplier</div>
    </div>
    <a href="<?= BASE_URL ?>/deliveries/index.php?status=Delayed" class="btn btn-outline-danger btn-sm">Manage in Deliveries</a>
  </div>
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>PO Number</th>
          <th>Supplier Company</th>
          <th>Expected Date</th>
          <th>Ordered Qty</th>
          <th>Days Overdue / Delay</th>
          <th>Status</th>
          <th>Remarks / Cause</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($delayedDeliveries)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:1.5rem; color:var(--success-600);">
              No overdue or delayed deliveries in MySQL! All orders running on schedule.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($delayedDeliveries as $d): ?>
            <?php $delayInfo = calculate_delay_days($d['expected_date'], $d['actual_date'], $d['status']); ?>
            <tr class="row-delayed">
              <td class="cell-code"><?= sanitize_output($d['order_id']) ?></td>
              <td class="cell-strong"><?= sanitize_output($d['supplier_name']) ?></td>
              <td><?= format_date_in($d['expected_date']) ?></td>
              <td><?= number_format($d['ordered_qty']) ?> units</td>
              <td>
                <span style="color:var(--danger-600); font-weight:700;">
                  +<?= $delayInfo['delay'] ?> Days (<?= $delayInfo['isOverdue'] ? 'Overdue' : 'Late' ?>)
                </span>
              </td>
              <td><?= render_status_badge($d['status']) ?></td>
              <td style="font-size:0.8rem;"><?= sanitize_output($d['remarks'] ?? 'No notes recorded') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Equal 2-Column: Recent POs & Upcoming Deliveries -->
<div class="grid-equal-2col">
  <!-- Recent POs -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Recent Purchase Orders</h3>
      <a href="<?= BASE_URL ?>/purchase-orders/index.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>PO ID</th>
            <th>Supplier</th>
            <th>Total Value</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recentOrders as $po): ?>
            <tr>
              <td class="cell-code"><?= sanitize_output($po['id']) ?></td>
              <td>
                <div class="cell-strong"><?= sanitize_output($po['supplier_name']) ?></div>
                <div class="cell-muted"><?= format_date_in($po['order_date']) ?></div>
              </td>
              <td class="cell-strong"><?= format_currency_inr($po['total_amount']) ?></td>
              <td><?= render_status_badge($po['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Upcoming Deliveries -->
  <div class="card">
    <div class="card-header">
      <h3 class="card-title">Upcoming Expected Deliveries</h3>
      <a href="<?= BASE_URL ?>/deliveries/index.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>Delivery ID</th>
            <th>PO ID</th>
            <th>Expected Date</th>
            <th>Qty</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($upcomingDeliveries as $ud): ?>
            <tr>
              <td class="cell-code"><?= sanitize_output($ud['id']) ?></td>
              <td class="cell-code" style="color:var(--primary-600);"><?= sanitize_output($ud['order_id']) ?></td>
              <td><?= format_date_in($ud['expected_date']) ?></td>
              <td><?= number_format($ud['ordered_qty']) ?></td>
              <td><?= render_status_badge($ud['status']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.Chart) {
    // Trend Chart
    const trendCtx = document.getElementById('chart-monthly-trend').getContext('2d');
    new Chart(trendCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode(!empty($trendLabels) ? $trendLabels : ['No Data']) ?>,
        datasets: [
          {
            label: 'Purchase Orders Placed',
            data: <?= json_encode(!empty($trendTotalOrders) ? $trendTotalOrders : [0]) ?>,
            borderColor: '#1d4ed8',
            backgroundColor: 'rgba(29, 78, 216, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
          },
          {
            label: 'Completed On-Time',
            data: <?= json_encode(!empty($trendCompletedOrders) ? $trendCompletedOrders : [0]) ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16, 185, 129, 0.1)',
            borderWidth: 2,
            tension: 0.3,
            fill: true
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f1f5f9' } },
          x: { grid: { display: false } }
        },
        plugins: {
          legend: { position: 'top', labels: { font: { family: 'Inter', weight: 600 } } }
        }
      }
    });

    // Breakdown Chart
    const breakdownCtx = document.getElementById('chart-status-breakdown').getContext('2d');
    new Chart(breakdownCtx, {
      type: 'doughnut',
      data: {
        labels: ['Delivered', 'Delayed', 'Pending', 'Partially Delivered', 'Cancelled'],
        datasets: [{
          data: [
            <?= (int)($statusMap['Delivered'] ?? 0) ?>,
            <?= (int)($statusMap['Delayed'] ?? 0) ?>,
            <?= (int)($statusMap['Pending'] ?? 0) ?>,
            <?= (int)($statusMap['Partially Delivered'] ?? 0) ?>,
            <?= (int)($statusMap['Cancelled'] ?? 0) ?>
          ],
          backgroundColor: ['#10b981', '#ef4444', '#0ea5e9', '#f59e0b', '#94a3b8'],
          borderWidth: 2,
          borderColor: '#ffffff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'right', labels: { font: { family: 'Inter', size: 11 } } }
        }
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
