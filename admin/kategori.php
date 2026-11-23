<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

// ---- Proses simpan / update / hapus (POST) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Token keamanan tidak valid.');
        redirect('/admin/kategori.php');
    }
    $aksi = $_POST['aksi'] ?? '';
    $nama = trim($_POST['nama_kategori'] ?? '');

    if ($aksi === 'tambah' && $nama !== '') {
        $stmt = $db->prepare('INSERT INTO kategori_menu (nama_kategori) VALUES (?)');
        $stmt->execute([$nama]);
        logActivity($_SESSION['user_id'], "Menambah kategori: $nama");
        setFlash('success', 'Kategori berhasil ditambahkan.');
    } elseif ($aksi === 'ubah' && $nama !== '' && !empty($_POST['id'])) {
        $stmt = $db->prepare('UPDATE kategori_menu SET nama_kategori=? WHERE id=?');
        $stmt->execute([$nama, $_POST['id']]);
        logActivity($_SESSION['user_id'], "Mengubah kategori #{$_POST['id']}: $nama");
        setFlash('success', 'Kategori berhasil diperbarui.');
    } elseif ($aksi === 'hapus' && !empty($_POST['id'])) {
        $cekMenu = $db->prepare('SELECT COUNT(*) c FROM menu WHERE kategori_id=?');
        $cekMenu->execute([$_POST['id']]);
        if ($cekMenu->fetch()['c'] > 0) {
            setFlash('danger', 'Kategori tidak dapat dihapus karena masih memiliki menu terkait.');
        } else {
            $stmt = $db->prepare('DELETE FROM kategori_menu WHERE id=?');
            $stmt->execute([$_POST['id']]);
            logActivity($_SESSION['user_id'], "Menghapus kategori #{$_POST['id']}");
            setFlash('success', 'Kategori berhasil dihapus.');
        }
    }
    redirect('/admin/kategori.php');
}

$kategoriList = $db->query('SELECT k.*, (SELECT COUNT(*) FROM menu m WHERE m.kategori_id=k.id) jumlah_menu FROM kategori_menu k ORDER BY nama_kategori')->fetchAll();
$csrf = generateCsrfToken();

$pageTitle = 'Kategori Menu'; $activeMenu = 'kategori';
require __DIR__ . '/../includes/layout_admin_top.php';
?>

<div class="card">
  <h3>Tambah Kategori Baru</h3>
  <form method="post" action="" class="form-row" style="align-items:flex-end">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="aksi" value="tambah">
    <div class="form-group" style="flex:2">
      <label>Nama Kategori</label>
      <input type="text" name="nama_kategori" required placeholder="Contoh: Kopi, Makanan Berat, dll.">
    </div>
    <div class="form-group" style="flex:0 0 auto">
      <button type="submit" class="btn">+ Tambah Kategori</button>
    </div>
  </form>
</div>

<div class="card">
  <h3>Daftar Kategori</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Nama Kategori</th><th>Jumlah Menu</th><th style="width:220px">Aksi</th></tr></thead>
    <tbody>
    <?php if (!$kategoriList): ?>
      <tr><td colspan="3" class="text-muted">Belum ada kategori.</td></tr>
    <?php endif; ?>
    <?php foreach ($kategoriList as $k): ?>
      <tr>
        <td>
          <form method="post" action="" style="display:flex;gap:8px">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="aksi" value="ubah">
            <input type="hidden" name="id" value="<?= $k['id'] ?>">
            <input type="text" name="nama_kategori" value="<?= e($k['nama_kategori']) ?>" style="max-width:220px">
            <button type="submit" class="btn btn-sm btn-outline">Simpan</button>
          </form>
        </td>
        <td><?= (int)$k['jumlah_menu'] ?> menu</td>
        <td>
          <form method="post" action="" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="aksi" value="hapus">
            <input type="hidden" name="id" value="<?= $k['id'] ?>">
            <button type="submit" class="btn btn-sm btn-danger" data-confirm="Hapus kategori '<?= e($k['nama_kategori']) ?>'?">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_admin_bottom.php'; ?>
