<?php
/**
 * SDPMS - Reusable Sidebar Component
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

$activePage = $activePage ?? '';
$user = current_user();
?>
<aside class="sidebar">
  <div class="sidebar-header">
    <div class="brand-icon">
      <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17a2 2 0 11-4 0 2 2 0 014 0zM19 17a2 2 0 11-4 0 2 2 0 014 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
    </div>
    <div class="brand-text">
      <h1><?= sanitize_output(APP_NAME) ?></h1>
      <span>Dynamic DB System</span>
    </div>
  </div>

  <div class="sidebar-content">
    <div class="nav-section-title">Core Operations</div>
    <ul class="nav-list">
      <li class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/dashboard.php">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
          <span>Dashboard</span>
        </a>
      </li>
      <li class="nav-item <?= $activePage === 'suppliers' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/suppliers/index.php">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          <span>Suppliers</span>
        </a>
      </li>
      <li class="nav-item <?= $activePage === 'purchase_orders' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/purchase-orders/index.php">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
          <span>Purchase Orders</span>
        </a>
      </li>
      <li class="nav-item <?= $activePage === 'deliveries' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/deliveries/index.php">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Delivery Tracking</span>
        </a>
      </li>
    </ul>

    <div class="nav-section-title">Analytics & Reports</div>
    <ul class="nav-list">
      <li class="nav-item <?= $activePage === 'performance' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/performance/index.php">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          <span>Supplier Performance</span>
        </a>
      </li>
      <li class="nav-item <?= $activePage === 'reports' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/reports/index.php">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <span>Reports & Analytics</span>
        </a>
      </li>
    </ul>

    <div class="nav-section-title">Settings & Account</div>
    <ul class="nav-list">
      <li class="nav-item <?= $activePage === 'profile' ? 'active' : '' ?>">
        <a href="<?= BASE_URL ?>/profile.php">
          <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <span>User Profile</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-footer">
    <div class="user-profile-widget">
      <div class="user-avatar">
        <?= strtoupper(substr($user['full_name'] ?? 'US', 0, 2)) ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= sanitize_output($user['full_name'] ?? 'User') ?></div>
        <div class="user-role"><?= sanitize_output($user['role'] ?? 'Staff') ?></div>
      </div>
      <a href="<?= BASE_URL ?>/logout.php" class="btn-sidebar-logout" title="Sign Out" onclick="return confirm('Sign out of ProcureTrack?');">
        <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      </a>
    </div>
  </div>
</aside>
