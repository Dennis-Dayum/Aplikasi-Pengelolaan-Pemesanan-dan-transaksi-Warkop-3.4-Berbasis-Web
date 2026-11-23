<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('kasir');
$db = getDB();

$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

// FR-14: Sistem harus menyimpan riwayat transaksi.
$stmt = $db->prepare("
  SELECT t.*, p.kode_pesanan, p.nomor_meja
  FROM transaksi t JOIN pesanan p ON p.id=t.pesanan_id
  WHERE t.kasir_id=? AND DATE(t.created_at)=?
  ORDER BY t.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $tanggal]);
$riwayat = $stmt->fetchAll();

$totalLunas = 0;
foreach ($riwayat as $r) { if ($r['status'] === 'lunas') $totalLunas += $r['total_bayar']; }

$pageTitle = 'Riwayat Transaksi Saya'; $activeMenu = 'riwayat';
require __DIR__ . '/../includes/layout_kasir_top.php';
?>

<div class="card no-print">
  <form method="get" action="" class="filter-bar">
    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($tanggal) ?>"></div>
    <div class="form-group"><button class="btn btn-outline">Tampilkan</button></div>
  </form>
</div>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= count($riwayat) ?></div><div class="lbl">Jumlah Transaksi</div></div>
  <div class="stat-card"><div class="num"><?= rupiah($totalLunas) ?></div><div class="lbl">Total Penjualan (Lunas)</div></div>
</div>

<div class="card">
  <div class="table-wrap">
  <table>
    <thead><tr><th>Kode Transaksi</th><th>Pesanan</th><th>Meja</th><th>Metode</th><th>Total</th><th>Status</th><th>Waktu</th></tr></thead>
    <tbody>
    <?php if (!$riwayat): ?><tr><td colspan="7" class="text-muted">Tidak ada transaksi pada tanggal ini.</td></tr><?php endif; ?>
    <?php foreach ($riwayat as $r): ?>
      <tr>
        <td><?= e($r['kode_transaksi']) ?></td>
        <td><?= e($r['kode_pesanan']) ?></td>
        <td><?= e($r['nomor_meja']) ?></td>
        <td><?= e(ucfirst($r['metode_pembayaran'])) ?></td>
        <td><?= rupiah($r['total_bayar']) ?></td>
        <td><span class="badge <?= $r['status']==='lunas'?'badge-success':'badge-danger' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
        <td><?= date('H:i', strtotime($r['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_kasir_bottom.php'; ?>
