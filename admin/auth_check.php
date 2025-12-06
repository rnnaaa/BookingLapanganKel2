<?php
// admin/auth_check.php

// 1. Pastikan session dimulai
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2. SECURITY HEADER: Mencegah Browser Caching (Penting agar tombol Back tidak berfungsi setelah logout)
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// 3. CEK LOGIN: Apakah session id_user ada?
if (!isset($_SESSION['id_user']) || !isset($_SESSION['role'])) {
    // Opsional: Simpan pesan error untuk ditampilkan di halaman login
    $_SESSION['error'] = "Anda harus login untuk mengakses halaman admin!";
    
    header('Location: ../Auth/login.php');
    exit;
}

// 4. CEK ROLE: Apakah role-nya admin?
if ($_SESSION['role'] !== 'admin') {
    // Jika user biasa mencoba masuk admin, tendang ke halaman depan
    header('Location: ../index.php'); 
    exit;
}

// 5. TIMEOUT DIHAPUS
// Kode pengecekan durasi last_activity telah dihapus agar admin tidak auto-logout.
?>