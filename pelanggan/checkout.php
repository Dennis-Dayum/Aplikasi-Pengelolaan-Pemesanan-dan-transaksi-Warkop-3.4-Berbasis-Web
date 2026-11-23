<?php
require_once __DIR__ . '/../includes/pelanggan_bootstrap.php';
$db = getDB();

if (empty($_SESSION['keranjang'])) {
    setFlash('danger', 'Keranjang Anda kosong.');
    redirect('/pelanggan/index.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomorMeja = trim($_POST['nomor_meja'] ?? '');
    $tipe = $_POST['tipe_pesanan'] ?? 'dine-in';
    $catatan = trim($_POST['catatan'] ?? '');

    if ($nomorMeja === '') {
        $errors[] = 'Nomor meja wajib diisi.';
    }

    if (empty($errors)) {
        // Ambil ulang data menu terbaru dari database (harga & ketersediaan) - jangan percaya session
        $ids = array_keys($_SESSION['keranjang']);
        $in = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT * FROM menu WHERE id IN ($in) AND is_active=1");
        $stmt->execute($ids);
        $menuRows = $stmt->fetchAll();
        $menuMap = [];
        foreach ($menuRows as $m) $menuMap[$m['id']] = $m;

        $itemsValid = [];
        $total = 0;
        foreach ($_SESSION['keranjang'] as $menuId => $jumlah) {
            if (!isset($menuMap[$menuId])) { $errors[] = 'Salah satu menu di keranjang sudah tidak tersedia lagi.'; continue; }
            $m = $menuMap[$menuId];
            // NFR-16: Pelanggan hanya dapat memesan menu yang berstatus tersedia
            if ($m['status_ketersediaan'] !== 'tersedia') {
                $errors[] = "Menu '{$m['nama_menu']}' baru saja habis, silakan hapus dari keranjang.";
                continue;
            }
            $subtotal = $jumlah * $m['harga'];
            $total += $subtotal;
            $itemsValid[] = ['menu' => $m, 'jumlah' => $jumlah, 'subtotal' => $subtotal];
        }

        if (empty($errors) && $itemsValid) {
            $kodePesanan = kodeUnik('ORD');
            $db->beginTransaction();
            try {
                // FR-9: Pelanggan dapat mengirimkan pesanan (submit order) beserta nomor meja
                $stmt = $db->prepare("INSERT INTO pesanan (kode_pesanan, nomor_meja, tipe_pesanan, status, total_harga, catatan) VALUES (?,?,?,'diproses',?,?)");
                $stmt->execute([$kodePesanan, $nomorMeja, $tipe, $total, $catatan]);
                $pesananId = $db->lastInsertId();

                $stmtItem = $db->prepare("INSERT INTO detail_pesanan (pesanan_id, menu_id, nama_menu_snapshot, harga_satuan, jumlah, subtotal) VALUES (?,?,?,?,?,?)");
                foreach ($itemsValid as $it) {
                    $stmtItem->execute([$pesananId, $it['menu']['id'], $it['menu']['nama_menu'], $it['menu']['harga'], $it['jumlah'], $it['subtotal']]);
                }
                $db->commit();

                $_SESSION['keranjang'] = [];
                $_SESSION['kode_pesanan_terakhir'] = $kodePesanan;
                redirect('/pelanggan/status_pesanan.php?kode=' . urlencode($kodePesanan));
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Gagal mengirim pesanan: ' . $e->getMessage();
            }
        }
    }
}

// Data untuk ditampilkan di ringkasan
$ids = array_keys($_SESSION['keranjang']);
$in = implode(',', array_fill(0, count($ids), '?'));
$stmt = $db->prepare("SELECT * FROM menu WHERE id IN ($in)");
$stmt->execute($ids);
$menuRows = $stmt->fetchAll();
$items = []; $total = 0;
foreach ($menuRows as $m) {
    $jumlah = $_SESSION['keranjang'][$m['id']];
    $subtotal = $jumlah * $m['harga'];
    $total += $subtotal;
    $items[] = ['menu' => $m, 'jumlah' => $jumlah, 'subtotal' => $subtotal];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Konfirmasi Pesanan - Warkop 3.4</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="customer-header">
  <div class="brand">☕ Warkop 3.4 <small>Konfirmasi Pesanan</small></div>
  <a href="<?= BASE_URL ?>/pelanggan/keranjang.php" class="cart-link">&larr; Kembali ke Keranjang</a>
</div>

<div class="customer-wrap">

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="card">
    <h3 class="mt-0">Ringkasan Pesanan</h3>
    <?php foreach ($items as $it): ?>
      <div class="cart-item">
        <div><?= e($it['menu']['nama_menu']) ?> x <?= $it['jumlah'] ?></div>
        <div><?= rupiah($it['subtotal']) ?></div>
      </div>
    <?php endforeach; ?>
    <div style="text-align:right;margin-top:14px;font-size:1.1rem">Total: <strong><?= rupiah($total) ?></strong></div>
  </div>

  <div class="card">
    <form method="post" action="">
      <div class="form-row">
        <div class="form-group">
          <label>Nomor Meja</label>
          <input type="text" name="nomor_meja" required value="<?= e($_POST['nomor_meja'] ?? '') ?>" placeholder="Contoh: 5">
        </div>
        <div class="form-group">
          <label>Tipe Pesanan</label>
          <select name="tipe_pesanan">
            <option value="dine-in">Makan di Tempat (Dine-in)</option>
            <option value="take-away">Bawa Pulang (Take-away)</option>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Catatan (opsional)</label>
        <textarea name="catatan" placeholder="Contoh: tidak pakai gula, dsb."><?= e($_POST['catatan'] ?? '') ?></textarea>
      </div>
      <button type="submit" class="btn btn-block">Kirim Pesanan</button>
    </form>
  </div>

</div>

</body>
</html>
