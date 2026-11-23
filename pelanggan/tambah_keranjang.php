<?php
require_once __DIR__ . '/../includes/pelanggan_bootstrap.php';
$db = getDB();

// FR-8: Pelanggan dapat menambahkan menu ke keranjang pesanan.
// NFR-16: Pelanggan hanya dapat memesan menu yang berstatus tersedia.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menuId = $_POST['menu_id'] ?? null;
    $jumlah = max(1, (int)($_POST['jumlah'] ?? 1));

    $stmt = $db->prepare("SELECT id FROM menu WHERE id=? AND is_active=1 AND status_ketersediaan='tersedia'");
    $stmt->execute([$menuId]);
    if ($stmt->fetch()) {
        $_SESSION['keranjang'][$menuId] = ($_SESSION['keranjang'][$menuId] ?? 0) + $jumlah;
        setFlash('success', 'Menu berhasil ditambahkan ke keranjang.');
    } else {
        setFlash('danger', 'Menu tidak tersedia untuk dipesan saat ini.');
    }
}

$redirectKategori = $_POST['redirect_kategori'] ?? '';
redirect('/pelanggan/index.php' . ($redirectKategori !== '' ? '?kategori=' . urlencode($redirectKategori) : ''));
