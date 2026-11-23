<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/laporan_query.php';
requireRole('admin');
$db = getDB();
$data = ambilDataLaporan($db);

/**
 * Export ke Excel tanpa dependensi library eksternal (agar langsung jalan di XAMPP
 * tanpa perlu composer install). Menghasilkan file HTML-table dengan ekstensi .xls,
 * yang dibuka secara native oleh Microsoft Excel / LibreOffice Calc / Google Sheets.
 * (Jika Anda ingin file .xlsx murni, tinggal install PhpSpreadsheet via composer
 * dan ganti bagian output di bawah ini.)
 */

$filename = 'Laporan_Penjualan_Warkop34_' . $data['tgl_mulai'] . '_sd_' . $data['tgl_akhir'] . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
echo "\xEF\xBB\xBF"; // BOM agar karakter UTF-8 (Rp, dsb) tampil benar di Excel
?>
<html>
<head><meta charset="UTF-8"></head>
<body>
<table border="1">
  <tr><td colspan="6" style="font-size:16px;font-weight:bold">LAPORAN PENJUALAN - WARKOP 3.4</td></tr>
  <tr><td colspan="6">Periode: <?= e($data['tgl_mulai']) ?> s/d <?= e($data['tgl_akhir']) ?></td></tr>
  <tr><td colspan="6">Dicetak pada: <?= date('d/m/Y H:i') ?></td></tr>
  <tr><td colspan="6"></td></tr>
  <tr><td><b>Jumlah Transaksi</b></td><td><?= (int)$data['ringkasan']['jumlah_transaksi'] ?></td></tr>
  <tr><td><b>Total Penjualan</b></td><td><?= (float)$data['ringkasan']['total_penjualan'] ?></td></tr>
  <tr><td colspan="6"></td></tr>

  <tr><td colspan="6" style="font-weight:bold">REKAP MENU TERJUAL</td></tr>
  <tr style="font-weight:bold;background:#eee">
    <td>No</td><td>Nama Menu</td><td>Qty Terjual</td><td>Total Penjualan (Rp)</td>
  </tr>
  <?php foreach ($data['rekap_menu'] as $i => $rm): ?>
  <tr>
    <td><?= $i+1 ?></td>
    <td><?= e($rm['nama_menu']) ?></td>
    <td><?= (int)$rm['qty'] ?></td>
    <td><?= (float)$rm['total'] ?></td>
  </tr>
  <?php endforeach; ?>
  <tr><td colspan="6"></td></tr>

  <tr><td colspan="9" style="font-weight:bold">RINCIAN TRANSAKSI</td></tr>
  <tr style="font-weight:bold;background:#eee">
    <td>Kode Transaksi</td><td>Kode Pesanan</td><td>Meja</td><td>Tipe</td>
    <td>Kasir</td><td>Metode Bayar</td><td>Total (Rp)</td><td>Status</td><td>Waktu</td>
  </tr>
  <?php foreach ($data['rincian'] as $r): ?>
  <tr>
    <td><?= e($r['kode_transaksi']) ?></td>
    <td><?= e($r['kode_pesanan']) ?></td>
    <td><?= e($r['nomor_meja']) ?></td>
    <td><?= e(ucfirst($r['tipe_pesanan'])) ?></td>
    <td><?= e($r['nama_kasir']) ?></td>
    <td><?= e(ucfirst($r['metode_pembayaran'])) ?></td>
    <td><?= (float)$r['total_bayar'] ?></td>
    <td><?= e(ucfirst($r['status'])) ?></td>
    <td><?= date('d/m/Y H:i', strtotime($r['created_at'])) ?></td>
  </tr>
  <?php endforeach; ?>
</table>
</body>
</html>
