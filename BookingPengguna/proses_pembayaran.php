<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php'; 

// =================================================================
// 1. VALIDASI & KEAMANAN
// =================================================================

if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['id_user'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit;
}

// Cek session hold
if (!isset($_SESSION['temp_booking_id'])) {
    $_SESSION['booking_error'] = "Sesi booking tidak ditemukan atau kadaluarsa.";
    header('Location: booking.php');
    exit;
}

$temp_booking_id = $_SESSION['temp_booking_id'];

// Ambil data input
$metode_pembayaran_post = $_POST['metode_pembayaran_hidden'] ?? '';
$payment_type_post = $_POST['payment_type_hidden'] ?? 'lunas';
$payment_amount_post = (float)($_POST['payment_amount_hidden'] ?? 0);
$nama_pemesan = trim($_POST['nama_pemesan'] ?? $_SESSION['nama']);

// =================================================================
// 2. CEK STATUS BOOKING (HOLD)
// =================================================================

$cek_sql = "SELECT total_amount FROM booking 
            WHERE id_booking = ? AND status = 'hold' AND expired_at > NOW() 
            LIMIT 1";
$stmt_cek = $conn->prepare($cek_sql);
$stmt_cek->bind_param("i", $temp_booking_id);
$stmt_cek->execute();
$result_cek = $stmt_cek->get_result();

if ($result_cek->num_rows === 0) {
    unset($_SESSION['temp_booking_id']);
    unset($_SESSION['booking_expired_at']);
    $_SESSION['booking_error'] = "Waktu booking Anda telah habis. Silakan ulangi pesanan.";
    header('Location: booking.php');
    exit;
}

// =================================================================
// 3. HITUNG ULANG TOTAL (BERDASARKAN SESSION TERBARU)
// =================================================================

// PERBAIKAN: Hitung sewa dari SESSION, bukan DB.
// Karena user mungkin menghapus item di halaman payment.php
$items_to_book = $_SESSION['keranjang'] ?? [];
if (empty($items_to_book)) {
    header('Location: booking.php');
    exit;
}

$total_biaya_sewa = 0;
foreach ($items_to_book as $item) {
    $total_biaya_sewa += (float)$item['harga'];
}

// Hitung produk
$total_biaya_produk = 0;
$produk_tambahan = $_SESSION['produk_tambahan'] ?? [];
if (!empty($produk_tambahan)) {
    foreach ($produk_tambahan as $harga) {
        $total_biaya_produk += (float)$harga;
    }
}

$total_keseluruhan = $total_biaya_sewa + $total_biaya_produk;

// Tentukan DP / Lunas
$sisa_bayar = 0.00;
$dp_amount = 0.00;
$tipe_pembayaran_db = 'Pelunasan';
$payment_status_db = 'menunggu_verifikasi';

if ($payment_type_post === 'dp') {
    $amount_to_pay_now = $total_keseluruhan / 2;
    $sisa_bayar = $total_keseluruhan - $amount_to_pay_now;
    $dp_amount = $amount_to_pay_now;
    $tipe_pembayaran_db = 'DP';
} else {
    $amount_to_pay_now = $total_keseluruhan;
    $sisa_bayar = 0.00;
    $dp_amount = 0.00;
    $tipe_pembayaran_db = 'Pelunasan';
}

// Validasi
if (abs((float)$payment_amount_post - $amount_to_pay_now) > 1) {
    $_SESSION['booking_error'] = "Total pembayaran tidak cocok. Harap ulangi.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// =================================================================
// 4. UPLOAD BUKTI
// =================================================================
$bukti_file_name = null;

if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['bukti_pembayaran'];
    $upload_dir = '../uploads/bukti_pembayaran/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $max_size = 5 * 1024 * 1024; 
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($file['size'] > $max_size || !in_array($mime_type, $allowed_types)) {
        $_SESSION['booking_error'] = "File tidak valid (Max 5MB, JPG/PNG/PDF).";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $bukti_file_name = 'bukti_' . $temp_booking_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $bukti_file_name;

    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $_SESSION['booking_error'] = "Gagal mengupload file bukti.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
} else {
    $_SESSION['booking_error'] = "Anda wajib mengupload bukti pembayaran.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// =================================================================
// 5. UPDATE DATABASE
// =================================================================

mysqli_begin_transaction($conn);

try {
    // 1. Update Tabel BOOKING
    $sql_update = "UPDATE booking SET 
                   status = 'menunggu', 
                   payment_status = ?, 
                   payment_method = ?,
                   dp_amount = ?,
                   total_amount = ?,
                   remaining_amount = ?,
                   expired_at = NULL
                   WHERE id_booking = ?";
    
    $stmt_up = $conn->prepare($sql_update);
    $stmt_up->bind_param("ssdddi", 
        $payment_status_db, 
        $metode_pembayaran_post, 
        $dp_amount, 
        $total_keseluruhan, 
        $sisa_bayar, 
        $temp_booking_id
    );
    $stmt_up->execute();

    // 2. Insert Tabel PEMBAYARAN
    $sql_pay = "INSERT INTO pembayaran (booking_id, tipe, bukti_pembayaran, amount, method, status_verifikasi, tanggal_upload) 
                VALUES (?, ?, ?, ?, ?, 'menunggu', NOW())";
    $stmt_pay = $conn->prepare($sql_pay);
    $stmt_pay->bind_param("issds", 
        $temp_booking_id, 
        $tipe_pembayaran_db, 
        $bukti_file_name, 
        $amount_to_pay_now, 
        $metode_pembayaran_post
    );
    $stmt_pay->execute();

    // 3. Update Status Slot Jadwal (Hanya untuk item yang ADA DI SESSION)
    // Item 'hold' yang dihapus dari session akan tetap 'hold' dan expired sendiri (tidak jadi 'dibooking')
    $sql_slot = "UPDATE jadwal_detail SET status = 'dibooking' 
                 WHERE id_booking = ? AND id_jadwal_waktu = ?";
    $stmt_slot = $conn->prepare($sql_slot);
    
    foreach ($items_to_book as $item) {
        $stmt_slot->bind_param("ii", $temp_booking_id, $item['id_jadwal_waktu']);
        $stmt_slot->execute();
    }

    // 4. Hapus Detail Booking yang TIDAK ada di Session (Pembersihan Data)
    // Kita perlu menghapus row di detail_booking yang id_jadwal_waktu-nya TIDAK ada di session
    // Ini opsional tapi bagus agar database bersih dari item yang dibatalkan saat checkout
    // (Untuk sekarang, biarkan saja, item detail_booking yang tidak jadi dibayar tidak masalah)

    mysqli_commit($conn);
    
    // Bersihkan sesi
    unset($_SESSION['keranjang']);
    unset($_SESSION['produk_tambahan']);
    unset($_SESSION['temp_booking_id']);
    unset($_SESSION['booking_expired_at']);

    $_SESSION['booking_success'] = "Pembayaran berhasil dikirim! Booking ID #$temp_booking_id sedang menunggu verifikasi Admin.";
    header('Location: ../DashPengguna.php');
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    if ($bukti_file_name && file_exists($upload_path)) unlink($upload_path);

    $_SESSION['booking_error'] = "Terjadi kesalahan sistem: " . $e->getMessage();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
?>