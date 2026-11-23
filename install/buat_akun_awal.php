<?php
/**
 * SKRIP CADANGAN (opsional) — akun admin & kasir default SUDAH otomatis dibuat
 * saat Anda mengimport database/warkop34.sql (lihat bagian "AKUN LOGIN DEFAULT"
 * di file tersebut). Jalankan skrip ini HANYA jika Anda perlu membuat ulang
 * akun tersebut (misalnya akunnya terlanjur terhapus).
 *
 * Contoh: http://localhost/warkop34/install/buat_akun_awal.php
 *
 * SETELAH DIGUNAKAN, HAPUS FOLDER install/ INI DARI SERVER (keamanan).
 */

require_once __DIR__ . '/../config/database.php';

$db = getDB();

$akunDefault = [
    ['username' => 'admin',  'password' => 'admin123', 'nama' => 'Owner Warkop 3.4', 'role' => 'admin'],
    ['username' => 'kasir1', 'password' => 'kasir123',  'nama' => 'Kasir Warkop 3.4', 'role' => 'kasir'],
];

$hasil = [];
foreach ($akunDefault as $akun) {
    $cek = $db->prepare('SELECT id FROM users WHERE username = ?');
    $cek->execute([$akun['username']]);
    if ($cek->fetch()) {
        $hasil[] = "Akun '{$akun['username']}' sudah ada, dilewati.";
        continue;
    }
    $hash = password_hash($akun['password'], PASSWORD_BCRYPT);
    $stmt = $db->prepare('INSERT INTO users (username, password, nama_lengkap, role) VALUES (?,?,?,?)');
    $stmt->execute([$akun['username'], $hash, $akun['nama'], $akun['role']]);
    $hasil[] = "Akun '{$akun['username']}' ({$akun['role']}) berhasil dibuat dengan password default '{$akun['password']}'.";
}
?>
<!DOCTYPE html>
<html lang="id">
<head><meta charset="UTF-8"><title>Instalasi Akun Awal</title>
<style>body{font-family:sans-serif;max-width:640px;margin:60px auto;padding:0 20px;line-height:1.7}
li{margin-bottom:6px} .warn{background:#fff6e0;border:1px solid #f0dca0;padding:14px;border-radius:8px;margin-top:20px}</style>
</head>
<body>
<h2>☕ Instalasi Akun Awal - Warkop 3.4</h2>
<ul>
<?php foreach ($hasil as $h): ?>
  <li><?= htmlspecialchars($h) ?></li>
<?php endforeach; ?>
</ul>
<div class="warn">
  <strong>PENTING:</strong> Segera hapus folder <code>install/</code> ini dari server,
  lalu login dan ganti password default melalui menu <em>Admin &gt; Manajemen Pengguna</em>.
</div>
<p><a href="../auth/login.php">&larr; Ke halaman Login</a></p>
</body>
</html>
