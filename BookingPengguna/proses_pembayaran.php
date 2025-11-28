<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php'; 

// 1. VALIDASI & KEAMANAN
if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php'); exit;
}
$user_id = $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php'); exit;
}

// Cek session hold (Support Multiple IDs)
if (!isset($_SESSION['temp_booking_ids']) && !isset($_SESSION['temp_booking_id'])) {
    $_SESSION['booking_error'] = "Sesi booking tidak ditemukan atau kadaluarsa.";
    header('Location: booking.php'); exit;
}

// Ambil Booking IDs (Prioritas Array)
$booking_ids = isset($_SESSION['temp_booking_ids']) ? $_SESSION['temp_booking_ids'] : [$_SESSION['temp_booking_id']];

// Ambil data input
$metode_pembayaran_post = $_POST['metode_pembayaran_hidden'] ?? '';
$payment_type_post = $_POST['payment_type_hidden'] ?? 'lunas';
$payment_amount_total_post = (float)($_POST['payment_amount_hidden'] ?? 0); // Ini total bayar dari user

// =================================================================
// 2. PROSES UPLOAD BUKTI
// =================================================================
$bukti_baru = null;
$target_dir = __DIR__ . '/../uploads/bukti_pembayaran/';
if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);

if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === 0) {
    $ext = pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION);
    // Gunakan ID pertama untuk nama file
    $bukti_baru = "bukti_" . $booking_ids[0] . "_" . time() . "." . $ext;
    if (!move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $target_dir . $bukti_baru)) {
        $_SESSION['booking_error'] = "Gagal menyimpan file.";
        header('Location: verifikasi_payment.php'); exit;
    }
} else {
    $_SESSION['booking_error'] = "Bukti pembayaran wajib diupload.";
    header('Location: verifikasi_payment.php'); exit;
}

// =================================================================
// 3. UPDATE DATABASE (LOOPING UNTUK SETIAP BOOKING)
// =================================================================

mysqli_begin_transaction($conn);

try {
    // Hitung Total Asli Semua Booking (Untuk Proporsi Pembayaran)
    $grand_total_bookings = 0;
    $booking_details = [];
    
    foreach($booking_ids as $bid) {
        $q = mysqli_query($conn, "SELECT total_amount FROM booking WHERE id_booking = '$bid'");
        $d = mysqli_fetch_assoc($q);
        $booking_details[$bid] = (float)$d['total_amount'];
        $grand_total_bookings += (float)$d['total_amount'];
    }

    // Tambahkan Total Produk Tambahan (Hanya ditambahkan ke Booking Pertama)
    $total_produk = 0;
    $info_produk_text = NULL;
    if (isset($_SESSION['produk_tambahan']) && !empty($_SESSION['produk_tambahan'])) {
        $list_produk_str = []; 
        foreach ($_SESSION['produk_tambahan'] as $item) {
            $total_produk += (float)$item['harga'];
            $list_produk_str[] = $item['nama'] . " (Rp " . number_format($item['harga'], 0, ',', '.') . ")";
        }
        $info_produk_text = implode(", ", $list_produk_str);
    }
    
    $grand_total_system = $grand_total_bookings + $total_produk;

    // Loop Processing
    $is_first = true;
    foreach ($booking_ids as $bid) {
        
        $current_booking_total = $booking_details[$bid];
        
        // Jika ini booking pertama, tambahkan harga produk ke total booking ini
        if ($is_first && $total_produk > 0) {
            $current_booking_total += $total_produk;
            
            // Update Booking DB untuk memasukkan info produk
            $stmt_prod = $conn->prepare("UPDATE booking SET total_amount = ?, info_produk = ? WHERE id_booking = ?");
            $stmt_prod->bind_param("dsi", $current_booking_total, $info_produk_text, $bid);
            $stmt_prod->execute();
        }

        // Hitung Jumlah Bayar per Booking (Proporsional)
        // Rumus: (Total Booking Ini / Total Semua Tagihan) * Total Yang Dibayar User
        if ($grand_total_system > 0) {
            $amount_per_booking = ($current_booking_total / $grand_total_system) * $payment_amount_total_post;
        } else {
            $amount_per_booking = 0;
        }

        // 1. Insert ke Tabel Pembayaran
        $tipe_db = ($payment_type_post === 'lunas') ? 'Pelunasan' : 'DP';
        $sql_bayar = "INSERT INTO pembayaran (booking_id, tipe, bukti_pembayaran, amount, method, status_verifikasi) 
                      VALUES (?, ?, ?, ?, ?, 'menunggu')";
        $stmt_p = $conn->prepare($sql_bayar);
        $stmt_p->bind_param("issds", $bid, $tipe_db, $bukti_baru, $amount_per_booking, $metode_pembayaran_post);
        $stmt_p->execute();

        // 2. Update Status Booking
        $sql_update = "UPDATE booking SET status = 'menunggu', payment_status = ?, payment_method = ? WHERE id_booking = ?";
        $status_bayar = 'menunggu_verifikasi';
        $stmt_u = $conn->prepare($sql_update);
        $stmt_u->bind_param("ssi", $status_bayar, $metode_pembayaran_post, $bid);
        $stmt_u->execute();

        // 3. Update Slot Jadwal
        $sql_slot = "UPDATE jadwal_detail SET status = 'dibooking' WHERE id_booking = ?";
        $stmt_slot = $conn->prepare($sql_slot);
        $stmt_slot->bind_param("i", $bid);
        $stmt_slot->execute();

        $is_first = false; // Loop selanjutnya bukan yang pertama
    }

    mysqli_commit($conn);
    
    // Bersihkan sesi
    unset($_SESSION['keranjang']);
    unset($_SESSION['produk_tambahan']);
    unset($_SESSION['temp_booking_id']);
    unset($_SESSION['temp_booking_ids']); // Hapus array IDs
    unset($_SESSION['booking_expired_at']);
    unset($_SESSION['payment_temp']); 

    $_SESSION['booking_success'] = "Pembayaran berhasil dikirim! Menunggu verifikasi Admin.";
    header("Location: ../riwayat/riwayat.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['booking_error'] = "Terjadi kesalahan: " . $e->getMessage();
    header("Location: booking.php");
    exit;
}
?>