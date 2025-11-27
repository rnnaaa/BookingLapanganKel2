<?php
// Admin/auth_check.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cek apakah user sudah login
if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
    header('Location: ../Auth/login.php');
    exit;
}

// Cek apakah role adalah admin
if ($_SESSION['role'] !== 'admin') {
    // Jika bukan admin, redirect ke halaman user
    header('Location: ../index.php');
    exit;
}

// Optional: Cek masa aktif session (30 menit)
// if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
//     session_unset();
//     session_destroy();
//     header('Location: ../Auth/login.php?timeout=1');
//     exit;
// }
$_SESSION['last_activity'] = time();
?>