<?php
/**
 * SDPMS - Supplier Performance Evaluation Module
 * Transparent, rule-based mathematical scoring based on verified MySQL records
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

// All performance metrics from DB
$allMetrics = get_supplier_performance_metrics($db);

// Filter params
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$ratingFilter = trim($_GET['rating'] ?? '');

$filteredMetrics = array_filter($allMetrics, function($item) use ($search, $category, $ratingFilter) {
    if ($search !== '') {
        $q = strtolower($search);
        $nameMatch = str_contains(strtolower($item['company_name']), $q);
        $idMatch = str_contains(strtolower($item['supplier_id']), $q);
        $catMatch = str_contains(strtolower($item['category']), $q);
        if (!$nameMatch && !$idMatch && !$catMatch) return false;
    }
    if ($category !== '' && $item['category'] !== $category) {
        return false;
    }
    if ($ratingFilter !== '' && $item['rating'] !== $ratingFilter) {
        return false;
    }
    return true;
});

// Categories for filter
$allCategories = array_unique(array_column($allMetrics, 'category'));
sort($allCategories);

// Data for Chart.js
$ratedItems = array_values(array_filter($allMetrics, fn($m) => $m['eligible_completed'] > 0));
$chartLabels = array_map(fn($m) => explode(' ', $m['company_name'])[0], $ratedItems);
$chartOnTime = array_map(fn($m) => $m['on_time_percentage'], $ratedItems);
$chartScores = array_map(fn($m) => $m['overall_score'], $ratedItems);

$pageTitle = 'Supplier Performance Evaluation';
$pageSubtitle = 'Transparent mathematical scorecards based on verified MySQL delivery track records';
$activePage = 'performance';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Formulas Card -->
<div class="formula-box">
  <div class="formula-title">
    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
    Procurement Reliability Scoring Formulas (Transparent Rule-Based Metrics)
  </div>
  <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1rem; font-size:0.8rem; color:var(--slate-700);">
    <div>
      <strong>1. On-Time Delivery Rate:</strong><br>
      <div class="formula-code">(On-Time Completed / Eligible Completed) &times; 100</div>
      <div class="form-help">Safely handles 0 completed deliveries without division errors.</div>
    </div>
    <div>
      <strong>2. Average Delivery Delay:</strong><br>
      <div class="formula-code">Sum of positive delay days / Eligible Completed</div>
      <div class="form-help">Calculates the severity of late arrivals in calendar days.</div>
    </div>
    <div>
      <strong>3. Quantity Fulfilment Rate:</strong><br>
      <div class="formula-code">(Total Accepted Qty / Total Ordered Qty) &times; 100</div>
      <div class="form-help">Measures quantity conformity across active orders.</div>
    </div>
    <div>
      <strong>4. Composite Score &amp; Rating:</strong><br>
      <div class="formula-code">70% (On-Time %) + 30% (Quantity Fulfilment %)</div>
      <div class="form-help">&ge;90% Excellent | &ge;75% Good | &ge;60% Average | &lt;60% Needs Improvement</div>
    </div>
  </div>
</div>

<!-- Comparative Chart -->
<div class="card" style="margin-bottom:2rem;">
  <div class="card-header">
    <div>
      <h3 class="card-title">Vendor Performance Benchmark Comparison</h3>
      <div class="card-subtitle">Comparing on-time compliance % against weighted composite scores (Live MySQL Data)</div>
    </div>
  </div>
  <div class="card-body">
    <div style="height:300px; position:relative;">
      <canvas id="chart-supplier-comparison"></canvas>
    </div>
  </div>
</div>

<!-- Toolbar -->
<div class="toolbar-container">
  <form method="GET" action="<?= BASE_URL ?>/performance/index.php" class="toolbar-filters">
    <div class="search-input-group">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="search" value="<?= sanitize_output($search) ?>" placeholder="Search supplier or category...">
    </div>

    <select name="category" class="filter-select" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($allCategories as $c): ?>
        <option value="<?= sanitize_output($c) ?>" <?= $category === $c ? 'selected' : '' ?>>
          <?= sanitize_output($c) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="rating" class="filter-select" onchange="this.form.submit()">
      <option value="">All Performance Ratings</option>
      <option value="Excellent" <?= $ratingFilter === 'Excellent' ? 'selected' : '' ?>>Excellent (90-100%)</option>
      <option value="Good" <?= $ratingFilter === 'Good' ? 'selected' : '' ?>>Good (75-89%)</option>
      <option value="Average" <?= $ratingFilter === 'Average' ? 'selected' : '' ?>>Average (60-74%)</option>
      <option value="Needs Improvement" <?= $ratingFilter === 'Needs Improvement' ? 'selected' : '' ?>>Needs Improvement (&lt;60%)</option>
      <option value="Unrated" <?= $ratingFilter === 'Unrated' ? 'selected' : '' ?>>Unrated (New)</option>
    </select>

    <button type="submit" class="btn btn-navy btn-sm">Filter</button>
    <?php if ($search || $category || $ratingFilter): ?>
      <a href="<?= BASE_URL ?>/performance/index.php" class="btn btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>
</div>

<!-- Performance Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Supplier &amp; Category</th>
          <th style="text-align:center;">Completed Volume</th>
          <th style="text-align:center;">On-Time</th>
          <th style="text-align:center;">Late</th>
          <th style="min-width:140px;">On-Time Delivery %</th>
          <th style="text-align:center;">Avg Delay</th>
          <th style="min-width:130px;">Qty Fulfilment</th>
          <th style="text-align:center;">Composite Score</th>
          <th>Rating Tier</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($filteredMetrics)): ?>
          <tr>
            <td colspan="10" style="text-align:center; padding:3rem; color:var(--slate-500);">
              No supplier performance evaluations match the specified filters.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($filteredMetrics as $m): ?>
            <tr>
              <td>
                <div class="cell-strong">
                  <a href="<?= BASE_URL ?>/suppliers/view.php?id=<?= urlencode($m['supplier_id']) ?>" style="color:var(--navy-900); text-decoration:underline;">
                    <?= sanitize_output($m['company_name']) ?>
                  </a>
                </div>
                <div class="cell-code" style="font-size:0.75rem;"><?= sanitize_output($m['supplier_id']) ?> &bull; <?= sanitize_output($m['category']) ?></div>
              </td>
              <td style="text-align:center;">
                <span style="font-weight:700;"><?= $m['eligible_completed'] ?></span>
                <div class="cell-muted" style="font-size:0.7rem;">of <?= $m['total_orders'] ?> POs</div>
              </td>
              <td style="text-align:center; color:var(--success-600); font-weight:700;"><?= $m['on_time_count'] ?></td>
              <td style="text-align:center; color:<?= $m['late_count'] > 0 ? 'var(--danger-600)' : 'var(--slate-600)' ?>; font-weight:700;"><?= $m['late_count'] ?></td>
              <td>
                <?php if ($m['on_time_percentage'] !== null): ?>
                  <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.25rem;">
                    <span style="font-weight:700;"><?= $m['on_time_percentage'] ?>%</span>
                  </div>
                  <div class="progress-container">
                    <div class="progress-bar <?= $m['on_time_percentage'] >= 80 ? 'progress-success' : ($m['on_time_percentage'] >= 60 ? 'progress-warning' : 'progress-danger') ?>" 
                         style="width:<?= max(5, $m['on_time_percentage']) ?>%;"></div>
                  </div>
                <?php else: ?>
                  <span class="cell-muted">N/A (0 completed)</span>
                <?php endif; ?>
              </td>
              <td style="text-align:center; font-weight:600; color:<?= $m['avg_delay_days'] > 0 ? 'var(--danger-600)' : 'var(--success-600)' ?>;">
                <?= $m['avg_delay_days'] ?> d
              </td>
              <td>
                <div style="display:flex; justify-content:space-between; font-size:0.8rem; margin-bottom:0.25rem;">
                  <span><?= $m['qty_fulfilment_pct'] ?>%</span>
                </div>
                <div class="progress-container">
                  <div class="progress-bar <?= $m['qty_fulfilment_pct'] >= 95 ? 'progress-success' : 'progress-warning' ?>" 
                       style="width:<?= min(100, $m['qty_fulfilment_pct']) ?>%;"></div>
                </div>
              </td>
              <td style="text-align:center;">
                <?php if ($m['overall_score'] !== null): ?>
                  <span style="font-size:1.15rem; font-weight:800; color:var(--navy-900);"><?= $m['overall_score'] ?></span>
                  <span style="font-size:0.7rem; color:var(--slate-400);">/100</span>
                <?php else: ?>
                  <span class="cell-muted">—</span>
                <?php endif; ?>
              </td>
              <td><?= render_rating_badge($m['rating']) ?></td>
              <td>
                <a href="<?= BASE_URL ?>/suppliers/view.php?id=<?= urlencode($m['supplier_id']) ?>" class="btn btn-outline btn-sm">
                  Profile
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.Chart) {
    const ctx = document.getElementById('chart-supplier-comparison').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
          {
            label: 'On-Time Delivery Rate (%)',
            data: <?= json_encode($chartOnTime) ?>,
            backgroundColor: 'rgba(37, 99, 235, 0.85)',
            borderColor: '#1d4ed8',
            borderWidth: 1,
            borderRadius: 4
          },
          {
            label: 'Weighted Composite Score (/100)',
            data: <?= json_encode($chartScores) ?>,
            backgroundColor: 'rgba(16, 185, 129, 0.85)',
            borderColor: '#059669',
            borderWidth: 1,
            borderRadius: 4
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: 100,
            ticks: { callback: val => `${val}%` },
            grid: { color: '#f1f5f9' }
          },
          x: { grid: { display: false } }
        },
        plugins: {
          legend: { position: 'top', labels: { font: { family: 'Inter', weight: 600, size: 12 } } },
          tooltip: {
            callbacks: {
              label: ctx => `${ctx.dataset.label}: ${ctx.raw}%`
            }
          }
        }
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
