-- =========================================================
-- DATABASE: warkop34
-- Aplikasi Pengelolaan Pemesanan & Transaksi Warkop 3.4
-- Sesuai SRS v1.0 (FR-1 s/d FR-14, NFR-01 s/d NFR-19)
-- Jalankan file ini di phpMyAdmin (XAMPP) atau via:
--   mysql -u root -p < warkop34.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS warkop34 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE warkop34;

-- ---------------------------------------------------------
-- Tabel users
-- Menyimpan akun Admin/Owner dan Kasir (FR-1, FR-2, FR-3, NFR-05, NFR-06)
-- Pelanggan TIDAK perlu akun (self-order via nomor meja)
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,           -- disimpan dengan password_hash() (bcrypt)
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel kategori_menu
-- ---------------------------------------------------------
CREATE TABLE kategori_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(50) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel menu
-- FR-4, FR-5, FR-6, NFR-16 (hanya menu berstatus tersedia yang bisa dipesan)
-- ---------------------------------------------------------
CREATE TABLE menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    nama_menu VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    foto VARCHAR(255) DEFAULT NULL,           -- max 2MB, divalidasi di aplikasi
    status_ketersediaan ENUM('tersedia','habis') NOT NULL DEFAULT 'tersedia',
    is_active TINYINT(1) NOT NULL DEFAULT 1,  -- 0 = dihapus/nonaktif oleh admin (FR-6)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_menu_kategori FOREIGN KEY (kategori_id) REFERENCES kategori_menu(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel bahan_baku (Manajemen Stok Bahan Baku - fitur 4.5)
-- ---------------------------------------------------------
CREATE TABLE bahan_baku (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_bahan VARCHAR(100) NOT NULL,
    satuan VARCHAR(20) NOT NULL DEFAULT 'pcs',
    stok DECIMAL(12,2) NOT NULL DEFAULT 0,
    stok_minimum DECIMAL(12,2) NOT NULL DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Riwayat pergerakan stok bahan baku (masuk/keluar), untuk jejak audit
CREATE TABLE stok_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bahan_baku_id INT NOT NULL,
    jenis ENUM('masuk','keluar','penyesuaian') NOT NULL,
    jumlah DECIMAL(12,2) NOT NULL,
    keterangan VARCHAR(255) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_stoklog_bahan FOREIGN KEY (bahan_baku_id) REFERENCES bahan_baku(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_stoklog_user FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel pesanan (FR-7, FR-8, FR-9, FR-10)
-- status: diproses -> disajikan -> selesai (atau dibatalkan)
-- ---------------------------------------------------------
CREATE TABLE pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pesanan VARCHAR(20) NOT NULL UNIQUE,   -- dipakai pelanggan untuk cek status
    nomor_meja VARCHAR(10) NOT NULL,
    tipe_pesanan ENUM('dine-in','take-away') NOT NULL DEFAULT 'dine-in',
    status ENUM('diproses','disajikan','selesai','dibatalkan') NOT NULL DEFAULT 'diproses',
    total_harga DECIMAL(12,2) NOT NULL DEFAULT 0,
    catatan VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel detail_pesanan
-- ---------------------------------------------------------
CREATE TABLE detail_pesanan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pesanan_id INT NOT NULL,
    menu_id INT NOT NULL,
    nama_menu_snapshot VARCHAR(100) NOT NULL,   -- snapshot nama menu saat dipesan
    harga_satuan DECIMAL(12,2) NOT NULL,
    jumlah INT NOT NULL DEFAULT 1,
    subtotal DECIMAL(12,2) NOT NULL,
    CONSTRAINT fk_detail_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_detail_menu FOREIGN KEY (menu_id) REFERENCES menu(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel transaksi (FR-11, FR-12, FR-13, FR-14, NFR-04, NFR-17, NFR-19)
-- Transaksi yang sudah lunas TIDAK BOLEH diubah, hanya bisa di-VOID oleh admin
-- dengan alasan yang wajib dicatat (NFR-19). Tidak ada cetak nota/struk -
-- riwayat transaksi hanya digunakan sebagai sumber data Laporan Penjualan.
-- ---------------------------------------------------------
CREATE TABLE transaksi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_transaksi VARCHAR(20) NOT NULL UNIQUE,
    pesanan_id INT NOT NULL,
    kasir_id INT NOT NULL,
    metode_pembayaran ENUM('tunai','non-tunai') NOT NULL,
    total_bayar DECIMAL(12,2) NOT NULL,
    jumlah_dibayar DECIMAL(12,2) NOT NULL DEFAULT 0,
    kembalian DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('lunas','void') NOT NULL DEFAULT 'lunas',
    alasan_void VARCHAR(255) DEFAULT NULL,
    void_by INT DEFAULT NULL,
    void_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_transaksi_pesanan FOREIGN KEY (pesanan_id) REFERENCES pesanan(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_kasir FOREIGN KEY (kasir_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_transaksi_voidby FOREIGN KEY (void_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Tabel activity_log (opsional, jejak audit sederhana)
-- ---------------------------------------------------------
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    aktivitas VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- DATA AWAL (SEED)
-- =========================================================

-- =========================================================
-- AKUN LOGIN DEFAULT (siap pakai setelah import database ini)
-- =========================================================
-- Role ADMIN/OWNER
--   Username : admin
--   Password : admin123
--
-- Role KASIR
--   Username : kasir1
--   Password : kasir123
--
-- Password di atas sudah di-hash dengan algoritma bcrypt (kompatibel penuh
-- dengan password_hash()/password_verify() PHP) sehingga AMAN dan bisa
-- langsung dipakai untuk login setelah file SQL ini diimport.
--
-- PENTING (keamanan): setelah login pertama kali, segera ganti password
-- melalui menu Admin > Manajemen Pengguna > Reset Password.
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin',  '$2b$10$6ijLnjq1BcCx1ngQiGvEQ.0yCBL6rDXttyv4GGTPXmgFV5KqBTxPi', 'Owner Warkop 3.4', 'admin'),
('kasir1', '$2b$10$49axyxdrAmzLQXb9WuWLTuQ57AZ1ADA5xRoIOjpjwfVPmPRoFwKFC', 'Kasir Warkop 3.4', 'kasir');

-- Catatan alternatif: folder install/buat_akun_awal.php tetap tersedia sebagai
-- cara CADANGAN untuk membuat ulang akun admin/kasir secara otomatis lewat
-- browser (misalnya jika Anda menghapus/lupa akun di atas), karena skrip itu
-- meng-hash password langsung memakai PHP di server Anda.

INSERT INTO kategori_menu (nama_kategori) VALUES
('Kopi'), ('Non-Kopi'), ('Makanan Ringan'), ('Makanan Berat');

INSERT INTO menu (kategori_id, nama_menu, deskripsi, harga, status_ketersediaan) VALUES
(1, 'Kopi Hitam', 'Kopi robusta tubruk khas warkop', 8000, 'tersedia'),
(1, 'Kopi Susu Gula Aren', 'Kopi susu dengan gula aren asli', 15000, 'tersedia'),
(2, 'Es Teh Manis', 'Teh manis segar dengan es batu', 6000, 'tersedia'),
(2, 'Coklat Panas', 'Minuman coklat hangat', 12000, 'tersedia'),
(3, 'Pisang Goreng', '5 pcs pisang goreng crispy', 10000, 'tersedia'),
(3, 'Mie Instan Goreng', 'Mie instan digoreng dengan telur', 12000, 'tersedia'),
(4, 'Nasi Goreng Warkop', 'Nasi goreng dengan telur dan kerupuk', 18000, 'tersedia'),
(4, 'Indomie Rebus Telur', 'Indomie rebus dengan tambahan telur', 10000, 'tersedia');

INSERT INTO bahan_baku (nama_bahan, satuan, stok, stok_minimum) VALUES
('Kopi Bubuk', 'gram', 5000, 500),
('Gula Aren', 'gram', 3000, 300),
('Teh Celup', 'pcs', 200, 20),
('Beras', 'kg', 25, 5),
('Telur', 'butir', 100, 10),
('Mie Instan', 'pcs', 150, 15);
