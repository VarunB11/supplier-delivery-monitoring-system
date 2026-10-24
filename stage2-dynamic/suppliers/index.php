<?php
/**
 * SDPMS - Suppliers Directory & Listing
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$db = Database::getConnection();

// Filtering parameters
$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$status = trim($_GET['status'] ?? '');

$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(s.company_name LIKE :s1 OR s.id LIKE :s2 OR s.contact_person LIKE :s3 OR s.city LIKE :s4)";
    $params[':s1'] = "%{$search}%";
    $params[':s2'] = "%{$search}%";
    $params[':s3'] = "%{$search}%";
    $params[':s4'] = "%{$search}%";
}

if ($category !== '') {
    $whereClauses[] = "s.category = :category";
    $params[':category'] = $category;
}

if ($status !== '') {
    $whereClauses[] = "s.status = :status";
    $params[':status'] = $status;
}

$whereSQL = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

// Categories for filter
$catStmt = $db->query("SELECT DISTINCT `category` FROM `suppliers` ORDER BY `category` ASC");
$allCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Pagination
$perPage = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Total Count
$countSql = "SELECT COUNT(*) FROM `suppliers` s {$whereSQL}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $perPage));

// Fetch Records with linked PO count
$sql = "SELECT s.*, 
               (SELECT COUNT(*) FROM `purchase_orders` po WHERE po.supplier_id = s.id) AS linked_orders_count
        FROM `suppliers` s
        {$whereSQL}
        ORDER BY s.company_name ASC
        LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$suppliers = $stmt->fetchAll();

$pageTitle = 'Supplier Directory';
$pageSubtitle = 'Manage registered industrial vendors, product categories and active contract statuses';
$activePage = 'suppliers';

$headerActions = '<a href="' . BASE_URL . '/suppliers/create.php" class="btn btn-primary btn-sm">
  <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  Register Supplier
</a>';

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Toolbar -->
<div class="toolbar-container">
  <form method="GET" action="<?= BASE_URL ?>/suppliers/index.php" class="toolbar-filters">
    <div class="search-input-group">
      <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" name="search" value="<?= sanitize_output($search) ?>" placeholder="Search name, ID, contact or city...">
    </div>

    <select name="category" class="filter-select" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php foreach ($allCategories as $c): ?>
        <option value="<?= sanitize_output($c) ?>" <?= $category === $c ? 'selected' : '' ?>>
          <?= sanitize_output($c) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <select name="status" class="filter-select" onchange="this.form.submit()">
      <option value="">All Statuses</option>
      <option value="Active" <?= $status === 'Active' ? 'selected' : '' ?>>Active</option>
      <option value="Inactive" <?= $status === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
    </select>

    <button type="submit" class="btn btn-navy btn-sm">Filter</button>
    <?php if ($search || $category || $status): ?>
      <a href="<?= BASE_URL ?>/suppliers/index.php" class="btn btn-outline btn-sm">Reset</a>
    <?php endif; ?>
  </form>

  <div style="font-size:0.85rem; font-weight:600; color:var(--slate-500);">
    <?= $totalRecords ?> supplier(s) recorded
  </div>
</div>

<!-- Supplier Data Table -->
<div class="card">
  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th>Supplier ID</th>
          <th>Company Name &amp; City</th>
          <th>Contact Person &amp; Email</th>
          <th>Material Category</th>
          <th>Linked Orders</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($suppliers)): ?>
          <tr>
            <td colspan="7" style="text-align:center; padding:3rem; color:var(--slate-500);">
              No suppliers found matching your query criteria.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($suppliers as $s): ?>
            <tr>
              <td class="cell-code"><?= sanitize_output($s['id']) ?></td>
              <td>
                <div class="cell-strong"><?= sanitize_output($s['company_name']) ?></div>
                <div class="cell-muted"><?= sanitize_output($s['city']) ?></div>
              </td>
              <td>
                <div><?= sanitize_output($s['contact_person']) ?></div>
                <div class="cell-muted"><?= sanitize_output($s['email']) ?></div>
              </td>
              <td><?= sanitize_output($s['category']) ?></td>
              <td>
                <a href="<?= BASE_URL ?>/purchase-orders/index.php?supplier=<?= urlencode($s['id']) ?>" style="color:var(--primary-600); text-decoration:underline;">
                  <?= (int)$s['linked_orders_count'] ?> order(s)
                </a>
              </td>
              <td><?= render_status_badge($s['status']) ?></td>
              <td>
                <div style="display:flex; gap:0.4rem;">
                  <a href="<?= BASE_URL ?>/suppliers/view.php?id=<?= urlencode($s['id']) ?>" class="btn btn-icon btn-sm" title="View Profile">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </a>
                  <a href="<?= BASE_URL ?>/suppliers/edit.php?id=<?= urlencode($s['id']) ?>" class="btn btn-icon btn-sm" title="Edit Supplier">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                  </a>
                  <a href="<?= BASE_URL ?>/suppliers/delete.php?id=<?= urlencode($s['id']) ?>" 
                     class="btn btn-icon btn-sm" 
                     title="Safe Deactivate or Delete"
                     data-confirm="Are you sure you want to delete or deactivate supplier <?= sanitize_output($s['company_name']) ?>? If purchase orders exist, safety constraints will deactivate the vendor.">
                    <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </a>
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
          <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>" class="btn btn-outline btn-sm">&laquo; Prev</a>
        <?php endif; ?>
        <?php if ($page < $totalPages): ?>
          <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>" class="btn btn-outline btn-sm">Next &raquo;</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
