<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Token keamanan tidak valid.'); redirect('/admin/stok.php');
    }
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah_bahan') {
        $nama = trim($_POST['nama_bahan'] ?? '');
        $satuan = trim($_POST['satuan'] ?? 'pcs');
        $stokAwal = (float)($_POST['stok'] ?? 0);
        $stokMin = (float)($_POST['stok_minimum'] ?? 0);
        if ($nama !== '') {
            $stmt = $db->prepare('INSERT INTO bahan_baku (nama_bahan, satuan, stok, stok_minimum) VALUES (?,?,?,?)');
            $stmt->execute([$nama, $satuan, $stokAwal, $stokMin]);
            logActivity($_SESSION['user_id'], "Menambah bahan baku: $nama");
            setFlash('success', 'Bahan baku berhasil ditambahkan.');
        }
    } elseif ($aksi === 'update_stok') {
        $bahanId = $_POST['bahan_id'] ?? null;
        $jenis = $_POST['jenis'] ?? 'penyesuaian';
        $jumlah = (float)($_POST['jumlah'] ?? 0);
        $keterangan = trim($_POST['keterangan'] ?? '');
        if ($bahanId && $jumlah != 0) {
            $db->beginTransaction();
            try {
                if ($jenis === 'masuk') {
                    $db->prepare('UPDATE bahan_baku SET stok = stok + ? WHERE id=?')->execute([$jumlah, $bahanId]);
                } elseif ($jenis === 'keluar') {
                    $db->prepare('UPDATE bahan_baku SET stok = GREATEST(0, stok - ?) WHERE id=?')->execute([$jumlah, $bahanId]);
                } else {
                    $db->prepare('UPDATE bahan_baku SET stok = ? WHERE id=?')->execute([$jumlah, $bahanId]);
                }
                $db->prepare('INSERT INTO stok_log (bahan_baku_id, jenis, jumlah, keterangan, user_id) VALUES (?,?,?,?,?)')
                   ->execute([$bahanId, $jenis, $jumlah, $keterangan, $_SESSION['user_id']]);
                $db->commit();
                setFlash('success', 'Stok bahan baku berhasil diperbarui.');
            } catch (Exception $e) {
                $db->rollBack();
                setFlash('danger', 'Gagal memperbarui stok: ' . $e->getMessage());
            }
        }
    } elseif ($aksi === 'hapus_bahan' && !empty($_POST['id'])) {
        $db->prepare('DELETE FROM bahan_baku WHERE id=?')->execute([$_POST['id']]);
        setFlash('success', 'Bahan baku dihapus.');
    }
    redirect('/admin/stok.php');
}

$bahanList = $db->query('SELECT * FROM bahan_baku ORDER BY nama_bahan')->fetchAll();
$csrf = generateCsrfToken();
$pageTitle = 'Stok Bahan Baku'; $activeMenu = 'stok';
require __DIR__ . '/../includes/layout_admin_top.php';
?>

<div class="card">
  <h3>Tambah Bahan Baku</h3>
  <form method="post" action="" class="form-row" style="align-items:flex-end">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="aksi" value="tambah_bahan">
    <div class="form-group"><label>Nama Bahan</label><input type="text" name="nama_bahan" required></div>
    <div class="form-group"><label>Satuan</label><input type="text" name="satuan" value="pcs" required></div>
    <div class="form-group"><label>Stok Awal</label><input type="number" step="0.01" name="stok" value="0" required></div>
    <div class="form-group"><label>Stok Minimum</label><input type="number" step="0.01" name="stok_minimum" value="0" required></div>
    <div class="form-group"><button class="btn">+ Tambah</button></div>
  </form>
</div>

<div class="card">
  <h3>Daftar Bahan Baku</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Nama Bahan</th><th>Stok Saat Ini</th><th>Stok Minimum</th><th>Status</th><th style="width:420px">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($bahanList as $b): $rendah = $b['stok'] <= $b['stok_minimum']; ?>
      <tr>
        <td><?= e($b['nama_bahan']) ?></td>
        <td><?= rtrim(rtrim(number_format($b['stok'],2,'.',' '),'0'),'.') ?> <?= e($b['satuan']) ?></td>
        <td><?= rtrim(rtrim(number_format($b['stok_minimum'],2,'.',' '),'0'),'.') ?> <?= e($b['satuan']) ?></td>
        <td><span class="badge <?= $rendah ? 'badge-danger' : 'badge-success' ?>"><?= $rendah ? 'Stok Menipis' : 'Aman' ?></span></td>
        <td>
          <form method="post" action="" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="aksi" value="update_stok">
            <input type="hidden" name="bahan_id" value="<?= $b['id'] ?>">
            <select name="jenis" style="width:auto">
              <option value="masuk">+ Masuk</option>
              <option value="keluar">- Keluar</option>
              <option value="penyesuaian">Set Nilai</option>
            </select>
            <input type="number" step="0.01" name="jumlah" placeholder="Jumlah" required style="width:100px">
            <input type="text" name="keterangan" placeholder="Keterangan" style="width:130px">
            <button class="btn btn-sm btn-outline" type="submit">Update</button>
          </form>
          <form method="post" action="" style="display:inline" data-confirm="Hapus bahan '<?= e($b['nama_bahan']) ?>'?">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="aksi" value="hapus_bahan">
            <input type="hidden" name="id" value="<?= $b['id'] ?>">
            <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_admin_bottom.php'; ?>
