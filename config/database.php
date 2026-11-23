<?php
/**
 * Konfigurasi koneksi database MySQL (XAMPP)
 * Sesuaikan DB_HOST, DB_USER, DB_PASS, DB_NAME dengan environment XAMPP Anda.
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');            // default XAMPP: password kosong
define('DB_NAME', 'warkop34');

// Base URL aplikasi (dipakai untuk redirect & link gambar). Sesuaikan jika perlu.
define('BASE_URL', '/warkop34');

// Batas maksimal ukuran unggah gambar menu (sesuai SRS: 2MB)
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB dalam bytes

// Lama sesi tidak aktif sebelum otomatis logout (NFR-07: 30 menit)
define('SESSION_TIMEOUT', 30 * 60); // dalam detik

function getDB(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('Koneksi database gagal. Pastikan MySQL XAMPP aktif dan database "warkop34" sudah diimport. Detail: ' . $e->getMessage());
        }
    }
    return $pdo;
}
