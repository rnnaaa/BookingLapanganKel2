<?php
// include_user/release_slots.php

date_default_timezone_set('Asia/Jakarta');

// Pastikan koneksi database sudah ada
if (isset($conn)) {
    
    // 1. Cari Booking yang statusnya 'hold' DAN waktu expired-nya sudah lewat
    $sql_find = "SELECT id_booking FROM booking WHERE status = 'hold' AND expired_at <= NOW()";
    $result_find = mysqli_query($conn, $sql_find);
    
    $ids_to_delete = [];
    while ($row = mysqli_fetch_assoc($result_find)) {
        $ids_to_delete[] = $row['id_booking'];
    }

    if (!empty($ids_to_delete)) {
        // Konversi array ID menjadi string comma-separated (contoh: 101, 102, 103)
        $ids_string = implode(',', array_map('intval', $ids_to_delete));

        mysqli_begin_transaction($conn);
        try {
            // 2. Lepas Slot: Update jadwal_detail menjadi 'tersedia'
            $sql_update_slots = "UPDATE jadwal_detail SET status = 'tersedia', id_booking = NULL 
                                 WHERE id_booking IN ($ids_string)";
            mysqli_query($conn, $sql_update_slots);

            // 3. Hapus Permanen Data Sampah dari tabel booking
            $sql_delete_booking = "DELETE FROM booking WHERE id_booking IN ($ids_string)";
            mysqli_query($conn, $sql_delete_booking);

            // 4. Hapus detail_booking (Opsional, biasanya kena Cascade Delete di DB)
            $sql_delete_detail = "DELETE FROM detail_booking WHERE id_booking IN ($ids_string)";
            mysqli_query($conn, $sql_delete_detail);

            mysqli_commit($conn);
            
            // 5. Bersihkan Session User (Jika user yang sedang login adalah pemilik data yang dihapus)
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $session_needs_clearing = false;

            // Cek Single ID (Legacy)
            if (isset($_SESSION['temp_booking_id']) && in_array($_SESSION['temp_booking_id'], $ids_to_delete)) {
                $session_needs_clearing = true;
            }

            // Cek Multi ID (Array) - INI PENTING UNTUK FITUR MULTI BOOKING
            if (isset($_SESSION['temp_booking_ids']) && is_array($_SESSION['temp_booking_ids'])) {
                foreach ($_SESSION['temp_booking_ids'] as $sess_id) {
                    if (in_array($sess_id, $ids_to_delete)) {
                        $session_needs_clearing = true;
                        break; 
                    }
                }
            }

            // Eksekusi Pembersihan Sesi
            if ($session_needs_clearing) {
                unset($_SESSION['temp_booking_id']);
                unset($_SESSION['temp_booking_ids']);
                unset($_SESSION['booking_expired_at']);
                unset($_SESSION['keranjang']);
                unset($_SESSION['produk_tambahan']);
                unset($_SESSION['payment_temp']);
            }
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
        }
    }
}
?>