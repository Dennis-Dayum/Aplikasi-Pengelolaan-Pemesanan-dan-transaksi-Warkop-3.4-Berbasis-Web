<?php
/**
 * Layout atas untuk seluruh halaman Kasir.
 * Wajib panggil requireRole('kasir') SEBELUM include file ini.
 */
$pageTitle  = $pageTitle ?? 'Kasir';
$activeMenu = $activeMenu ?? '';
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> - Warkop 3.4</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <div class="brand">☕ Warkop 3.4 <small>Panel Kasir</small></div>
    <nav>
      <a href="<?= BASE_URL ?>/kasir/dashboard.php" class="<?= $activeMenu==='dashboard'?'active':'' ?>">📥 Pesanan Masuk</a>
      <a href="<?= BASE_URL ?>/kasir/stok_menu.php" class="<?= $activeMenu==='stok'?'active':'' ?>">🍽️ Ketersediaan Menu</a>
      <a href="<?= BASE_URL ?>/kasir/riwayat_transaksi.php" class="<?= $activeMenu==='riwayat'?'active':'' ?>">🧾 Riwayat Transaksi</a>
      <a href="<?= BASE_URL ?>/kasir/laporan_harian.php" class="<?= $activeMenu==='laporan'?'active':'' ?>">📈 Laporan Harian</a>
    </nav>
  </aside>

  <div class="main-content">
    <div class="topbar no-print">
      <strong><?= e($pageTitle) ?></strong>
      <div class="user-info">
        <span><?= e($user['nama']) ?></span>
        <span class="role-badge">Kasir</span>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-sm btn-secondary" data-confirm="Yakin ingin logout?">Logout</a>
      </div>
    </div>
    <div class="content">
      <?php if ($flash = getFlash()): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" data-autohide><?= e($flash['message']) ?></div>
      <?php endif; ?>
