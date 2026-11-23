<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

/**
 * NFR-04: data transaksi tidak dapat dihapus tanpa konfirmasi dan hak akses admin.
 * NFR-19: Transaksi yang telah dibayar tidak dapat diubah, hanya dapat dibatalkan
 *         (void) oleh admin dengan alasan yang dicatat.
 * Karena itu tidak ada fitur "hapus transaksi" - yang ada hanya VOID dengan alasan wajib.
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Token keamanan tidak valid.'); redirect('/admin/void_transaksi.php');
    }
    $transaksiId = $_POST['transaksi_id'] ?? null;
    $alasan = trim($_POST['alasan_void'] ?? '');

    if (!$transaksiId || $alasan === '') {
        setFlash('danger', 'Alasan pembatalan (void) wajib diisi.');
    } else {
        $stmt = $db->prepare('SELECT * FROM transaksi WHERE id=?');
        $stmt->execute([$transaksiId]);
        $trx = $stmt->fetch();
        if (!$trx) {
            setFlash('danger', 'Transaksi tidak ditemukan.');
        } elseif ($trx['status'] === 'void') {
            setFlash('warning', 'Transaksi ini sudah berstatus void sebelumnya.');
        } else {
            $db->beginTransaction();
            try {
                $db->prepare("UPDATE transaksi SET status='void', alasan_void=?, void_by=?, void_at=NOW() WHERE id=?")
                   ->execute([$alasan, $_SESSION['user_id'], $transaksiId]);
                $db->prepare("UPDATE pesanan SET status='dibatalkan' WHERE id=?")
                   ->execute([$trx['pesanan_id']]);
                $db->commit();
                logActivity($_SESSION['user_id'], "Void transaksi {$trx['kode_transaksi']} - alasan: $alasan");
                setFlash('success', 'Transaksi berhasil di-void.');
            } catch (Exception $e) {
                $db->rollBack();
                setFlash('danger', 'Gagal memproses void: ' . $e->getMessage());
            }
        }
    }
    redirect('/admin/void_transaksi.php');
}

$transaksiList = $db->query("
    SELECT t.*, p.kode_pesanan, p.nomor_meja, u.nama_lengkap AS nama_kasir
    FROM transaksi t
    JOIN pesanan p ON p.id = t.pesanan_id
    JOIN users u ON u.id = t.kasir_id
    ORDER BY t.created_at DESC LIMIT 100
")->fetchAll();
$csrf = generateCsrfToken();
$pageTitle = 'Void Transaksi'; $activeMenu = 'void';
require __DIR__ . '/../includes/layout_admin_top.php';
?>

<div class="alert alert-info">
  Transaksi yang sudah <strong>lunas tidak dapat diedit</strong>. Jika terjadi kesalahan input kasir,
  gunakan tombol <strong>Void</strong> di bawah ini dan sertakan alasan pembatalan (wajib dicatat sesuai kebijakan sistem).
</div>

<div class="card">
  <div class="table-wrap">
  <table>
    <thead><tr><th>Kode Transaksi</th><th>Pesanan</th><th>Meja</th><th>Kasir</th><th>Total</th><th>Status</th><th>Waktu</th><th style="width:220px">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($transaksiList as $t): ?>
      <tr>
        <td><?= e($t['kode_transaksi']) ?></td>
        <td><?= e($t['kode_pesanan']) ?></td>
        <td><?= e($t['nomor_meja']) ?></td>
        <td><?= e($t['nama_kasir']) ?></td>
        <td><?= rupiah($t['total_bayar']) ?></td>
        <td>
          <?php if ($t['status'] === 'lunas'): ?>
            <span class="badge badge-success">Lunas</span>
          <?php else: ?>
            <span class="badge badge-danger">Void</span>
            <div class="text-muted" style="font-size:.72rem">Alasan: <?= e($t['alasan_void']) ?></div>
          <?php endif; ?>
        </td>
        <td><?= date('d/m/Y H:i', strtotime($t['created_at'])) ?></td>
        <td>
          <?php if ($t['status'] === 'lunas'): ?>
            <button type="button" class="btn btn-sm btn-danger" onclick="document.getElementById('voidModal<?= $t['id'] ?>').style.display='flex'">Void</button>
            <div id="voidModal<?= $t['id'] ?>" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:50">
              <form method="post" action="" class="card" style="max-width:380px;width:90%">
                <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
                <input type="hidden" name="transaksi_id" value="<?= $t['id'] ?>">
                <h4 class="mt-0">Void Transaksi <?= e($t['kode_transaksi']) ?></h4>
                <div class="form-group">
                  <label>Alasan Pembatalan (wajib)</label>
                  <textarea name="alasan_void" required placeholder="Contoh: salah input pesanan oleh kasir"></textarea>
                </div>
                <button type="submit" class="btn btn-danger" data-confirm="Yakin ingin void transaksi ini? Aksi tidak dapat dibatalkan.">Konfirmasi Void</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('voidModal<?= $t['id'] ?>').style.display='none'">Batal</button>
              </form>
            </div>
          <?php else: ?>
            <span class="text-muted">—</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_admin_bottom.php'; ?>
