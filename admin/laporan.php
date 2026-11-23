<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

/**
 * FR-14 & Fitur 4.6 Laporan Penjualan
 * Bab 6 (Other Requirements): "Sistem mendukung format ekspor laporan PDF dan Excel (XLSX)."
 * Halaman ini HANYA menampilkan & mengekspor LAPORAN (bukan nota/struk transaksi).
 */

$tglMulai = $_GET['tgl_mulai'] ?? date('Y-m-01');   // default: awal bulan ini
$tglAkhir = $_GET['tgl_akhir'] ?? date('Y-m-d');    // default: hari ini
$kasirId  = $_GET['kasir_id'] ?? '';
$metode   = $_GET['metode'] ?? '';
$statusTrx= $_GET['status'] ?? 'lunas'; // default hanya transaksi lunas dihitung sbg penjualan

$where = "WHERE DATE(t.created_at) BETWEEN ? AND ?";
$params = [$tglMulai, $tglAkhir];

if ($statusTrx !== '') { $where .= " AND t.status = ?"; $params[] = $statusTrx; }
if ($kasirId !== '')   { $where .= " AND t.kasir_id = ?"; $params[] = $kasirId; }
if ($metode !== '')    { $where .= " AND t.metode_pembayaran = ?"; $params[] = $metode; }

// Ringkasan
$sqlRingkasan = "
  SELECT COUNT(*) jumlah_transaksi, COALESCE(SUM(t.total_bayar),0) total_penjualan
  FROM transaksi t $where";
$stmt = $db->prepare($sqlRingkasan);
$stmt->execute($params);
$ringkasan = $stmt->fetch();

// Rincian per transaksi
$sqlDetail = "
  SELECT t.kode_transaksi, t.created_at, p.kode_pesanan, p.nomor_meja, p.tipe_pesanan,
         u.nama_lengkap AS nama_kasir, t.metode_pembayaran, t.total_bayar, t.status
  FROM transaksi t
  JOIN pesanan p ON p.id = t.pesanan_id
  JOIN users u ON u.id = t.kasir_id
  $where
  ORDER BY t.created_at ASC";
$stmt = $db->prepare($sqlDetail);
$stmt->execute($params);
$rincian = $stmt->fetchAll();

// Rekap menu terjual pada periode tsb
$sqlMenu = "
  SELECT dp.nama_menu_snapshot AS nama_menu, SUM(dp.jumlah) qty, SUM(dp.subtotal) total
  FROM detail_pesanan dp
  JOIN transaksi t ON t.pesanan_id = dp.pesanan_id
  $where
  GROUP BY dp.nama_menu_snapshot
  ORDER BY qty DESC";
$stmt = $db->prepare($sqlMenu);
$stmt->execute($params);
$rekapMenu = $stmt->fetchAll();

// Rekap per metode pembayaran
$sqlMetode = "
  SELECT t.metode_pembayaran, COUNT(*) jumlah, SUM(t.total_bayar) total
  FROM transaksi t $where GROUP BY t.metode_pembayaran";
$stmt = $db->prepare($sqlMetode);
$stmt->execute($params);
$rekapMetode = $stmt->fetchAll();

$kasirList = $db->query("SELECT id, nama_lengkap FROM users WHERE role='kasir' ORDER BY nama_lengkap")->fetchAll();

// Query string untuk dipakai di link export (Excel/Cetak) agar filter tetap konsisten
$queryExport = http_build_query([
    'tgl_mulai' => $tglMulai, 'tgl_akhir' => $tglAkhir,
    'kasir_id' => $kasirId, 'metode' => $metode, 'status' => $statusTrx,
]);

$pageTitle = 'Laporan Penjualan'; $activeMenu = 'laporan';
require __DIR__ . '/../includes/layout_admin_top.php';
?>

<div class="card no-print">
  <h3 class="mt-0">Filter Laporan</h3>
  <form method="get" action="" class="filter-bar">
    <div class="form-group"><label>Dari Tanggal</label><input type="date" name="tgl_mulai" value="<?= e($tglMulai) ?>"></div>
    <div class="form-group"><label>Sampai Tanggal</label><input type="date" name="tgl_akhir" value="<?= e($tglAkhir) ?>"></div>
    <div class="form-group">
      <label>Kasir</label>
      <select name="kasir_id">
        <option value="">Semua Kasir</option>
        <?php foreach ($kasirList as $k): ?>
          <option value="<?= $k['id'] ?>" <?= $kasirId==$k['id']?'selected':'' ?>><?= e($k['nama_lengkap']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label>Metode Bayar</label>
      <select name="metode">
        <option value="">Semua</option>
        <option value="tunai" <?= $metode==='tunai'?'selected':'' ?>>Tunai</option>
        <option value="non-tunai" <?= $metode==='non-tunai'?'selected':'' ?>>Non-Tunai</option>
      </select>
    </div>
    <div class="form-group">
      <label>Status Transaksi</label>
      <select name="status">
        <option value="lunas" <?= $statusTrx==='lunas'?'selected':'' ?>>Lunas</option>
        <option value="void" <?= $statusTrx==='void'?'selected':'' ?>>Void</option>
        <option value="" <?= $statusTrx===''?'selected':'' ?>>Semua</option>
      </select>
    </div>
    <div class="form-group"><button class="btn">Terapkan Filter</button></div>
  </form>
</div>

<div class="stat-grid no-print">
  <div class="stat-card"><div class="num"><?= (int)$ringkasan['jumlah_transaksi'] ?></div><div class="lbl">Jumlah Transaksi</div></div>
  <div class="stat-card"><div class="num"><?= rupiah($ringkasan['total_penjualan']) ?></div><div class="lbl">Total Penjualan</div></div>
  <div class="stat-card">
    <div class="num"><?= rupiah($ringkasan['jumlah_transaksi'] > 0 ? $ringkasan['total_penjualan']/$ringkasan['jumlah_transaksi'] : 0) ?></div>
    <div class="lbl">Rata-rata per Transaksi</div>
  </div>
</div>

<div class="card no-print flex-between">
  <div><strong>Ekspor Laporan Periode <?= e($tglMulai) ?> s/d <?= e($tglAkhir) ?></strong></div>
  <div>
    <a href="<?= BASE_URL ?>/admin/laporan_export_excel.php?<?= $queryExport ?>" class="btn btn-success">⬇️ Export Excel</a>
    <a href="<?= BASE_URL ?>/admin/laporan_cetak.php?<?= $queryExport ?>" target="_blank" class="btn btn-info">🖨️ Cetak / Simpan PDF</a>
  </div>
</div>

<div class="card">
  <h3>Rekap per Metode Pembayaran</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Metode Pembayaran</th><th>Jumlah Transaksi</th><th>Total</th></tr></thead>
    <tbody>
      <?php if (!$rekapMetode): ?><tr><td colspan="3" class="text-muted">Tidak ada data.</td></tr><?php endif; ?>
      <?php foreach ($rekapMetode as $rm): ?>
        <tr><td><?= e(ucfirst($rm['metode_pembayaran'])) ?></td><td><?= (int)$rm['jumlah'] ?></td><td><?= rupiah($rm['total']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="card">
  <h3>Rekap Menu Terjual</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>#</th><th>Nama Menu</th><th>Qty Terjual</th><th>Total Penjualan</th></tr></thead>
    <tbody>
      <?php if (!$rekapMenu): ?><tr><td colspan="4" class="text-muted">Tidak ada data.</td></tr><?php endif; ?>
      <?php foreach ($rekapMenu as $i => $rm): ?>
        <tr><td><?= $i+1 ?></td><td><?= e($rm['nama_menu']) ?></td><td><?= (int)$rm['qty'] ?></td><td><?= rupiah($rm['total']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<div class="card">
  <h3>Rincian Transaksi</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Kode Transaksi</th><th>Kode Pesanan</th><th>Meja</th><th>Tipe</th><th>Kasir</th><th>Metode</th><th>Total</th><th>Status</th><th>Waktu</th></tr></thead>
    <tbody>
      <?php if (!$rincian): ?><tr><td colspan="9" class="text-muted">Tidak ada transaksi pada periode ini.</td></tr><?php endif; ?>
      <?php foreach ($rincian as $r): ?>
        <tr>
          <td><?= e($r['kode_transaksi']) ?></td>
          <td><?= e($r['kode_pesanan']) ?></td>
          <td><?= e($r['nomor_meja']) ?></td>
          <td><?= e(ucfirst($r['tipe_pesanan'])) ?></td>
          <td><?= e($r['nama_kasir']) ?></td>
          <td><?= e(ucfirst($r['metode_pembayaran'])) ?></td>
          <td><?= rupiah($r['total_bayar']) ?></td>
          <td><span class="badge <?= $r['status']==='lunas'?'badge-success':'badge-danger' ?>"><?= e(ucfirst($r['status'])) ?></span></td>
          <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_admin_bottom.php'; ?>
