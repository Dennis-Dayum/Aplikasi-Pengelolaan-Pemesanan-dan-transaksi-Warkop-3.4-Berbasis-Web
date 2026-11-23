<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

$totalMenu   = $db->query("SELECT COUNT(*) c FROM menu WHERE is_active=1")->fetch()['c'];
$totalHabis  = $db->query("SELECT COUNT(*) c FROM menu WHERE is_active=1 AND status_ketersediaan='habis'")->fetch()['c'];
$pesananHariIni = $db->query("SELECT COUNT(*) c FROM pesanan WHERE DATE(created_at)=CURDATE()")->fetch()['c'];
$omsetHariIni = $db->query("SELECT COALESCE(SUM(total_bayar),0) t FROM transaksi WHERE status='lunas' AND DATE(created_at)=CURDATE()")->fetch()['t'];
$omsetBulanIni = $db->query("SELECT COALESCE(SUM(total_bayar),0) t FROM transaksi WHERE status='lunas' AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())")->fetch()['t'];
$pesananMenunggu = $db->query("SELECT COUNT(*) c FROM pesanan WHERE status='diproses'")->fetch()['c'];
$stokMenipis = $db->query("SELECT COUNT(*) c FROM bahan_baku WHERE stok <= stok_minimum")->fetch()['c'];

$menuTerlaris = $db->query("
  SELECT m.nama_menu, SUM(dp.jumlah) total_terjual
  FROM detail_pesanan dp
  JOIN menu m ON m.id = dp.menu_id
  JOIN transaksi t ON t.pesanan_id = dp.pesanan_id AND t.status='lunas'
  GROUP BY dp.menu_id ORDER BY total_terjual DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard'; $activeMenu = 'dashboard';
require __DIR__ . '/../includes/layout_admin_top.php';
?>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= rupiah($omsetHariIni) ?></div><div class="lbl">Omset Hari Ini</div></div>
  <div class="stat-card"><div class="num"><?= rupiah($omsetBulanIni) ?></div><div class="lbl">Omset Bulan Ini</div></div>
  <div class="stat-card"><div class="num"><?= (int)$pesananHariIni ?></div><div class="lbl">Pesanan Hari Ini</div></div>
  <div class="stat-card"><div class="num"><?= (int)$pesananMenunggu ?></div><div class="lbl">Pesanan Menunggu Diproses</div></div>
  <div class="stat-card"><div class="num"><?= (int)$totalMenu ?></div><div class="lbl">Total Menu Aktif</div></div>
  <div class="stat-card"><div class="num"><?= (int)$totalHabis ?></div><div class="lbl">Menu Berstatus Habis</div></div>
</div>

<?php if ($stokMenipis > 0): ?>
<div class="alert alert-warning">⚠️ Ada <strong><?= $stokMenipis ?></strong> bahan baku dengan stok di bawah batas minimum. <a href="<?= BASE_URL ?>/admin/stok.php">Cek Stok Bahan Baku &rarr;</a></div>
<?php endif; ?>

<div class="card">
  <h3>Menu Terlaris (Top 5)</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>#</th><th>Nama Menu</th><th>Total Terjual</th></tr></thead>
    <tbody>
      <?php if (!$menuTerlaris): ?>
        <tr><td colspan="3" class="text-muted">Belum ada data transaksi.</td></tr>
      <?php else: foreach ($menuTerlaris as $i => $m): ?>
        <tr><td><?= $i+1 ?></td><td><?= e($m['nama_menu']) ?></td><td><?= (int)$m['total_terjual'] ?> porsi</td></tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="card">
  <h3>Akses Cepat</h3>
  <a href="<?= BASE_URL ?>/admin/laporan.php" class="btn">📈 Buka Laporan Penjualan</a>
  <a href="<?= BASE_URL ?>/admin/menu.php" class="btn btn-outline">🍽️ Kelola Menu</a>
  <a href="<?= BASE_URL ?>/admin/stok.php" class="btn btn-outline">📦 Kelola Stok</a>
</div>

<?php require __DIR__ . '/../includes/layout_admin_bottom.php'; ?>
