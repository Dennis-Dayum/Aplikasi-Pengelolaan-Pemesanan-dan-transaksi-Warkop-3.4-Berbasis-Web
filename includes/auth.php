<?php
/**
 * Middleware Autentikasi & RBAC (Role-Based Access Control)
 * Sesuai FR-1, FR-2, FR-3, NFR-06 (RBAC), NFR-07 (auto logout 30 menit tidak aktif)
 *
 * Cara pakai: include file ini di paling atas halaman, lalu panggil:
 *   requireLogin();            -> wajib login (peran apapun)
 *   requireRole('admin');      -> wajib login DAN berperan admin
 *   requireRole(['admin','kasir']); -> wajib login dan salah satu peran berikut
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * NFR-07: Session login harus otomatis logout setelah 30 menit tidak aktif.
 */
function checkSessionTimeout(): void
{
    if (isset($_SESSION['user_id'])) {
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            session_start();
            setFlash('warning', 'Sesi Anda telah berakhir karena 30 menit tidak ada aktivitas. Silakan login kembali.');
            redirect('/auth/login.php');
        }
        $_SESSION['last_activity'] = time();
    }
}
checkSessionTimeout();

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'      => $_SESSION['user_id'],
        'username'=> $_SESSION['username'],
        'nama'    => $_SESSION['nama_lengkap'],
        'role'    => $_SESSION['role'],
    ];
}

/**
 * FR-3: Sistem harus mengarahkan pengguna ke dashboard sesuai role setelah login berhasil.
 */
function redirectToDashboard(): void
{
    $role = $_SESSION['role'] ?? null;
    if ($role === 'admin') {
        redirect('/admin/dashboard.php');
    } elseif ($role === 'kasir') {
        redirect('/kasir/dashboard.php');
    } else {
        redirect('/pelanggan/index.php');
    }
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        setFlash('danger', 'Silakan login terlebih dahulu.');
        redirect('/auth/login.php');
    }
}

/**
 * NFR-06: role-based access control (RBAC) untuk membatasi akses (Admin, Kasir).
 * $roles bisa berupa string tunggal atau array.
 */
function requireRole($roles): void
{
    requireLogin();
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($_SESSION['role'], $roles, true)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:40px;text-align:center">
                <h2>403 - Akses Ditolak</h2>
                <p>Anda tidak memiliki hak akses ke halaman ini.</p>
                <a href="' . BASE_URL . '/index.php">Kembali ke Beranda</a>
             </div>');
    }
}
