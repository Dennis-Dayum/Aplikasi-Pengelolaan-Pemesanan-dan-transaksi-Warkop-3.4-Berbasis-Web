<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

$kategoriFilter = $_GET['kategori'] ?? '';
$cari = trim($_GET['cari'] ?? '');

$sql = "SELECT m.*, k.nama_kategori FROM menu m JOIN kategori_menu k ON k.id=m.kategori_id WHERE m.is_active=1";
$params = [];
if ($kategoriFilter !== '') { $sql .= " AND m.kategori_id=?"; $params[] = $kategoriFilter; }
if ($cari !== '') { $sql .= " AND m.nama_menu LIKE ?"; $params[] = "%$cari%"; }
$sql .= " ORDER BY k.nama_kategori, m.nama_menu";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$menuList = $stmt->fetchAll();

$kategoriList = $db->query('SELECT * FROM kategori_menu ORDER BY nama_kategori')->fetchAll();

$pageTitle = 'Data Menu'; $activeMenu = 'menu';
require __DIR__ . '/../includes/layout_admin_top.php';
?>

<div class="flex-between">
  <h2 class="mt-0">Data Menu</h2>
  <a href="<?= BASE_URL ?>/admin/menu_form.php" class="btn">+ Tambah Menu</a>
</div>

<div class="card">
  <form method="get" action="" class="filter-bar">
    <div class="form-group">
      <label>Kategori</label>
      <select name="kategori" onchange="this.form.submit()">
        <option value="">Semua Kategori</option>
        <?php foreach ($kategoriList as $k): ?>
          <option value="<?= $k['id'] ?>" <?= $kategoriFilter == $k['id'] ? 'selected' : '' ?>><?= e($k['nama_kategori']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Cari Nama Menu</label>
      <input type="search" name="cari" value="<?= e($cari) ?>" placeholder="Ketik nama menu...">
    </div>
    <div class="form-group"><button class="btn btn-outline">Cari</button></div>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
  <table>
    <thead><tr><th>Foto</th><th>Nama Menu</th><th>Kategori</th><th>Harga</th><th>Ketersediaan</th><th style="width:190px">Aksi</th></tr></thead>
    <tbody>
    <?php if (!$menuList): ?>
      <tr><td colspan="6" class="text-muted">Tidak ada menu ditemukan.</td></tr>
    <?php endif; ?>
    <?php foreach ($menuList as $m): ?>
      <tr>
        <td>
          <?php if ($m['foto']): ?>
            <img src="<?= BASE_URL ?>/uploads/menu/<?= e($m['foto']) ?>" alt="" style="width:50px;height:50px;object-fit:cover;border-radius:6px">
          <?php else: ?>
            <div style="width:50px;height:50px;border-radius:6px;background:#eee2ce;display:flex;align-items:center;justify-content:center">☕</div>
          <?php endif; ?>
        </td>
        <td><?= e($m['nama_menu']) ?></td>
        <td><?= e($m['nama_kategori']) ?></td>
        <td><?= rupiah($m['harga']) ?></td>
        <td>
          <span class="badge <?= $m['status_ketersediaan']==='tersedia' ? 'badge-success' : 'badge-danger' ?>">
            <?= $m['status_ketersediaan']==='tersedia' ? 'Tersedia' : 'Habis' ?>
          </span>
        </td>
        <td>
          <a href="<?= BASE_URL ?>/admin/menu_form.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline">Ubah</a>
          <a href="<?= BASE_URL ?>/admin/menu_hapus.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-danger" data-confirm="Hapus/nonaktifkan menu '<?= e($m['nama_menu']) ?>'?">Hapus</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_admin_bottom.php'; ?>
