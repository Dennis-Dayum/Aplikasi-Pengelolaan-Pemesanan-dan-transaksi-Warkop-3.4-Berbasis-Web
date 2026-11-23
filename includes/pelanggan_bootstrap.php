<?php
/**
 * Bootstrap untuk halaman Pelanggan (self-order). Pelanggan TIDAK perlu login
 * (sesuai SRS: 2.3 - Pelanggan hanya melihat menu, memesan, dan melihat status).
 * Keranjang belanja disimpan di session PHP per-pengunjung.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['keranjang']) || !is_array($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = []; // format: [menu_id => jumlah]
}

function jumlahItemKeranjang(): int
{
    return array_sum($_SESSION['keranjang']);
}
