<?php
/**
 * SDPMS - Reusable Header Component
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

$currentUser = current_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= sanitize_output($pageTitle ?? 'ProcureTrack') ?> | <?= sanitize_output(APP_NAME) ?></title>
  <meta name="description" content="Centralized supplier delivery and performance monitoring system for procurement teams.">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <div class="app-container">
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <main class="main-wrapper">
      <header class="top-header">
        <div class="header-left">
          <button class="mobile-menu-toggle" aria-label="Toggle navigation">
            <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
          </button>
          <div class="page-title-box">
            <h2><?= sanitize_output($pageTitle ?? 'Dashboard') ?></h2>
            <p><?= sanitize_output($pageSubtitle ?? 'Enterprise Procurement & Vendor Reliability Management') ?></p>
          </div>
        </div>
        <div class="header-right">
          <div class="system-status-indicator">
            <span class="status-dot-pulse"></span>
            <span>MySQL Connected</span>
          </div>
          <?php if (!empty($headerActions)): ?>
            <?= $headerActions ?>
          <?php endif; ?>
        </div>
      </header>

      <div class="page-body">
        <!-- Flash Messages Display -->
        <?php foreach (get_flash_messages() as $flash): ?>
          <div class="alert-box alert-<?= sanitize_output($flash['type']) ?>">
            <div style="flex:1;">
              <?= sanitize_output($flash['message']) ?>
            </div>
            <button style="color:currentColor; opacity:0.6; cursor:pointer;" onclick="this.parentElement.remove()">&times;</button>
          </div>
        <?php endforeach; ?>
