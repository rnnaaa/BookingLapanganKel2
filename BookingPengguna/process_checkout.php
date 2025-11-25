<?php
session_start();
require '../config/database.php'; 
date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

// 1. Cek Login & Keranjang
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

if (empty($_SESSION['keranjang'])) {
    echo json_encode(['status' => 'error', 'message' => 'Keranjang kosong.']);
    exit;
}

$user_id = $_SESSION['id_user'];
$keranjang = $_SESSION['keranjang'];
$expired_at = date('Y-m-d H:i:s', strtotime('+7 minutes')); // Waktu hold 7 menit

// === PERBAIKAN: HITUNG TOTAL HARGA DI AWAL ===
$total_booking_awal = 0;
foreach ($keranjang as $item) {
    $total_booking_awal += (float)$item['harga'];
}
// =============================================

mysqli_begin_transaction($conn);

try {
    // 2. Validasi Ketersediaan Slot (Double Check)
    $check_sql = "SELECT 1 FROM jadwal_detail jd
                  JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                  LEFT JOIN booking b ON jd.id_booking = b.id_booking
                  WHERE jd.id_jadwal_waktu = ? 
                  AND jh.tanggal = ? 
                  AND jh.id_lapangan = ? 
                  AND (
                      jd.status = 'dibooking' 
                      OR (jd.status = 'hold' AND b.expired_at > NOW())
                  )";
    
    $stmt_check = $conn->prepare($check_sql);

    foreach ($keranjang as $item) {
        $stmt_check->bind_param("isi", $item['id_jadwal_waktu'], $item['tanggal'], $item['id_lapangan']);
        $stmt_check->execute();
        if ($stmt_check->get_result()->num_rows > 0) {
            throw new Exception("Maaf, slot jam " . $item['jam'] . " baru saja diambil orang lain.");
        }
    }

    // 3. Buat Booking Utama (Status 'hold')
    // UPDATE: Masukkan $total_booking_awal ke database
    $first_item = $keranjang[0];
    $insert_booking = "INSERT INTO booking (id_user, id_lapangan, tanggal, tipe_booking, status, expired_at, total_amount) 
                       VALUES (?, ?, ?, 'reguler', 'hold', ?, ?)";
    
    $stmt_b = $conn->prepare($insert_booking);
    // Perhatikan parameter terakhir ('d' untuk decimal/double)
    $stmt_b->bind_param("iissd", $user_id, $first_item['id_lapangan'], $first_item['tanggal'], $expired_at, $total_booking_awal);
    $stmt_b->execute();
    $booking_id = $conn->insert_id;

    // 4. Insert Detail & Kunci Slot (Update status jadi 'hold')
    $insert_detail = "INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga) VALUES (?, ?, ?)";
    $stmt_d = $conn->prepare($insert_detail);

    $update_slot = "UPDATE jadwal_detail jd
                    JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                    SET jd.status = 'hold', jd.id_booking = ?
                    WHERE jd.id_jadwal_waktu = ? AND jh.tanggal = ? AND jh.id_lapangan = ?";
    $stmt_up = $conn->prepare($update_slot);

    foreach ($keranjang as $item) {
        // Insert detail
        $stmt_d->bind_param("iid", $booking_id, $item['id_jadwal_waktu'], $item['harga']);
        $stmt_d->execute();

        // Kunci slot
        $stmt_up->bind_param("iisi", $booking_id, $item['id_jadwal_waktu'], $item['tanggal'], $item['id_lapangan']);
        $stmt_up->execute();
    }

    // 5. Simpan Session ID Booking Sementara
    $_SESSION['temp_booking_id'] = $booking_id;
    $_SESSION['booking_expired_at'] = $expired_at;

    mysqli_commit($conn);

    // Redirect ke halaman produk tambahan
    echo json_encode(['status' => 'ok', 'redirect' => 'produk_tambahan.php?cart=1']); 

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>