<?php
// include_user/release_slots.php

// Pastikan koneksi $conn tersedia
if (isset($conn)) {
    $now = date('Y-m-d H:i:s');

    // 1. Lepaskan slot di jadwal_detail (kembalikan jadi 'tersedia')
    // Cari slot yang booking-nya berstatus 'hold' DAN waktu expired-nya sudah lewat
    $sql_release = "UPDATE jadwal_detail jd
                    JOIN booking b ON jd.id_booking = b.id_booking
                    SET jd.status = 'tersedia', jd.id_booking = NULL
                    WHERE b.status = 'hold' AND b.expired_at < '$now'";
    mysqli_query($conn, $sql_release);

    // 2. Batalkan booking yang expired
    $sql_cancel = "UPDATE booking 
                   SET status = 'dibatalkan' 
                   WHERE status = 'hold' AND expired_at < '$now'";
    mysqli_query($conn, $sql_cancel);
}
?>