<?php
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    logActivity($_SESSION['user_id'], 'Logout');
}
session_unset();
session_destroy();
session_start();
setFlash('success', 'Anda berhasil logout.');
redirect('/auth/login.php');
