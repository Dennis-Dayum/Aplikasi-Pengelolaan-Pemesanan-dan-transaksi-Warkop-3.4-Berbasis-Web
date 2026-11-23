# Warkop 3.4 — Aplikasi Pengelolaan Pemesanan dan Transaksi Berbasis Web

Warkop 3.4 merupakan aplikasi berbasis web yang dikembangkan untuk membantu proses pengelolaan pemesanan, transaksi, menu, stok, dan laporan penjualan pada UMKM Warkop.

Project ini dibangun berdasarkan dokumen SRS (Software Requirements Specification) dengan judul **"Perancangan Aplikasi Pengelolaan Pemesanan dan Transaksi pada Warkop 3.4 Berbasis Web".**

## 1. Teknologi yang Digunakan

- **Backend:** PHP Native (tanpa framework).
- **Frontend:** HTML dan CSS Custom.
- **Database:** MySQL.
- **Web Server:** Apache (XAMPP).
- **Browser:** Google Chrome, Mozilla Firefox, atau Microsoft Edge.

## 2. Fitur Aplikasi

### Admin / Owner
- Dashboard pengelolaan aplikasi.
- Manajemen kategori menu.
- Manajemen menu makanan dan minuman.
- Manajemen stok bahan baku.
- Manajemen pengguna.
- Monitoring seluruh pesanan.
- Pengelolaan pembatalan transaksi.
- Laporan penjualan.
- Export laporan ke Excel.
- Cetak atau simpan laporan dalam format PDF.

### Kasir
- Dashboard pesanan masuk.
- Melihat detail pesanan pelanggan.
- Mengelola ketersediaan menu.
- Memproses pembayaran tunai dan non-tunai.
- Mengelola status pesanan.
- Melihat riwayat transaksi.
- Melihat laporan penjualan harian.

### Pelanggan
- Melihat daftar menu berdasarkan kategori.
- Menambahkan menu ke keranjang.
- Mengubah dan mengelola keranjang.
- Melakukan pemesanan secara online.
- Memasukkan nomor meja.
- Melihat status pesanan.

## 3. Laporan Penjualan

Aplikasi menyediakan fitur laporan penjualan untuk membantu Admin/Owner memantau aktivitas transaksi.

Fitur laporan meliputi:

1. Filter laporan berdasarkan rentang tanggal.
2. Filter berdasarkan kasir.
3. Filter berdasarkan metode pembayaran.
4. Filter berdasarkan status transaksi.
5. Ringkasan total penjualan.
6. Rekap menu yang terjual.
7. Rekap metode pembayaran.
8. Rincian transaksi.
9. Export laporan ke Excel.
10. Cetak atau simpan laporan ke PDF melalui browser.

**Catatan:** Aplikasi ini tidak menyediakan fitur cetak nota atau struk transaksi pelanggan. Fitur yang tersedia adalah laporan penjualan.

## 4. Struktur Folder Project

```text
warkop34/
├── admin/             # Halaman Admin / Owner
├── kasir/             # Halaman Kasir
├── pelanggan/         # Halaman Pemesanan Pelanggan
├── auth/              # Autentikasi Pengguna
├── includes/          # File pendukung aplikasi
├── config/            # Konfigurasi aplikasi
├── assets/
│   ├── css/           # File CSS
│   └── js/            # File JavaScript
├── uploads/
│   └── menu/          # Foto menu
├── database/          # Database aplikasi
└── index.php          # Halaman utama aplikasi
```

## 5. Persyaratan Sistem

Sebelum menjalankan aplikasi, pastikan perangkat sudah memiliki:

- XAMPP dengan PHP 8.x.
- MySQL.
- Browser modern.
- Text editor atau IDE (Visual Studio Code direkomendasikan).

## 6. Cara Instalasi

1. Download atau clone repository project.
2. Pindahkan folder project ke direktori `htdocs` XAMPP.
3. Jalankan Apache dan MySQL melalui XAMPP Control Panel.
4. Buka phpMyAdmin melalui browser.
5. Buat database sesuai konfigurasi aplikasi.
6. Import file database yang tersedia pada folder `database`.
7. Sesuaikan konfigurasi koneksi database.
8. Buka aplikasi melalui browser:

```text
http://localhost/warkop34/
```

9. Lakukan proses login menggunakan akun yang telah disiapkan oleh administrator.

## 7. Keamanan Aplikasi

Aplikasi memperhatikan beberapa aspek keamanan, antara lain:

- Autentikasi pengguna.
- Pembagian hak akses berdasarkan role.
- Pengelolaan sesi pengguna.
- Password menggunakan mekanisme hashing.
- Pencegahan SQL Injection melalui prepared statements.
- Perlindungan form menggunakan CSRF token.
- Validasi file upload.
- Pembatasan akses berdasarkan hak pengguna.
- Pengelolaan pembatalan transaksi dengan pencatatan alasan.

## 8. Tujuan Pengembangan

Aplikasi Warkop 3.4 dikembangkan untuk:

- Mempermudah proses pemesanan pelanggan.
- Membantu kasir mengelola transaksi.
- Mempermudah Admin/Owner mengelola menu dan stok.
- Meningkatkan efisiensi operasional warkop.
- Mempermudah pemantauan laporan penjualan.
- Mengurangi kesalahan dalam pencatatan transaksi.
  
## 9. Pengembang

Project ini dikembangkan secara berkelompok sebagai bagian dari kegiatan perancangan dan pengembangan aplikasi berbasis web.

**Nama Project:** Warkop 3.4

**Jenis Aplikasi:** Aplikasi Pengelolaan Pemesanan dan Transaksi Berbasis Web.

---

© 2026 Warkop 3.4 — Project Pengembangan Aplikasi Berbasis Web.
