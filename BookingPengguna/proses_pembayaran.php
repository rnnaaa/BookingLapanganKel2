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

$cek_sql = "SELECT status, total_amount FROM booking WHERE id_booking = ?";
$stmt_cek = $conn->prepare($cek_sql);
$stmt_cek->bind_param("i", $temp_booking_id);
$stmt_cek->execute();
$res_cek = $stmt_cek->get_result();
$row_cek = $res_cek->fetch_assoc();

if (!$row_cek || $row_cek['status'] !== 'hold') {
    $_SESSION['booking_error'] = "Maaf, sesi booking Anda sudah habis/kadaluarsa.";
    header('Location: booking.php');
    exit;
}

// =================================================================
// 3. PROSES UPLOAD BUKTI (DIPERBAIKI: PATH ABSOLUT & AUTO CREATE FOLDER)
// =================================================================

$bukti_baru = null;

// Tentukan folder tujuan menggunakan __DIR__ (Path Absolut) agar aman
// __DIR__ adalah folder tempat file ini berada (BookingPengguna)
// Kita naik satu level (..) lalu masuk ke uploads/bukti
$target_dir = __DIR__ . '/../uploads/bukti_pembayaran/';

// Cek apakah folder tujuan ada, jika tidak, buat foldernya!
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0755, true);
}

if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === 0) {
    $ext = pathinfo($_FILES['bukti_pembayaran']['name'], PATHINFO_EXTENSION);
    // Nama file unik
    $bukti_baru = "bukti_" . $temp_booking_id . "_" . time() . "." . $ext;
    
    // Pindahkan file ke folder tujuan
    if (!move_uploaded_file($_FILES['bukti_pembayaran']['tmp_name'], $target_dir . $bukti_baru)) {
        $_SESSION['booking_error'] = "Gagal menyimpan file ke server. Cek izin folder uploads.";
        header('Location: verifikasi_payment.php'); 
        exit;
    }
} else {
    $_SESSION['booking_error'] = "Bukti pembayaran wajib diupload.";
    header('Location: verifikasi_payment.php');
    exit;
}

// =================================================================
// 4. UPDATE DATABASE (FINALISASI)
// =================================================================

mysqli_begin_transaction($conn);

try {
    // 1. Insert ke Tabel Pembayaran
    $sql_bayar = "INSERT INTO pembayaran (booking_id, tipe, bukti_pembayaran, amount, method, status_verifikasi, verified_by) 
                  VALUES (?, ?, ?, ?, ?, 'menunggu', NULL)";
    $tipe_db = ($payment_type_post === 'lunas') ? 'Pelunasan' : 'DP';
    
    $stmt_p = $conn->prepare($sql_bayar);
    // s = string (bukti_baru), d = double (amount)
    $stmt_p->bind_param("issds", $temp_booking_id, $tipe_db, $bukti_baru, $payment_amount_post, $metode_pembayaran_post);
    $stmt_p->execute();

    // 2. Update Status Booking Utama -> 'menunggu'
    $status_bayar = 'menunggu_verifikasi';
    $sql_update = "UPDATE booking SET status = 'menunggu', payment_status = ?, payment_method = ? WHERE id_booking = ?";
    $stmt_u = $conn->prepare($sql_update);
    $stmt_u->bind_param("ssi", $status_bayar, $metode_pembayaran_post, $temp_booking_id);
    $stmt_u->execute();

    // 3. Pastikan Slot Jadwal Tetap Aman (Status 'dibooking')
    $sql_slot = "UPDATE jadwal_detail SET status = 'dibooking' WHERE id_booking = ?";
    $stmt_slot = $conn->prepare($sql_slot);
    $stmt_slot->bind_param("i", $temp_booking_id);
    $stmt_slot->execute();

    // === SIMPAN INFO PRODUK KE TABEL BOOKING (KOLOM info_produk) ===
    if (isset($_SESSION['produk_tambahan']) && !empty($_SESSION['produk_tambahan'])) {
        $total_produk_db = 0;
        $list_produk_str = []; 

        foreach ($_SESSION['produk_tambahan'] as $id_prod => $item) {
            $harga = $item['harga'];
            $nama_produk = $item['nama'];
            $total_produk_db += $harga;
            $list_produk_str[] = "$nama_produk (Rp " . number_format($harga, 0, ',', '.') . ")";
        }

        $info_produk_text = implode(", ", $list_produk_str);

        $update_total = "UPDATE booking SET total_amount = total_amount + ?, info_produk = ? WHERE id_booking = ?";
        $stmt_ub = $conn->prepare($update_total);
        $stmt_ub->bind_param("dsi", $total_produk_db, $info_produk_text, $temp_booking_id);
        $stmt_ub->execute();
    }
    // ===================================================================

    mysqli_commit($conn);
    
    // Bersihkan sesi
    unset($_SESSION['keranjang']);
    unset($_SESSION['produk_tambahan']);
    unset($_SESSION['temp_booking_id']);
    unset($_SESSION['booking_expired_at']);
    unset($_SESSION['payment_temp']); 

    $_SESSION['booking_success'] = "Pembayaran berhasil dikirim! Booking ID #$temp_booking_id sedang menunggu verifikasi Admin.";
    header("Location: ../riwayat/riwayat.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['booking_error'] = "Terjadi kesalahan sistem: " . $e->getMessage();
    header("Location: booking.php");
    exit;
}
?>