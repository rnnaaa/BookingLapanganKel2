<?php
// include_user/release_slots.php

// Pastikan koneksi database sudah ada dari file induk (booking.php)
if (isset($conn)) {
    
    // 1. Cari Booking yang statusnya 'hold' DAN waktu expired-nya sudah lewat
    // Kita ambil ID-nya dulu untuk melepas slot jadwal
    $sql_find = "SELECT id_booking FROM booking WHERE status = 'hold' AND expired_at <= NOW()";
    $result_find = mysqli_query($conn, $sql_find);
    
    $ids_to_delete = [];
    while ($row = mysqli_fetch_assoc($result_find)) {
        $ids_to_delete[] = $row['id_booking'];
    }

    if (!empty($ids_to_delete)) {
        // Konversi array ID menjadi string comma-separated untuk query (contoh: 1, 2, 5)
        $ids_string = implode(',', array_map('intval', $ids_to_delete));

        mysqli_begin_transaction($conn);
        try {
            // 2. Update jadwal_detail menjadi 'tersedia'
            $sql_update_slots = "UPDATE jadwal_detail SET status = 'tersedia', id_booking = NULL 
                                 WHERE id_booking IN ($ids_string)";
            mysqli_query($conn, $sql_update_slots);

            // 3. Hapus Permanen dari tabel booking
            $sql_delete_booking = "DELETE FROM booking WHERE id_booking IN ($ids_string)";
            mysqli_query($conn, $sql_delete_booking);

            mysqli_commit($conn);
            
            // Jika user yang sedang login adalah pemilik booking yang dihapus, bersihkan sesi mereka
            if (isset($_SESSION['temp_booking_id']) && in_array($_SESSION['temp_booking_id'], $ids_to_delete)) {
                unset($_SESSION['temp_booking_id']);
                unset($_SESSION['booking_expired_at']);
                unset($_SESSION['keranjang']);
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
        }
    }
}
?>