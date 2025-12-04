<?php
// users_hapus.php
session_start();
require_once __DIR__ . '/../config/database.php';

if (isset($_GET['id'])) {
    $id_user = mysqli_real_escape_string($conn, $_GET['id']);

    // Hapus data
    $sql = "DELETE FROM users WHERE id_user = '$id_user'";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['alert'] = [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Data berhasil dihapus.'
        ];
    } else {
        $_SESSION['alert'] = [
            'type' => 'error',
            'title' => 'Gagal!',
            'message' => 'Gagal menghapus: ' . mysqli_error($conn)
        ];
    }
}

// LOGIKA REDIRECT DINAMIS
// Cek apakah ada parameter 'redirect' di URL
$redirect_to = 'users.php'; // Default ke halaman user biasa

if (isset($_GET['redirect'])) {
    // Jika redirect diminta ke 'admin_data.php' (atau admin.php sesuai nama file Anda)
    if ($_GET['redirect'] == 'admin.php') {
        $redirect_to = 'admin.php'; 
    }
    // Anda bisa menambahkan kondisi lain jika ada halaman lain
}

header("Location: " . $redirect_to);
exit;
?>