<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('kasir');
$db = getDB();

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$stmt = $db->prepare("SELECT COUNT(*) jumlah, COALESCE(SUM(total_bayar),0) total FROM transaksi WHERE status='lunas' AND DATE(created_at)=?");
$stmt->execute([$tanggal]);
$ringkasan = $stmt->fetch();

$stmt = $db->prepare("
  SELECT dp.nama_menu_snapshot nama_menu, SUM(dp.jumlah) qty, SUM(dp.subtotal) total
  FROM detail_pesanan dp JOIN transaksi t ON t.pesanan_id=dp.pesanan_id
  WHERE t.status='lunas' AND DATE(t.created_at)=?
  GROUP BY dp.nama_menu_snapshot ORDER BY qty DESC
");
$stmt->execute([$tanggal]);
$rekapMenu = $stmt->fetchAll();

$pageTitle = 'Laporan Harian'; $activeMenu = 'laporan';
require __DIR__ . '/../includes/layout_kasir_top.php';
?>

<div class="alert alert-info">Laporan ini hanya menampilkan ringkasan penjualan harian (bukan nota/struk). Untuk laporan lengkap dan ekspor Excel/PDF, hubungi Admin/Owner.</div>

<div class="card no-print">
  <form method="get" action="" class="filter-bar">
    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($tanggal) ?>"></div>
    <div class="form-group"><button class="btn btn-outline">Tampilkan</button></div>
  </form>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= (int)$ringkasan['jumlah'] ?></div><div class="lbl">Jumlah Transaksi (Semua Kasir)</div></div>
  <div class="stat-card"><div class="num"><?= rupiah($ringkasan['total']) ?></div><div class="lbl">Total Penjualan Warkop</div></div>
</div>

<div class="card">
  <h3 class="mt-0">Menu Terjual Tanggal <?= e(date('d/m/Y', strtotime($tanggal))) ?></h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>#</th><th>Nama Menu</th><th>Qty</th><th>Total</th></tr></thead>
    <tbody>
    <?php if (!$rekapMenu): ?><tr><td colspan="4" class="text-muted">Tidak ada data.</td></tr><?php endif; ?>
    <?php foreach ($rekapMenu as $i => $rm): ?>
      <tr><td><?= $i+1 ?></td><td><?= e($rm['nama_menu']) ?></td><td><?= (int)$rm['qty'] ?></td><td><?= rupiah($rm['total']) ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_kasir_bottom.php'; ?>
