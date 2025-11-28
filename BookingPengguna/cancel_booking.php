<?php
session_start();
require '../config/database.php';

// 1. Kumpulkan semua ID Booking yang harus dibatalkan
$ids_to_cancel = [];

// Cek Array IDs (Logika Baru - Multi Lapangan)
if (isset($_SESSION['temp_booking_ids']) && is_array($_SESSION['temp_booking_ids'])) {
    $ids_to_cancel = $_SESSION['temp_booking_ids'];
} 
// Cek Single ID (Logika Lama/Fallback jika cuma 1 lapangan)
elseif (isset($_SESSION['temp_booking_id'])) {
    $ids_to_cancel[] = $_SESSION['temp_booking_id'];
}

if (!empty($ids_to_cancel)) {
    foreach ($ids_to_cancel as $booking_id) {
        $booking_id = (int)$booking_id; // Pastikan integer untuk keamanan

        // A. Kembalikan Status Slot di Jadwal Detail menjadi 'tersedia'
        // Penting: Lakukan ini SEBELUM menghapus booking agar relasinya jelas
        $sql_release = "UPDATE jadwal_detail SET status = 'tersedia', id_booking = NULL WHERE id_booking = '$booking_id'";
        mysqli_query($conn, $sql_release);

        // B. Hapus Data di Tabel Booking
        // (Tabel detail_booking biasanya terhapus otomatis jika ada ON DELETE CASCADE di database, 
        // tapi menghapus induknya adalah langkah wajib)
        $sql_delete = "DELETE FROM booking WHERE id_booking = '$booking_id'";
        mysqli_query($conn, $sql_delete);
    }
}

// 2. Bersihkan Semua Session Terkait Booking
unset($_SESSION['keranjang']);
unset($_SESSION['produk_tambahan']);
unset($_SESSION['temp_booking_id']);   // Hapus single ID
unset($_SESSION['temp_booking_ids']);  // Hapus array IDs (PENTING)
unset($_SESSION['booking_expired_at']);
unset($_SESSION['payment_temp']);

// 3. Response / Redirect
// Jika dipanggil lewat AJAX/Beacon (saat tutup tab/back button), tidak perlu redirect
if (isset($_GET['ajax'])) {
    echo json_encode(['status' => 'ok']);
    exit;
}

// Jika dipanggil lewat tombol "Batal" biasa
header("Location: booking.php");
exit;
?>