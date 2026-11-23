<?php
/**
 * Layout atas untuk seluruh halaman Admin/Owner.
 * Wajib panggil requireRole('admin') SEBELUM include file ini.
 * Variabel $pageTitle & $activeMenu bisa diset sebelum include.
 */
$pageTitle  = $pageTitle ?? 'Admin';
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
    <div class="brand">☕ Warkop 3.4 <small>Panel Admin / Owner</small></div>
    <nav>
      <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $activeMenu==='dashboard'?'active':'' ?>">📊 Dashboard</a>

      <div class="section-label">Manajemen Menu</div>
      <a href="<?= BASE_URL ?>/admin/kategori.php" class="<?= $activeMenu==='kategori'?'active':'' ?>">🗂️ Kategori Menu</a>
      <a href="<?= BASE_URL ?>/admin/menu.php" class="<?= $activeMenu==='menu'?'active':'' ?>">🍽️ Data Menu</a>
      <a href="<?= BASE_URL ?>/admin/stok.php" class="<?= $activeMenu==='stok'?'active':'' ?>">📦 Stok Bahan Baku</a>

      <div class="section-label">Transaksi</div>
      <a href="<?= BASE_URL ?>/admin/pesanan.php" class="<?= $activeMenu==='pesanan'?'active':'' ?>">🧾 Semua Pesanan</a>
      <a href="<?= BASE_URL ?>/admin/void_transaksi.php" class="<?= $activeMenu==='void'?'active':'' ?>">⛔ Void Transaksi</a>

      <div class="section-label">Laporan</div>
      <a href="<?= BASE_URL ?>/admin/laporan.php" class="<?= $activeMenu==='laporan'?'active':'' ?>">📈 Laporan Penjualan</a>

      <div class="section-label">Pengguna</div>
      <a href="<?= BASE_URL ?>/admin/pengguna.php" class="<?= $activeMenu==='pengguna'?'active':'' ?>">👤 Manajemen Pengguna</a>
    </nav>
  </aside>

  <div class="main-content">
    <div class="topbar no-print">
      <strong><?= e($pageTitle) ?></strong>
      <div class="user-info">
        <span><?= e($user['nama']) ?></span>
        <span class="role-badge">Owner/Admin</span>
        <a href="<?= BASE_URL ?>/auth/logout.php" class="btn btn-sm btn-secondary" data-confirm="Yakin ingin logout?">Logout</a>
      </div>
    </div>
    <div class="content">
      <?php if ($flash = getFlash()): ?>
        <div class="alert alert-<?= e($flash['type']) ?>" data-autohide><?= e($flash['message']) ?></div>
      <?php endif; ?>
