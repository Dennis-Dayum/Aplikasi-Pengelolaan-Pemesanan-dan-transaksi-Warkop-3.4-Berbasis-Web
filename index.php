<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectToDashboard();
} else {
    redirect('/pelanggan/index.php');
}
