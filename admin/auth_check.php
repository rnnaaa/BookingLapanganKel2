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

// 5. CEK TIMEOUT: Auto logout jika tidak aktif selama 30 menit (1800 detik)
$timeout_duration = 1800; 

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Hapus semua data session
    session_unset();
    session_destroy();
    
    // Mulai session baru sebentar hanya untuk mengirim pesan error (feedback)
    session_start();
    $_SESSION['error'] = "Sesi Anda telah habis. Silakan login kembali.";
    
    header('Location: ../Auth/login.php?timeout=1');
    exit;
}

// Update waktu aktivitas terakhir user
$_SESSION['last_activity'] = time();
?>