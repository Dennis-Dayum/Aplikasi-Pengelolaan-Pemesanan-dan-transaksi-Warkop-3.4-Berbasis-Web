# Warkop 3.4 — Aplikasi Pengelolaan Pemesanan & Transaksi Berbasis Web

Dibangun sesuai SRS (PERANCANGAN APLIKASI PENGELOLAAN PEMESANAN DAN
TRANSAKSI PADA "WARKOP 3.4" BERBASIS WEB).

- Backend: PHP native (tanpa framework, arsitektur mendekati MVC sesuai constraint SRS 2.5)
- Frontend: HTML, CSS (custom, tanpa framework CSS)
- Database: MySQL (XAMPP)
- Tidak ada fitur cetak nota/struk transaksi hanya Laporan Penjualan.

---

# 1. Kebutuhan Sistem

- XAMPP (Apache + MySQL + PHP 8.x) — [https://www.apachefriends.org](https://www.apachefriends.org)
- Browser modern (Chrome/Firefox/Edge)

---

# 2. Akun Login Default (Admin/Owner & Kasir)

Akun berikut **sudah langsung aktif** begitu `database/warkop34.sql`
diimport di phpMyAdmin — tidak perlu langkah tambahan apa pun.

| Role          | Username | Password   |
|---------------|----------|------------|
| Admin / Owner | `admin`  | `admin123` |
| Kasir         | `kasir1` | `kasir123` |

> Penting: ganti kedua password ini setelah login pertama
> kali melalui menu Admin → Manajemen Pengguna → Reset Password, terutama
> jika aplikasi akan dipakai di luar lingkungan demo/skripsi.

Ingin menambah kasir lain? Login sebagai admin → menu Manajemen Pengguna
→ isi form "Tambah Akun".

## Jika akun default hilang/terhapus
Buka `http://localhost/warkop34/install/buat_akun_awal.php` satu kali di
browser — skrip ini akan membuat ulang akun `admin` dan `kasir1` di atas
secara otomatis (password di-hash langsung oleh PHP di server Anda). Setelah
dipakai, sebaiknya folder `install/` dihapus dari server produksi.

---

## 3. Struktur Folder

```
warkop34/
├── admin/              -> Semua halaman Admin/Owner
│   ├── dashboard.php
│   ├── kategori.php            (Manajemen Kategori Menu)
│   ├── menu.php, menu_form.php, menu_hapus.php   (FR-4, FR-5, FR-6)
│   ├── stok.php                (Manajemen Stok Bahan Baku)
│   ├── pengguna.php            (Manajemen akun Admin/Kasir)
│   ├── pesanan.php             (Monitor semua pesanan)
│   ├── void_transaksi.php      (NFR-04, NFR-19 - pembatalan transaksi wajib alasan)
│   ├── laporan.php             (★ Laporan Penjualan - filter & ringkasan)
│   ├── laporan_export_excel.php (★ Export Excel/.xls)
│   └── laporan_cetak.php       (★ Cetak / Simpan sebagai PDF)
├── kasir/               -> Semua halaman Kasir
│   ├── dashboard.php           (FR-11 - daftar pesanan masuk)
│   ├── detail_pesanan.php      (FR-13 - proses pembayaran tunai/non-tunai)
│   ├── stok_menu.php           (FR-12 - toggle status tersedia/habis)
│   ├── riwayat_transaksi.php   (FR-14 - riwayat transaksi milik kasir)
│   └── laporan_harian.php      (Ringkasan laporan harian untuk kasir)
├── pelanggan/            -> Halaman Pelanggan (self-order, tanpa login)
│   ├── index.php               (FR-7 - lihat menu per kategori)
│   ├── tambah_keranjang.php / keranjang.php / update_keranjang.php  (FR-8)
│   ├── checkout.php            (FR-9 - submit pesanan + nomor meja)
│   └── status_pesanan.php      (FR-10 - tracking status pesanan)
├── auth/                 -> login.php, logout.php (FR-1, FR-2, FR-3)
├── install/               -> Skrip cadangan pembuat akun awal
├── includes/               -> auth.php (RBAC & session timeout), functions.php, layout, dll.
├── config/database.php      -> Konfigurasi koneksi MySQL
├── assets/css/style.css, assets/js/script.js
├── uploads/menu/            -> Lokasi penyimpanan foto menu (maks 2MB)
├── database/warkop34.sql    -> Skema database + data awal + akun login
└── index.php                 -> Router awal (redirect sesuai status login)
```

---

## 4. Fitur & Pemetaan ke SRS

| Fitur                              | Requirement SRS         | Lokasi File                                  |
|-------------------------------------|--------------------------|-----------------------------------------------|
| Login & RBAC (Admin/Kasir)          | FR-1, FR-2, FR-3, NFR-06 | `auth/login.php`, `includes/auth.php`         |
| Auto logout 30 menit tidak aktif    | NFR-07                   | `includes/auth.php`                           |
| Manajemen Menu                      | FR-4, FR-5, FR-6         | `admin/menu*.php`                             |
| Manajemen Stok Bahan Baku           | Fitur 4.5                | `admin/stok.php`                              |
| Pemesanan Pelanggan                 | FR-7, FR-8, FR-9         | `pelanggan/*.php`                             |
| Status Pesanan                      | FR-10                    | `pelanggan/status_pesanan.php`                |
| Pesanan Masuk & Ketersediaan Menu   | FR-11, FR-12             | `kasir/dashboard.php`, `kasir/stok_menu.php`  |
| Pembayaran Tunai/Non-tunai          | FR-13                    | `kasir/detail_pesanan.php`                    |
| Riwayat Transaksi                   | FR-14                    | `kasir/riwayat_transaksi.php`                 |
| Laporan Penjualan (bukan nota)      | Fitur 4.6, Bab 6         | `admin/laporan.php` + export Excel/PDF        |
| Transaksi tidak dapat dihapus, hanya di-void dengan alasan | NFR-04, NFR-19 | `admin/void_transaksi.php`      |
| Upload foto menu maks 2MB           | Bab 2.5, Bab 6           | `includes/functions.php` (`uploadFotoMenu`)   |
| Password ter-hash (bcrypt)          | NFR-05                   | `password_hash()` / `password_verify()`       |

---

## 5. Catatan Laporan Penjualan (Bukan Nota)
aplikasi ini tidak menyediakan cetak nota/struk transaksi
untuk pelanggan. Yang tersedia hanyalah:

1. Laporan Penjualan (Admin) — `admin/laporan.php`: filter berdasarkan
   rentang tanggal, kasir, metode pembayaran, dan status transaksi; menampilkan
   ringkasan total penjualan, rekap menu terjual, rekap per metode pembayaran,
   dan rincian transaksi.
2. Export Excel — tombol "Export Excel" menghasilkan file `.xls` yang bisa
   dibuka langsung di Microsoft Excel / LibreOffice / Google Sheets, tanpa
   perlu instalasi library tambahan.
3. Cetak / Simpan PDF — tombol "Cetak / Simpan PDF" membuka halaman laporan
   versi cetak; gunakan dialog **Print → Save as PDF** pada browser untuk
   mendapatkan file PDF laporan (pendekatan ini dipilih agar aplikasi bisa
   langsung berjalan di XAMPP tanpa instalasi library PDF via Composer).
   Jika ke depannya Anda ingin PDF asli dari server (misalnya dengan
   TCPDF/FPDF/Dompdf), tinggal install library tersebut via Composer dan
   ganti isi `admin/laporan_cetak.php`.
4. Laporan Harian (Kasir) — `kasir/laporan_harian.php`: ringkasan
   sederhana untuk kasir memantau penjualan hari berjalan.

---

## 6. Alur Penggunaan Singkat

1. Pelanggan membuka `pelanggan/index.php` → pilih menu → checkout dengan
   nomor meja → mendapat kode pesanan → bisa cek status kapan saja.
2. Kasir login → melihat pesanan masuk di dashboard → buka detail →
   proses pembayaran (tunai/non-tunai) → update status disajikan/selesai.
3. Admin/Owner login → kelola menu, kategori, stok bahan baku, pengguna →
   memantau semua pesanan/transaksi → membuka Laporan Penjualan untuk
   melihat performa dan mengekspornya ke Excel/PDF → jika ada kesalahan input
   transaksi, gunakan fitur Void dengan alasan wajib diisi.

---

## 8. Keamanan yang Diterapkan

- Password disimpan dengan `password_hash()` (bcrypt) — NFR-05
- Role-Based Access Control di setiap halaman Admin/Kasir — NFR-06
- Auto logout setelah 30 menit tidak aktif — NFR-07
- CSRF token di setiap form POST
- Prepared statements (PDO) di seluruh query — mencegah SQL Injection
- Validasi & pembatasan ukuran/format file upload foto menu (maks 2MB)
- Transaksi lunas tidak bisa diedit, hanya bisa di-void oleh admin dengan
  alasan wajib dicatat — NFR-04, NFR-19
