<?php
session_start();
require '../config/database.php';

// Atur header agar bisa dipanggil via Fetch/Beacon
header('Content-Type: application/json');

// Cek apakah ada booking yang sedang 'hold'
if (isset($_SESSION['temp_booking_id'])) {
    $booking_id = $_SESSION['temp_booking_id'];
    
    mysqli_begin_transaction($conn);
    try {
        // 1. Kembalikan slot jadwal menjadi 'tersedia'
        // Hapus referensi booking di jadwal_detail
        $sql_release = "UPDATE jadwal_detail SET status = 'tersedia', id_booking = NULL 
                        WHERE id_booking = ?";
        $stmt = $conn->prepare($sql_release);
        $stmt->bind_param("i", $booking_id);
        $stmt->execute();

        // 2. Ubah status booking menjadi 'dibatalkan'
        $sql_cancel = "UPDATE booking SET status = 'dibatalkan' WHERE id_booking = ?";
        $stmt2 = $conn->prepare($sql_cancel);
        $stmt2->bind_param("i", $booking_id);
        $stmt2->execute();

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        // Error silent (karena user sudah keluar halaman)
    }
}

// 3. Bersihkan Sesi Terkait
unset($_SESSION['temp_booking_id']);
unset($_SESSION['booking_expired_at']);
unset($_SESSION['keranjang']);
unset($_SESSION['produk_tambahan']);

echo json_encode(['status' => 'ok', 'message' => 'Booking dibatalkan']);
?>