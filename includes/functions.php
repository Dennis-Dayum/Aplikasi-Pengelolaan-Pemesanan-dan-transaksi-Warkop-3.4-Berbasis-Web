<?php
/**
 * Kumpulan fungsi bantu (helper) yang dipakai di seluruh aplikasi.
 */

function rupiah($angka): string
{
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function kodeUnik(string $prefix): string
{
    return $prefix . date('ymd') . strtoupper(substr(uniqid(), -5));
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . $path);
    exit;
}

function e(?string $string): string
{
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function generateCsrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool
{
    return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Validasi & pindahkan file upload gambar menu (maks 2MB, jpg/jpeg/png/webp).
 * Mengembalikan nama file baru jika sukses, atau null jika tidak ada file diunggah.
 * Melempar Exception jika file tidak valid.
 */
function uploadFotoMenu(array $file, ?string $fotoLamaUntukDihapus = null): ?string
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null; // tidak ada file baru diunggah
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Terjadi kesalahan saat mengunggah file.');
    }
    if ($file['size'] > MAX_UPLOAD_SIZE) {
        throw new Exception('Ukuran gambar melebihi batas maksimal 2MB.');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        throw new Exception('Format gambar harus jpg, jpeg, png, atau webp.');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        throw new Exception('File yang diunggah bukan gambar yang valid.');
    }

    $newName = 'menu_' . uniqid() . '.' . $ext;
    $targetDir = __DIR__ . '/../uploads/menu/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    if (!move_uploaded_file($file['tmp_name'], $targetDir . $newName)) {
        throw new Exception('Gagal menyimpan file gambar ke server.');
    }

    if ($fotoLamaUntukDihapus && file_exists($targetDir . $fotoLamaUntukDihapus)) {
        @unlink($targetDir . $fotoLamaUntukDihapus);
    }

    return $newName;
}

function logActivity(?int $userId, string $aktivitas): void
{
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO activity_log (user_id, aktivitas) VALUES (?, ?)');
        $stmt->execute([$userId, $aktivitas]);
    } catch (Exception $e) {
        // silent fail - logging tidak boleh menghentikan proses utama
    }
}

function labelStatusPesanan(string $status): string
{
    $map = [
        'diproses'   => 'Diproses',
        'disajikan'  => 'Disajikan',
        'selesai'    => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
    ];
    return $map[$status] ?? ucfirst($status);
}

function badgeStatusPesanan(string $status): string
{
    $map = [
        'diproses'   => 'badge-warning',
        'disajikan'  => 'badge-info',
        'selesai'    => 'badge-success',
        'dibatalkan' => 'badge-danger',
    ];
    return $map[$status] ?? 'badge-secondary';
}
