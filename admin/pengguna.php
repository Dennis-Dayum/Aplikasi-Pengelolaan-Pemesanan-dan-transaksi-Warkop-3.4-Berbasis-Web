<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
$db = getDB();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? null)) {
        setFlash('danger', 'Token keamanan tidak valid.'); redirect('/admin/pengguna.php');
    }
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $username = trim($_POST['username'] ?? '');
        $nama = trim($_POST['nama_lengkap'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'kasir';

        if ($username === '' || $nama === '' || strlen($password) < 6) {
            setFlash('danger', 'Lengkapi semua data. Password minimal 6 karakter.');
        } else {
            $cek = $db->prepare('SELECT id FROM users WHERE username=?');
            $cek->execute([$username]);
            if ($cek->fetch()) {
                setFlash('danger', 'Username sudah digunakan.');
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $db->prepare('INSERT INTO users (username, password, nama_lengkap, role) VALUES (?,?,?,?)');
                $stmt->execute([$username, $hash, $nama, $role]);
                logActivity($_SESSION['user_id'], "Menambah akun pengguna: $username ($role)");
                setFlash('success', 'Akun pengguna berhasil ditambahkan.');
            }
        }
    } elseif ($aksi === 'toggle_status' && !empty($_POST['id'])) {
        if ((int)$_POST['id'] === (int)$_SESSION['user_id']) {
            setFlash('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        } else {
            $db->prepare("UPDATE users SET status = IF(status='aktif','nonaktif','aktif') WHERE id=?")->execute([$_POST['id']]);
            setFlash('success', 'Status akun berhasil diubah.');
        }
    } elseif ($aksi === 'reset_password' && !empty($_POST['id']) && !empty($_POST['password_baru'])) {
        $hash = password_hash($_POST['password_baru'], PASSWORD_BCRYPT);
        $db->prepare('UPDATE users SET password=? WHERE id=?')->execute([$hash, $_POST['id']]);
        logActivity($_SESSION['user_id'], "Reset password untuk user #{$_POST['id']}");
        setFlash('success', 'Password berhasil direset.');
    }
    redirect('/admin/pengguna.php');
}

$users = $db->query('SELECT * FROM users ORDER BY role, nama_lengkap')->fetchAll();
$csrf = generateCsrfToken();
$pageTitle = 'Manajemen Pengguna'; $activeMenu = 'pengguna';
require __DIR__ . '/../includes/layout_admin_top.php';
?>

<div class="card">
  <h3>Tambah Akun (Admin/Kasir)</h3>
  <form method="post" action="" class="form-row" style="align-items:flex-end">
    <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
    <input type="hidden" name="aksi" value="tambah">
    <div class="form-group"><label>Username</label><input type="text" name="username" required></div>
    <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama_lengkap" required></div>
    <div class="form-group"><label>Password</label><input type="password" name="password" required minlength="6"></div>
    <div class="form-group">
      <label>Role</label>
      <select name="role">
        <option value="kasir">Kasir</option>
        <option value="admin">Admin/Owner</option>
      </select>
    </div>
    <div class="form-group"><button class="btn">+ Tambah</button></div>
  </form>
</div>

<div class="card">
  <h3>Daftar Pengguna</h3>
  <div class="table-wrap">
  <table>
    <thead><tr><th>Username</th><th>Nama</th><th>Role</th><th>Status</th><th style="width:320px">Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= e($u['username']) ?></td>
        <td><?= e($u['nama_lengkap']) ?></td>
        <td><span class="badge badge-secondary"><?= e(ucfirst($u['role'])) ?></span></td>
        <td><span class="badge <?= $u['status']==='aktif'?'badge-success':'badge-danger' ?>"><?= e(ucfirst($u['status'])) ?></span></td>
        <td style="display:flex;gap:6px;flex-wrap:wrap">
          <form method="post" action="" data-confirm="Ubah status akun '<?= e($u['username']) ?>'?">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="aksi" value="toggle_status">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <button class="btn btn-sm btn-outline" type="submit">Aktifkan/Nonaktifkan</button>
          </form>
          <form method="post" action="" style="display:flex;gap:4px" onsubmit="return confirm('Reset password akun ini?')">
            <input type="hidden" name="csrf_token" value="<?= e($csrf) ?>">
            <input type="hidden" name="aksi" value="reset_password">
            <input type="hidden" name="id" value="<?= $u['id'] ?>">
            <input type="password" name="password_baru" placeholder="Password baru" required minlength="6" style="width:130px">
            <button class="btn btn-sm btn-warning" type="submit">Reset</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/../includes/layout_admin_bottom.php'; ?>
