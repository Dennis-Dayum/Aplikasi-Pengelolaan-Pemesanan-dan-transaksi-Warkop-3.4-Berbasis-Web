<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('kasir');
$db = getDB();

// FR-11: Kasir dapat melihat daftar pesanan yang masuk.
$pesananMasuk = $db->query("SELECT * FROM pesanan WHERE status='diproses' ORDER BY created_at ASC")->fetchAll();
$pesananDisajikan = $db->query("SELECT * FROM pesanan WHERE status='disajikan' ORDER BY created_at ASC")->fetchAll();

$pageTitle = 'Pesanan Masuk'; $activeMenu = 'dashboard';
require __DIR__ . '/../includes/layout_kasir_top.php';
?>

<div class="stat-grid">
  <div class="stat-card"><div class="num"><?= count($pesananMasuk) ?></div><div class="lbl">Menunggu Diproses / Dibayar</div></div>
  <div class="stat-card"><div class="num"><?= count($pesananDisajikan) ?></div><div class="lbl">Sedang Disajikan</div></div>
</div>

<div class="card">
  <h3 class="mt-0">🔔 Pesanan Baru Masuk</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Kode Pesanan</th><th>Meja</th><th>Tipe</th><th>Total</th><th>Waktu Masuk</th><th style="width:150px">Aksi</th></tr></thead>
    <tbody>
    <?php if (!$pesananMasuk): ?>
      <tr><td colspan="6" class="text-muted">Belum ada pesanan baru.</td></tr>
    <?php endif; ?>
    <?php foreach ($pesananMasuk as $p): ?>
      <tr>
        <td><strong><?= e($p['kode_pesanan']) ?></strong></td>
        <td><?= e($p['nomor_meja']) ?></td>
        <td><?= e(ucfirst($p['tipe_pesanan'])) ?></td>
        <td><?= rupiah($p['total_harga']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
        <td><a href="<?= BASE_URL ?>/kasir/detail_pesanan.php?id=<?= $p['id'] ?>" class="btn btn-sm">Proses</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="card">
  <h3 class="mt-0">🍽️ Sedang Disajikan</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Kode Pesanan</th><th>Meja</th><th>Total</th><th>Waktu</th><th style="width:150px">Aksi</th></tr></thead>
    <tbody>
    <?php if (!$pesananDisajikan): ?>
      <tr><td colspan="5" class="text-muted">Tidak ada pesanan yang sedang disajikan.</td></tr>
    <?php endif; ?>
    <?php foreach ($pesananDisajikan as $p): ?>
      <tr>
        <td><strong><?= e($p['kode_pesanan']) ?></strong></td>
        <td><?= e($p['nomor_meja']) ?></td>
        <td><?= rupiah($p['total_harga']) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
        <td><a href="<?= BASE_URL ?>/kasir/detail_pesanan.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline">Lihat</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_kasir_bottom.php'; ?>
