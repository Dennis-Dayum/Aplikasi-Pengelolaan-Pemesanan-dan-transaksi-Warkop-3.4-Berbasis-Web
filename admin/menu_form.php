<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

$id = $_GET['id'] ?? null;
$menu = null;
if ($id) {
    $stmt = $db->prepare('SELECT * FROM menu WHERE id=?');
    $stmt->execute([$id]);
    $menu = $stmt->fetch();
    if (!$menu) { setFlash('danger', 'Menu tidak ditemukan.'); redirect('/admin/menu.php'); }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Token keamanan tidak valid.';
    } else {
        $nama = trim($_POST['nama_menu'] ?? '');
        $kategoriId = $_POST['kategori_id'] ?? '';
        $harga = $_POST['harga'] ?? '';
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $status = $_POST['status_ketersediaan'] ?? 'tersedia';

        if ($nama === '') $errors[] = 'Nama menu wajib diisi.';
        if (!$kategoriId) $errors[] = 'Kategori wajib dipilih.';
        if (!is_numeric($harga) || $harga < 0) $errors[] = 'Harga harus berupa angka valid.';

        $namaFoto = $menu['foto'] ?? null;
        if (empty($errors) && !empty($_FILES['foto']['name'])) {
            try {
                $uploaded = uploadFotoMenu($_FILES['foto'], $menu['foto'] ?? null);
                if ($uploaded) $namaFoto = $uploaded;
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (empty($errors)) {
            if ($menu) {
                // FR-5: Admin dapat mengubah data menu
                $stmt = $db->prepare('UPDATE menu SET kategori_id=?, nama_menu=?, deskripsi=?, harga=?, status_ketersediaan=?, foto=? WHERE id=?');
                $stmt->execute([$kategoriId, $nama, $deskripsi, $harga, $status, $namaFoto, $menu['id']]);
                logActivity($_SESSION['user_id'], "Mengubah menu #{$menu['id']}: $nama");
                setFlash('success', 'Menu berhasil diperbarui.');
            } else {
                // FR-4: Admin dapat menambahkan menu baru beserta harga, kategori, dan foto
                $stmt = $db->prepare('INSERT INTO menu (kategori_id, nama_menu, deskripsi, harga, status_ketersediaan, foto) VALUES (?,?,?,?,?,?)');
                $stmt->execute([$kategoriId, $nama, $deskripsi, $harga, $status, $namaFoto]);
                logActivity($_SESSION['user_id'], "Menambah menu baru: $nama");
                setFlash('success', 'Menu baru berhasil ditambahkan.');
            }
            redirect('/admin/menu.php');
        }
    }
}

$kategoriList = $db->query('SELECT * FROM kategori_menu ORDER BY nama_kategori')->fetchAll();
$csrf = generateCsrfToken();
$pageTitle = $menu ? 'Ubah Menu' : 'Tambah Menu'; $activeMenu = 'menu';
require __DIR__ . '/../includes/layout_admin_top.php';
?>

<div class="card" style="max-width:640px">
  <h3><?= $menu ? 'Ubah Menu' : 'Tambah Menu Baru' ?></h3>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <div class="form-group">
      <label>Nama Menu</label>
      <input type="text" name="nama_menu" required value="<?= e($menu['nama_menu'] ?? ($_POST['nama_menu'] ?? '')) ?>">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Kategori</label>
        <select name="kategori_id" required>
          <option value="">-- Pilih Kategori --</option>
          <?php foreach ($kategoriList as $k): ?>
            <option value="<?= $k['id'] ?>" <?= (($menu['kategori_id'] ?? $_POST['kategori_id'] ?? '') == $k['id']) ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Harga (Rp)</label>
        <input type="number" step="1" min="0" name="harga" required value="<?= e($menu['harga'] ?? ($_POST['harga'] ?? '')) ?>">
      </div>
    </div>
    <div class="form-group">
      <label>Deskripsi</label>
      <textarea name="deskripsi"><?= e($menu['deskripsi'] ?? ($_POST['deskripsi'] ?? '')) ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Status Ketersediaan</label>
        <select name="status_ketersediaan">
          <option value="tersedia" <?= (($menu['status_ketersediaan'] ?? '') === 'tersedia') ? 'selected' : '' ?>>Tersedia</option>
          <option value="habis" <?= (($menu['status_ketersediaan'] ?? '') === 'habis') ? 'selected' : '' ?>>Habis</option>
        </select>
      </div>
      <div class="form-group">
        <label>Foto Menu (maks. 2MB, jpg/png/webp)</label>
        <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
        <?php if (!empty($menu['foto'])): ?>
          <img src="<?= BASE_URL ?>/uploads/menu/<?= e($menu['foto']) ?>" style="width:60px;margin-top:8px;border-radius:6px">
        <?php endif; ?>
      </div>
    </div>
    <button type="submit" class="btn"><?= $menu ? 'Simpan Perubahan' : 'Tambah Menu' ?></button>
    <a href="<?= BASE_URL ?>/admin/menu.php" class="btn btn-secondary">Batal</a>
  </form>
</div>

<?php require __DIR__ . '/../includes/layout_admin_bottom.php'; ?>
