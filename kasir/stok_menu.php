<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('kasir');
$db = getDB();

// FR-12: Kasir dapat mengatur status ketersediaan menu (tersedia/habis).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Token keamanan tidak valid.'); redirect('/kasir/stok_menu.php');
    }
    $menuId = $_POST['menu_id'] ?? null;
    if ($menuId) {
        $db->prepare("UPDATE menu SET status_ketersediaan = IF(status_ketersediaan='tersedia','habis','tersedia') WHERE id=?")->execute([$menuId]);
        logActivity($_SESSION['user_id'], "Mengubah status ketersediaan menu #$menuId");
        setFlash('success', 'Status ketersediaan menu berhasil diperbarui.');
    }
    redirect('/kasir/stok_menu.php');
}

$menuList = $db->query("SELECT m.*, k.nama_kategori FROM menu m JOIN kategori_menu k ON k.id=m.kategori_id WHERE m.is_active=1 ORDER BY k.nama_kategori, m.nama_menu")->fetchAll();
$csrf = generateCsrfToken();
$pageTitle = 'Ketersediaan Menu'; $activeMenu = 'stok';
require __DIR__ . '/../includes/layout_kasir_top.php';
?>

<div class="card">
  <p class="text-muted">Tandai menu yang bahannya sedang habis agar tidak dapat dipesan pelanggan.</p>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Nama Menu</th><th>Kategori</th><th>Harga</th><th>Status</th><th style="width:140px">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($menuList as $m): ?>
      <tr>
        <td><?= e($m['nama_menu']) ?></td>
        <td><?= e($m['nama_kategori']) ?></td>
        <td><?= rupiah($m['harga']) ?></td>
        <td>
          <span class="badge <?= $m['status_ketersediaan']==='tersedia' ? 'badge-success' : 'badge-danger' ?>">
            <?= $m['status_ketersediaan']==='tersedia' ? 'Tersedia' : 'Habis' ?>
          </span>
        </td>
        <td>
          <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="menu_id" value="<?= $m['id'] ?>">
            <button type="submit" class="btn btn-sm <?= $m['status_ketersediaan']==='tersedia' ? 'btn-danger' : 'btn-success' ?>">
              Tandai <?= $m['status_ketersediaan']==='tersedia' ? 'Habis' : 'Tersedia' ?>
            </button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_kasir_bottom.php'; ?>
