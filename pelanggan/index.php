<?php
require_once __DIR__ . '/../includes/pelanggan_bootstrap.php';
$db = getDB();

$kategoriId = $_GET['kategori'] ?? '';
$kategoriList = $db->query('SELECT * FROM kategori_menu ORDER BY nama_kategori')->fetchAll();

$sql = "SELECT * FROM menu WHERE is_active=1";
$params = [];
if ($kategoriId !== '') { $sql .= " AND kategori_id=?"; $params[] = $kategoriId; }
$sql .= " ORDER BY nama_menu";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$menuList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Menu - Warkop 3.4</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="customer-header">
  <div class="brand">☕ Warkop 3.4 <small>Self-Order Menu</small></div>
  <a href="<?= BASE_URL ?>/pelanggan/keranjang.php" class="cart-link">🛒 Keranjang (<?= jumlahItemKeranjang() ?>)</a>
</div>

<div class="customer-wrap">

  <?php if ($flash = getFlash()): ?>
    <div class="alert alert-<?= e($flash['type']) ?>" data-autohide><?= e($flash['message']) ?></div>
  <?php endif; ?>

  <div class="card" style="margin-bottom:18px">
    <p class="text-muted mt-0 mb-0">Pilih menu favoritmu, masukkan ke keranjang, lalu isi nomor meja untuk mengirim pesanan. Cek status pesanan kapan saja menggunakan kode pesanan.</p>
  </div>

  <div class="category-tabs">
    <a href="<?= BASE_URL ?>/pelanggan/index.php" class="<?= $kategoriId===''?'active':'' ?>">Semua</a>
    <?php foreach ($kategoriList as $k): ?>
      <a href="<?= BASE_URL ?>/pelanggan/index.php?kategori=<?= $k['id'] ?>" class="<?= $kategoriId==$k['id']?'active':'' ?>"><?= e($k['nama_kategori']) ?></a>
    <?php endforeach; ?>
  </div>

  <div class="menu-grid">
    <?php if (!$menuList): ?>
      <p class="text-muted">Belum ada menu tersedia.</p>
    <?php endif; ?>
    <?php foreach ($menuList as $m): ?>
      <div class="menu-card">
        <?php if ($m['foto']): ?>
          <div class="photo" style="background-image:url('<?= BASE_URL ?>/uploads/menu/<?= e($m['foto']) ?>')"></div>
        <?php else: ?>
          <div class="photo">☕</div>
        <?php endif; ?>
        <div class="body">
          <h4><?= e($m['nama_menu']) ?></h4>
          <div class="desc"><?= e($m['deskripsi']) ?></div>
          <div class="price"><?= rupiah($m['harga']) ?></div>
          <?php if ($m['status_ketersediaan'] === 'tersedia'): ?>
            <form method="post" action="<?= BASE_URL ?>/pelanggan/tambah_keranjang.php">
              <input type="hidden" name="menu_id" value="<?= $m['id'] ?>">
              <input type="hidden" name="redirect_kategori" value="<?= e($kategoriId) ?>">
              <button type="submit" class="btn btn-block btn-sm">+ Tambah ke Keranjang</button>
            </form>
          <?php else: ?>
            <div class="stock-out">Sedang Habis</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card" style="margin-top:24px;text-align:center">
    <a href="<?= BASE_URL ?>/pelanggan/status_pesanan.php">🔍 Cek Status Pesanan Saya</a>
    &nbsp;|&nbsp;
    <a href="<?= BASE_URL ?>/auth/login.php">Login Admin / Kasir</a>
  </div>
</div>

</body>
</html>
