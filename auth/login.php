<?php
require_once __DIR__ . '/../includes/auth.php';

// Jika sudah login, langsung arahkan ke dashboard sesuai role
if (isLoggedIn()) {
    redirectToDashboard();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Sesi form tidak valid, silakan coba lagi.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $errors[] = 'Username dan password wajib diisi.';
        } else {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // FR-1: verifikasi kredensial | FR-2: tolak jika salah
            if (!$user || $user['status'] !== 'aktif' || !password_verify($password, $user['password'])) {
                $errors[] = 'Username atau password salah, atau akun tidak aktif.';
                logActivity(null, "Percobaan login gagal untuk username: $username");
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']      = $user['id'];
                $_SESSION['username']     = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role']         = $user['role'];
                $_SESSION['last_activity']= time();
                logActivity($user['id'], 'Login berhasil sebagai ' . $user['role']);
                // FR-3: arahkan ke dashboard sesuai role
                redirectToDashboard();
            }
        }
    }
}
$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Warkop 3.4</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-box">
    <h1>☕ Warkop 3.4</h1>
    <p class="sub">Login Admin / Kasir</p>

    <?php foreach ($errors as $err): ?>
      <div class="alert alert-danger"><?= e($err) ?></div>
    <?php endforeach; ?>

    <?php if ($flash = getFlash()): ?>
      <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" required autofocus value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-block">Login</button>
    </form>

    <p style="text-align:center;margin-top:18px;font-size:.85rem">
      Pelanggan tidak perlu login. <a href="<?= BASE_URL ?>/pelanggan/index.php">Lihat menu &amp; pesan di sini &rarr;</a>
    </p>
  </div>
</div>
</body>
</html>
