<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/laporan_query.php';
requireRole('admin');
$db = getDB();
$data = ambilDataLaporan($db);

/**
 * Halaman "Cetak / Simpan PDF" untuk Laporan Penjualan.
 * Didesain khusus untuk media cetak (@media print) - pengguna tinggal menekan
 * tombol "Cetak" lalu memilih "Save as PDF" pada dialog print browser untuk
 * mendapatkan file PDF laporan (sesuai kebutuhan ekspor PDF pada Bab 6 SRS),
 * tanpa perlu instalasi library PDF tambahan di server.
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Cetak Laporan Penjualan - Warkop 3.4</title>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; color:#222; margin: 30px; }
  h1 { font-size: 1.3rem; margin-bottom:2px; }
  .sub { color:#666; font-size:.85rem; margin-bottom: 18px; }
  table { width:100%; border-collapse: collapse; margin-bottom: 22px; font-size:.85rem; }
  th, td { border: 1px solid #ccc; padding: 6px 8px; text-align:left; }
  th { background: #f0e9dd; }
  .ringkasan { display:flex; gap: 20px; margin-bottom: 20px; }
  .ringkasan div { border:1px solid #ccc; border-radius:8px; padding:10px 16px; }
  .ringkasan .num { font-size:1.2rem; font-weight:700; }
  .section-title { font-weight:700; margin: 18px 0 6px; font-size: 1rem; }
  .toolbar { margin-bottom: 20px; }
  .toolbar button { padding:8px 18px; font-size:.9rem; cursor:pointer; }
  .text-right { text-align:right; }
  @media print { .toolbar { display:none; } body { margin: 10px; } }
</style>
</head>
<body>

<div class="toolbar">
  <button onclick="window.print()">🖨️ Cetak / Simpan sebagai PDF</button>
</div>

<h1>LAPORAN PENJUALAN - WARKOP 3.4</h1>
<div class="sub">
  Periode: <?= e($data['tgl_mulai']) ?> s/d <?= e($data['tgl_akhir']) ?><br>
  Dicetak oleh: <?= e(currentUser()['nama']) ?> pada <?= date('d/m/Y H:i') ?>
</div>

<div class="ringkasan">
  <div><div class="num"><?= (int)$data['ringkasan']['jumlah_transaksi'] ?></div>Jumlah Transaksi</div>
  <div><div class="num"><?= rupiah($data['ringkasan']['total_penjualan']) ?></div>Total Penjualan</div>
</div>

<div class="section-title">Rekap Menu Terjual</div>
<table>
  <thead><tr><th>No</th><th>Nama Menu</th><th>Qty Terjual</th><th class="text-right">Total Penjualan</th></tr></thead>
  <tbody>
    <?php if (!$data['rekap_menu']): ?><tr><td colspan="4">Tidak ada data.</td></tr><?php endif; ?>
    <?php foreach ($data['rekap_menu'] as $i => $rm): ?>
      <tr><td><?= $i+1 ?></td><td><?= e($rm['nama_menu']) ?></td><td><?= (int)$rm['qty'] ?></td><td class="text-right"><?= rupiah($rm['total']) ?></td></tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="section-title">Rincian Transaksi</div>
<table>
  <thead>
    <tr><th>Kode Transaksi</th><th>Kode Pesanan</th><th>Meja</th><th>Kasir</th><th>Metode</th><th class="text-right">Total</th><th>Status</th><th>Waktu</th></tr>
  </thead>
  <tbody>
    <?php if (!$data['rincian']): ?><tr><td colspan="8">Tidak ada transaksi pada periode ini.</td></tr><?php endif; ?>
    <?php foreach ($data['rincian'] as $r): ?>
      <tr>
        <td><?= e($r['kode_transaksi']) ?></td>
        <td><?= e($r['kode_pesanan']) ?></td>
        <td><?= e($r['nomor_meja']) ?></td>
        <td><?= e($r['nama_kasir']) ?></td>
        <td><?= e(ucfirst($r['metode_pembayaran'])) ?></td>
        <td class="text-right"><?= rupiah($r['total_bayar']) ?></td>
        <td><?= e(ucfirst($r['status'])) ?></td>
        <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
  <tfoot>
    <tr><td colspan="5" class="text-right"><strong>TOTAL</strong></td><td class="text-right"><strong><?= rupiah($data['ringkasan']['total_penjualan']) ?></strong></td><td colspan="2"></td></tr>
  </tfoot>
</table>

<p style="font-size:.75rem;color:#888">Dokumen ini adalah laporan penjualan internal Warkop 3.4, bukan bukti pembayaran/nota transaksi.</p>

</body>
</html>
