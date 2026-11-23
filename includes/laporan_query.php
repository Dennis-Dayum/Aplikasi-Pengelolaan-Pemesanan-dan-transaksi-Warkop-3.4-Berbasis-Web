<?php
/**
 * Mengambil data laporan penjualan berdasarkan filter GET yang konsisten,
 * dipakai bersama oleh admin/laporan.php, laporan_export_excel.php, dan laporan_cetak.php.
 */
function ambilDataLaporan(PDO $db): array
{
    $tglMulai = $_GET['tgl_mulai'] ?? date('Y-m-01');
    $tglAkhir = $_GET['tgl_akhir'] ?? date('Y-m-d');
    $kasirId  = $_GET['kasir_id'] ?? '';
    $metode   = $_GET['metode'] ?? '';
    $statusTrx= $_GET['status'] ?? 'lunas';

    $where = "WHERE DATE(t.created_at) BETWEEN ? AND ?";
    $params = [$tglMulai, $tglAkhir];
    if ($statusTrx !== '') { $where .= " AND t.status = ?"; $params[] = $statusTrx; }
    if ($kasirId !== '')   { $where .= " AND t.kasir_id = ?"; $params[] = $kasirId; }
    if ($metode !== '')    { $where .= " AND t.metode_pembayaran = ?"; $params[] = $metode; }

    $stmt = $db->prepare("SELECT COUNT(*) jumlah_transaksi, COALESCE(SUM(t.total_bayar),0) total_penjualan FROM transaksi t $where");
    $stmt->execute($params);
    $ringkasan = $stmt->fetch();

    $stmt = $db->prepare("
        SELECT t.kode_transaksi, t.created_at, p.kode_pesanan, p.nomor_meja, p.tipe_pesanan,
               u.nama_lengkap AS nama_kasir, t.metode_pembayaran, t.total_bayar, t.status
        FROM transaksi t
        JOIN pesanan p ON p.id = t.pesanan_id
        JOIN users u ON u.id = t.kasir_id
        $where ORDER BY t.created_at ASC");
    $stmt->execute($params);
    $rincian = $stmt->fetchAll();

    $stmt = $db->prepare("
        SELECT dp.nama_menu_snapshot AS nama_menu, SUM(dp.jumlah) qty, SUM(dp.subtotal) total
        FROM detail_pesanan dp
        JOIN transaksi t ON t.pesanan_id = dp.pesanan_id
        $where GROUP BY dp.nama_menu_snapshot ORDER BY qty DESC");
    $stmt->execute($params);
    $rekapMenu = $stmt->fetchAll();

    return [
        'tgl_mulai' => $tglMulai, 'tgl_akhir' => $tglAkhir,
        'kasir_id' => $kasirId, 'metode' => $metode, 'status' => $statusTrx,
        'ringkasan' => $ringkasan, 'rincian' => $rincian, 'rekap_menu' => $rekapMenu,
    ];
}
