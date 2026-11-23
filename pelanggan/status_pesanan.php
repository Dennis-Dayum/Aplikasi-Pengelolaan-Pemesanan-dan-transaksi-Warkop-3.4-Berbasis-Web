<?php
require_once __DIR__ . '/../includes/pelanggan_bootstrap.php';
$db = getDB();

$kode = trim($_GET['kode'] ?? ($_SESSION['kode_pesanan_terakhir'] ?? ''));
$pesanan = null;
$items = [];

if ($kode !== '') {
    $stmt = $db->prepare('SELECT * FROM pesanan WHERE kode_pesanan=?');
    $stmt->execute([$kode]);
    $pesanan = $stmt->fetch();
    if ($pesanan) {
        $stmt = $db->prepare('SELECT * FROM detail_pesanan WHERE pesanan_id=?');
        $stmt->execute([$pesanan['id']]);
        $items = $stmt->fetchAll();
    }
}

// FR-10: Sistem harus menampilkan status pesanan (diproses, disajikan, selesai).
$urutanStatus = ['diproses' => 1, 'disajikan' => 2, 'selesai' => 3];
$statusSekarang = $pesanan ? ($urutanStatus[$pesanan['status']] ?? 0) : 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Status Pesanan - Warkop 3.4</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<?php if ($pesanan && !in_array($pesanan['status'], ['selesai','dibatalkan'])): ?>
<meta http-equiv="refresh" content="20">
<?php endif; ?>
</head>
<body>

<div class="customer-header">
  <div class="brand">☕ Warkop 3.4 <small>Status Pesanan</small></div>
  <a href="<?= BASE_URL ?>/pelanggan/index.php" class="cart-link">Pesan Lagi</a>
</div>

<div class="customer-wrap">

  <div class="card">
    <h3 class="mt-0">Cek Status Pesanan</h3>
    <form method="get" action="" style="display:flex;gap:10px">
      <input type="text" name="kode" value="<?= e($kode) ?>" placeholder="Masukkan kode pesanan, contoh: ORD..." style="flex:1">
      <button class="btn">Cek</button>
    </form>
  </div>

  <?php if ($kode !== '' && !$pesanan): ?>
    <div class="alert alert-danger">Kode pesanan tidak ditemukan. Periksa kembali kode yang Anda masukkan.</div>
  <?php elseif ($pesanan): ?>
    <div class="card">
      <div class="flex-between">
        <div>
          <h2 class="mt-0 mb-0"><?= e($pesanan['kode_pesanan']) ?></h2>
          <span class="text-muted">Meja <?= e($pesanan['nomor_meja']) ?> · <?= date('d/m/Y H:i', strtotime($pesanan['created_at'])) ?></span>
        </div>
        <span class="badge <?= badgeStatusPesanan($pesanan['status']) ?>" style="font-size:.9rem"><?= labelStatusPesanan($pesanan['status']) ?></span>
      </div>

      <?php if ($pesanan['status'] !== 'dibatalkan'): ?>
      <div class="status-tracker">
        <div class="step <?= $statusSekarang>=1?'active':'' ?>"><div class="dot">1</div><div class="lbl">Diproses</div></div>
        <div class="step <?= $statusSekarang>=2?'active':'' ?>"><div class="dot">2</div><div class="lbl">Disajikan</div></div>
        <div class="step <?= $statusSekarang>=3?'active':'' ?>"><div class="dot">3</div><div class="lbl">Selesai</div></div>
      </div>
      <?php else: ?>
        <div class="alert alert-danger" style="margin-top:16px">Pesanan ini telah dibatalkan.</div>
      <?php endif; ?>

      <table style="margin-top:10px">
        <thead><tr><th>Menu</th><th>Jumlah</th><th>Subtotal</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr><td><?= e($it['nama_menu_snapshot']) ?></td><td><?= (int)$it['jumlah'] ?></td><td><?= rupiah($it['subtotal']) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot><tr><td colspan="2" class="text-right"><strong>Total</strong></td><td><strong><?= rupiah($pesanan['total_harga']) ?></strong></td></tr></tfoot>
      </table>
      <p class="text-muted" style="font-size:.8rem;margin-top:10px">Halaman ini akan diperbarui otomatis secara berkala.</p>
    </div>
  <?php endif; ?>

</div>

</body>
</html>
