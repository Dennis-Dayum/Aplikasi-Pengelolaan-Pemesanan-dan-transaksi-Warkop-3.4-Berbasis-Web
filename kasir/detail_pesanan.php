<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('kasir');
$db = getDB();

$id = $_GET['id'] ?? null;
$stmt = $db->prepare('SELECT * FROM pesanan WHERE id=?');
$stmt->execute([$id]);
$pesanan = $stmt->fetch();
if (!$pesanan) { setFlash('danger', 'Pesanan tidak ditemukan.'); redirect('/kasir/dashboard.php'); }

$stmt = $db->prepare('SELECT * FROM detail_pesanan WHERE pesanan_id=?');
$stmt->execute([$id]);
$items = $stmt->fetchAll();

$stmt = $db->prepare('SELECT * FROM transaksi WHERE pesanan_id=? ORDER BY id DESC LIMIT 1');
$stmt->execute([$id]);
$transaksi = $stmt->fetch();

$sudahLunas = $transaksi && $transaksi['status'] === 'lunas';

// ---- Proses form (bayar / ubah status) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Token keamanan tidak valid.'); redirect('/kasir/detail_pesanan.php?id=' . $id);
    }
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'bayar') {
        // NFR-17: Kasir hanya dapat memproses pembayaran atas pesanan yang telah dikonfirmasi
        // (yaitu pesanan yang statusnya masih 'diproses' / belum dibayar sebelumnya)
        if ($pesanan['status'] === 'dibatalkan') {
            setFlash('danger', 'Pesanan ini sudah dibatalkan dan tidak dapat diproses.');
        } elseif ($sudahLunas) {
            setFlash('warning', 'Pesanan ini sudah dibayar sebelumnya.');
        } else {
            $metode = $_POST['metode_pembayaran'] ?? 'tunai';
            $jumlahBayar = (float)($_POST['jumlah_dibayar'] ?? 0);
            $total = (float)$pesanan['total_harga'];

            if ($metode === 'tunai' && $jumlahBayar < $total) {
                setFlash('danger', 'Jumlah uang yang dibayarkan kurang dari total tagihan.');
            } else {
                $kembalian = $metode === 'tunai' ? max(0, $jumlahBayar - $total) : 0;
                $kodeTrx = kodeUnik('TRX');
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare('INSERT INTO transaksi (kode_transaksi, pesanan_id, kasir_id, metode_pembayaran, total_bayar, jumlah_dibayar, kembalian, status) VALUES (?,?,?,?,?,?,?,\'lunas\')');
                    $stmt->execute([$kodeTrx, $id, $_SESSION['user_id'], $metode, $total, $metode==='tunai'?$jumlahBayar:$total, $kembalian]);
                    $db->commit();
                    logActivity($_SESSION['user_id'], "Memproses pembayaran pesanan {$pesanan['kode_pesanan']} ($kodeTrx)");
                    setFlash('success', "Pembayaran berhasil diproses. Kode transaksi: $kodeTrx" . ($kembalian > 0 ? '. Kembalian: ' . rupiah($kembalian) : ''));
                } catch (Exception $e) {
                    $db->rollBack();
                    setFlash('danger', 'Gagal memproses pembayaran: ' . $e->getMessage());
                }
            }
        }
    } elseif ($aksi === 'ubah_status') {
        $statusBaru = $_POST['status_baru'] ?? '';
        if (in_array($statusBaru, ['disajikan', 'selesai'], true)) {
            $db->prepare('UPDATE pesanan SET status=? WHERE id=?')->execute([$statusBaru, $id]);
            logActivity($_SESSION['user_id'], "Mengubah status pesanan {$pesanan['kode_pesanan']} menjadi $statusBaru");
            setFlash('success', 'Status pesanan berhasil diperbarui.');
        }
    }
    redirect('/kasir/detail_pesanan.php?id=' . $id);
}

$csrf = generateCsrfToken();
$pageTitle = 'Detail Pesanan ' . $pesanan['kode_pesanan']; $activeMenu = 'dashboard';
require __DIR__ . '/../includes/layout_kasir_top.php';
?>

<a href="<?= BASE_URL ?>/kasir/dashboard.php" class="btn btn-sm btn-secondary no-print">&larr; Kembali</a>

<div class="card">
  <div class="flex-between">
    <div>
      <h2 class="mt-0 mb-0"><?= e($pesanan['kode_pesanan']) ?></h2>
      <span class="text-muted">Meja <?= e($pesanan['nomor_meja']) ?> · <?= e(ucfirst($pesanan['tipe_pesanan'])) ?> · <?= date('d/m/Y H:i', strtotime($pesanan['created_at'])) ?></span>
    </div>
    <span class="badge <?= badgeStatusPesanan($pesanan['status']) ?>" style="font-size:.9rem"><?= labelStatusPesanan($pesanan['status']) ?></span>
  </div>
  <?php if (!empty($pesanan['catatan'])): ?>
    <p><em>Catatan pelanggan: <?= e($pesanan['catatan']) ?></em></p>
  <?php endif; ?>

  <div class="table-wrap">
  <table>
    <thead><tr><th>Menu</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td><?= e($it['nama_menu_snapshot']) ?></td>
        <td><?= rupiah($it['harga_satuan']) ?></td>
        <td><?= (int)$it['jumlah'] ?></td>
        <td><?= rupiah($it['subtotal']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr><td colspan="3" class="text-right"><strong>TOTAL</strong></td><td><strong><?= rupiah($pesanan['total_harga']) ?></strong></td></tr>
    </tfoot>
  </table>
  </div>
</div>

<?php if ($sudahLunas): ?>
  <div class="card alert alert-success">
    ✅ Pesanan ini sudah <strong>LUNAS</strong> — Kode Transaksi: <strong><?= e($transaksi['kode_transaksi']) ?></strong><br>
    Metode: <?= e(ucfirst($transaksi['metode_pembayaran'])) ?> · Dibayar: <?= rupiah($transaksi['jumlah_dibayar']) ?>
    <?php if ($transaksi['kembalian'] > 0): ?> · Kembalian: <?= rupiah($transaksi['kembalian']) ?><?php endif; ?>
    <br><span class="text-muted" style="font-size:.8rem">Transaksi yang sudah lunas tidak dapat diubah. Hubungi Admin/Owner jika perlu pembatalan (void).</span>
  </div>

  <?php if ($pesanan['status'] !== 'dibatalkan'): ?>
  <div class="card">
    <h3 class="mt-0">Update Status Penyajian</h3>
    <form method="post" action="" style="display:flex;gap:10px">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="aksi" value="ubah_status">
      <?php if ($pesanan['status'] === 'diproses'): ?>
        <button type="submit" name="status_baru" value="disajikan" class="btn btn-info">Tandai Disajikan</button>
      <?php elseif ($pesanan['status'] === 'disajikan'): ?>
        <button type="submit" name="status_baru" value="selesai" class="btn btn-success">Tandai Selesai</button>
      <?php else: ?>
        <span class="text-muted">Pesanan sudah selesai.</span>
      <?php endif; ?>
    </form>
  </div>
  <?php endif; ?>

<?php elseif ($pesanan['status'] === 'dibatalkan'): ?>
  <div class="card alert alert-danger">Pesanan ini telah dibatalkan.</div>
<?php else: ?>
  <div class="card">
    <h3 class="mt-0">💳 Proses Pembayaran</h3>
    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="aksi" value="bayar">
      <div class="form-row">
        <div class="form-group">
          <label>Metode Pembayaran</label>
          <select name="metode_pembayaran" id="metode_pembayaran" onchange="document.getElementById('wrapJumlahBayar').style.display = this.value==='tunai' ? 'block':'none'">
            <option value="tunai">Tunai</option>
            <option value="non-tunai">Non-Tunai (QRIS/Debit/dll.)</option>
          </select>
        </div>
        <div class="form-group" id="wrapJumlahBayar">
          <label>Jumlah Dibayar (Rp)</label>
          <input type="number" min="0" step="500" id="jumlah_dibayar" name="jumlah_dibayar" oninput="hitungKembalian()">
        </div>
      </div>
      <p id="total_bayar" data-total="<?= (float)$pesanan['total_harga'] ?>">Total Tagihan: <strong><?= rupiah($pesanan['total_harga']) ?></strong></p>
      <p>Kembalian: <strong id="kembalian_display">Rp 0</strong></p>
      <button type="submit" class="btn btn-success" data-confirm="Konfirmasi pembayaran pesanan ini?">Konfirmasi Pembayaran</button>
    </form>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/layout_kasir_bottom.php'; ?>
