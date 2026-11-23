<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

$status = $_GET['status'] ?? '';
$tanggal = $_GET['tanggal'] ?? '';

$sql = "SELECT * FROM pesanan WHERE 1=1";
$params = [];
if ($status !== '') { $sql .= " AND status=?"; $params[] = $status; }
if ($tanggal !== '') { $sql .= " AND DATE(created_at)=?"; $params[] = $tanggal; }
$sql .= " ORDER BY created_at DESC LIMIT 200";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pesananList = $stmt->fetchAll();

$pageTitle = 'Semua Pesanan'; $activeMenu = 'pesanan';
require __DIR__ . '/../includes/layout_admin_top.php';
?>

<div class="card">
  <form method="get" action="" class="filter-bar">
    <div class="form-group">
      <label>Status</label>
      <select name="status">
        <option value="">Semua</option>
        <?php foreach (['diproses','disajikan','selesai','dibatalkan'] as $s): ?>
          <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= labelStatusPesanan($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" value="<?= e($tanggal) ?>"></div>
    <div class="form-group"><button class="btn btn-outline">Filter</button></div>
    <div class="form-group"><a href="<?= BASE_URL ?>/admin/pesanan.php" class="btn btn-secondary">Reset</a></div>
  </form>
</div>

<div class="card">
  <div class="table-wrap">
  <table>
    <thead><tr><th>Kode Pesanan</th><th>Meja</th><th>Tipe</th><th>Total</th><th>Status</th><th>Waktu</th></tr></thead>
    <tbody>
    <?php if (!$pesananList): ?>
      <tr><td colspan="6" class="text-muted">Tidak ada data.</td></tr>
    <?php endif; ?>
    <?php foreach ($pesananList as $p): ?>
      <tr>
        <td><strong><?= e($p['kode_pesanan']) ?></strong></td>
        <td><?= e($p['nomor_meja']) ?></td>
        <td><?= e(ucfirst($p['tipe_pesanan'])) ?></td>
        <td><?= rupiah($p['total_harga']) ?></td>
        <td><span class="badge <?= badgeStatusPesanan($p['status']) ?>"><?= labelStatusPesanan($p['status']) ?></span></td>
        <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_admin_bottom.php'; ?>
