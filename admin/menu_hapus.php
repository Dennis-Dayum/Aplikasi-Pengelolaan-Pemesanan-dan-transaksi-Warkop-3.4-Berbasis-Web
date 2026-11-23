<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

$id = $_GET['id'] ?? null;
if ($id) {
    // FR-6: Admin dapat menghapus atau menonaktifkan menu.
    // Menu tidak dihapus permanen (soft-delete) agar riwayat pesanan lama tetap valid.
    $stmt = $db->prepare('UPDATE menu SET is_active=0 WHERE id=?');
    $stmt->execute([$id]);
    logActivity($_SESSION['user_id'], "Menonaktifkan (hapus) menu #$id");
    setFlash('success', 'Menu berhasil dihapus/dinonaktifkan.');
}
redirect('/admin/menu.php');
