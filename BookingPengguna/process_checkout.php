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
$expired_at = date('Y-m-d H:i:s', strtotime('+7 minutes')); 

// === 2. KELOMPOKKAN KERANJANG BERDASARKAN LAPANGAN ===
$grouped_items = [];
foreach ($keranjang as $item) {
    $lap_id = $item['id_lapangan'];
    if (!isset($grouped_items[$lap_id])) {
        $grouped_items[$lap_id] = [];
    }
    $grouped_items[$lap_id][] = $item;
}

mysqli_begin_transaction($conn);

try {
    $generated_booking_ids = [];

    // === 3. LOOPING UNTUK SETIAP LAPANGAN (MEMBUAT BOOKING TERPISAH) ===
    foreach ($grouped_items as $id_lapangan => $items) {
        
        // A. Hitung Total per Lapangan
        $subtotal_lapangan = 0;
        $tanggal_main = $items[0]['tanggal']; // Ambil tanggal dari item pertama di grup ini

        foreach ($items as $item) {
            $subtotal_lapangan += (float)$item['harga'];
            
            // B. Validasi Ketersediaan Slot (Double Check) per Item
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
            $stmt_check->bind_param("isi", $item['id_jadwal_waktu'], $item['tanggal'], $id_lapangan);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                throw new Exception("Maaf, slot jam " . $item['jam'] . " di " . $item['nama_lapangan'] . " baru saja diambil orang lain.");
            }
        }

        // C. Insert Booking Header (Satu per Lapangan)
        $insert_booking = "INSERT INTO booking (id_user, id_lapangan, tanggal, tipe_booking, status, expired_at, total_amount) 
                           VALUES (?, ?, ?, 'reguler', 'hold', ?, ?)";
        
        $stmt_b = $conn->prepare($insert_booking);
        $stmt_b->bind_param("iissd", $user_id, $id_lapangan, $tanggal_main, $expired_at, $subtotal_lapangan);
        $stmt_b->execute();
        $booking_id = $conn->insert_id;
        
        $generated_booking_ids[] = $booking_id; // Simpan ID Booking

        // D. Insert Detail & Kunci Slot
        $insert_detail = "INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga) VALUES (?, ?, ?)";
        $stmt_d = $conn->prepare($insert_detail);

        $update_slot = "UPDATE jadwal_detail jd
                        JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                        SET jd.status = 'hold', jd.id_booking = ?
                        WHERE jd.id_jadwal_waktu = ? AND jh.tanggal = ? AND jh.id_lapangan = ?";
        $stmt_up = $conn->prepare($update_slot);

        foreach ($items as $item) {
            // Insert detail
            $stmt_d->bind_param("iid", $booking_id, $item['id_jadwal_waktu'], $item['harga']);
            $stmt_d->execute();

            // Kunci slot
            $stmt_up->bind_param("iisi", $booking_id, $item['id_jadwal_waktu'], $item['tanggal'], $id_lapangan);
            $stmt_up->execute();
        }
    }

    // 4. Simpan Array ID Booking ke Session (Bukan single ID lagi)
    $_SESSION['temp_booking_ids'] = $generated_booking_ids; // Menggunakan array
    // Simpan salah satu ID untuk backward compatibility (opsional)
    $_SESSION['temp_booking_id'] = $generated_booking_ids[0]; 
    $_SESSION['booking_expired_at'] = $expired_at;

    mysqli_commit($conn);

    echo json_encode(['status' => 'ok', 'redirect' => 'produk_tambahan.php?cart=1']); 

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>