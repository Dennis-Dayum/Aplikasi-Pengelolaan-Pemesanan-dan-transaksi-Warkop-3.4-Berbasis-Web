<?php
require_once __DIR__ . '/../includes/pelanggan_bootstrap.php';
$db = getDB();

$items = [];
$total = 0;
if (!empty($_SESSION['keranjang'])) {
    $ids = array_keys($_SESSION['keranjang']);
    $in = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM menu WHERE id IN ($in)");
    $stmt->execute($ids);
    $menuRows = $stmt->fetchAll();
    foreach ($menuRows as $m) {
        $jumlah = $_SESSION['keranjang'][$m['id']];
        $subtotal = $jumlah * $m['harga'];
        $total += $subtotal;
        $items[] = ['menu' => $m, 'jumlah' => $jumlah, 'subtotal' => $subtotal];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Keranjang - Warkop 3.4</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="customer-header">
  <div class="brand">☕ Warkop 3.4 <small>Keranjang Pesanan</small></div>
  <a href="<?= BASE_URL ?>/pelanggan/index.php" class="cart-link">&larr; Tambah Menu Lain</a>
</div>

<div class="customer-wrap">

  <?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" data-autohide><?= e($flash['message']) ?></div>
  <?php endif; ?>

  <div class="card">
    <h3 class="mt-0">Keranjang Pesanan</h3>
    <?php if (!$items): ?>
      <p class="text-muted">Keranjang masih kosong. <a href="<?= BASE_URL ?>/pelanggan/index.php">Yuk pilih menu &rarr;</a></p>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <div class="cart-item">
          <div>
            <strong><?= e($it['menu']['nama_menu']) ?></strong><br>
            <span class="text-muted"><?= rupiah($it['menu']['harga']) ?> x <?= $it['jumlah'] ?></span>
          </div>
          <div style="display:flex;align-items:center;gap:14px">
            <form method="post" action="<?= BASE_URL ?>/pelanggan/update_keranjang.php" class="qty-control">
              <input type="hidden" name="menu_id" value="<?= $it['menu']['id'] ?>">
              <button type="submit" name="aksi" value="kurang">−</button>
              <span><?= $it['jumlah'] ?></span>
              <button type="submit" name="aksi" value="tambah">+</button>
            </form>
            <strong><?= rupiah($it['subtotal']) ?></strong>
            <form method="post" action="<?= BASE_URL ?>/pelanggan/update_keranjang.php">
              <input type="hidden" name="menu_id" value="<?= $it['menu']['id'] ?>">
              <input type="hidden" name="aksi" value="hapus">
              <button type="submit" class="btn btn-sm btn-danger">×</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      <div style="text-align:right;margin-top:16px;font-size:1.1rem">
        Total: <strong><?= rupiah($total) ?></strong>
      </div>
      <a href="<?= BASE_URL ?>/pelanggan/checkout.php" class="btn btn-block" style="margin-top:16px">Lanjutkan ke Konfirmasi Pesanan &rarr;</a>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
