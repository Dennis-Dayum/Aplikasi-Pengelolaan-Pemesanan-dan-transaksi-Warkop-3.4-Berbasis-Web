<?php
require_once __DIR__ . '/../includes/pelanggan_bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $menuId = $_POST['menu_id'] ?? null;
    $aksi = $_POST['aksi'] ?? '';

    if ($menuId && isset($_SESSION['keranjang'][$menuId])) {
        if ($aksi === 'tambah') {
            $_SESSION['keranjang'][$menuId]++;
        } elseif ($aksi === 'kurang') {
            $_SESSION['keranjang'][$menuId]--;
            if ($_SESSION['keranjang'][$menuId] <= 0) unset($_SESSION['keranjang'][$menuId]);
        } elseif ($aksi === 'hapus') {
            unset($_SESSION['keranjang'][$menuId]);
        }
    }
}
redirect('/pelanggan/keranjang.php');
