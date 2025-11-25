<?php
session_start();
require '../config/database.php';

// Cek apakah ada booking yang sedang 'hold'
if (isset($_SESSION['temp_booking_id'])) {
    $booking_id = $_SESSION['temp_booking_id'];
    
    mysqli_begin_transaction($conn);
    try {
        // 1. LEPASKAN SLOT JADWAL DULU (PENTING!)
        // Kita harus memutuskan hubungan booking dengan jadwal sebelum menghapus booking
        // agar slot kembali menjadi 'tersedia' dan tidak ikut terhapus (jika ada cascade delete)
        $sql_release = "UPDATE jadwal_detail SET status = 'tersedia', id_booking = NULL 
                        WHERE id_booking = ?";
        $stmt = $conn->prepare($sql_release);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        // 2. HAPUS PERMANEN DATA BOOKING (HARD DELETE)
        // Karena di database tabel 'detail_booking' biasanya terhubung cascade,
        // maka detailnya akan ikut terhapus otomatis.
        $sql_delete = "DELETE FROM booking WHERE id_booking = ?";
        $stmt2 = $conn->prepare($sql_delete);
        $stmt2->bind_param("i", $booking_id);
        $stmt2->execute();

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
    }
}

// 3. Bersihkan Sesi Terkait
unset($_SESSION['temp_booking_id']);
unset($_SESSION['booking_expired_at']);
unset($_SESSION['keranjang']);
unset($_SESSION['produk_tambahan']);

// Respons untuk AJAX/Redirect
if ((isset($_SERVER['HTTP_CONTENT_TYPE']) && stripos($_SERVER['HTTP_CONTENT_TYPE'], 'application/json') !== false) || isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => 'Booking telah dihapus']);
} else {
    header("Location: booking.php");
    exit;
}
?>