<?php
// pembatalan_hapus.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Pastikan ada parameter ID
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // 1. Ambil nama file gambar dulu sebelum data dihapus
    $query_cek = mysqli_query($conn, "SELECT bukti_refund FROM pembatalan_booking WHERE id = '$id'");
    $data = mysqli_fetch_assoc($query_cek);

    if ($data) {
        // 2. Hapus file fisik gambar jika ada
        if (!empty($data['bukti_refund'])) {
            $file_path = '../uploads/bukti_refund/' . $data['bukti_refund'];
            if (file_exists($file_path)) {
                unlink($file_path); // Hapus file dari folder
            }
        }

        // 3. Hapus data dari database
        $query_hapus = mysqli_query($conn, "DELETE FROM pembatalan_booking WHERE id = '$id'");

        if ($query_hapus) {
            $_SESSION['toast_success'] = "Data pengajuan refund berhasil dihapus.";
        } else {
            $_SESSION['toast_error'] = "Gagal menghapus data: " . mysqli_error($conn);
        }
    } else {
        $_SESSION['toast_error'] = "Data tidak ditemukan.";
    }
} else {
    $_SESSION['toast_error'] = "ID tidak valid.";
}

// Redirect kembali ke halaman utama
header("Location: pembatalan_booking.php");
exit;
?>