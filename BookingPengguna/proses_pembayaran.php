<?php
date_default_timezone_set('Asia/Jakarta');
session_start();
require '../config/database.php'; // Path ke koneksi DB

// =================================================================
// 1. VALIDASI DASAR & KEAMANAN
// =================================================================

// Cek jika user sudah login
if (!isset($_SESSION['id_user'])) {
    header('Location: ../auth/login.php');
    exit;
}
$user_id = $_SESSION['id_user'];

// Cek jika ini adalah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: booking.php');
    exit;
}

// Cek jika keranjang kosong
if (empty($_SESSION['keranjang'])) {
    header('Location: booking.php');
    exit;
}

// Ambil data dari keranjang dan form
$items_to_book = $_SESSION['keranjang'];
$produk_tambahan = $_SESSION['produk_tambahan'] ?? [];

// Ambil data dari form verifikasi
$metode_pembayaran_post = $_POST['metode_pembayaran_hidden'] ?? 'unknown';
$payment_type_post = $_POST['payment_type_hidden'] ?? 'lunas';
$payment_amount_post = (float)($_POST['payment_amount_hidden'] ?? 0); // Jumlah yang dibayar
$nama_pemesan = trim($_POST['nama_pemesan'] ?? $_SESSION['nama']);

// =================================================================
// 2. PROSES UPLOAD BUKTI PEMBAYARAN
// =================================================================
$bukti_file_name = null;
if (isset($_FILES['bukti_pembayaran']) && $_FILES['bukti_pembayaran']['error'] === UPLOAD_ERR_OK) {
    
    $file = $_FILES['bukti_pembayaran'];
    $upload_dir = '../uploads/bukti_pembayaran/'; // Pastikan folder ini ada dan writable
    $max_size = 5 * 1024 * 1024; // 5 MB
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    
    // Validasi ukuran
    if ($file['size'] > $max_size) {
        $_SESSION['booking_error'] = "File terlalu besar. Maksimum 5MB.";
        header('Location: ' . $_SERVER['HTTP_REFERER']); // Kembali ke halaman verifikasi
        exit;
    }
    
    // Validasi tipe file
    if (!in_array($file['type'], $allowed_types)) {
        $_SESSION['booking_error'] = "Format file tidak valid. Hanya JPG, PNG, atau PDF.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }

    // Buat nama file unik
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $bukti_file_name = 'bukti_' . $user_id . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $bukti_file_name;

    // Pindahkan file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        $_SESSION['booking_error'] = "Gagal mengupload file bukti.";
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
} else {
    // Jika tidak ada file atau ada error saat upload
    $_SESSION['booking_error'] = "Anda wajib mengupload bukti pembayaran.";
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

// =================================================================
// 3. HITUNG ULANG TOTAL (JANGAN PERCAYA POST DARI CLIENT)
// =================================================================
$total_biaya_sewa = 0;
foreach ($items_to_book as $item) {
    $total_biaya_sewa += (float)$item['harga'];
}

$total_biaya_produk = 0;
foreach ($produk_tambahan as $harga) {
    $total_biaya_produk += (float)$harga;
}

$total_keseluruhan = $total_biaya_sewa + $total_biaya_produk;

// Tentukan jumlah yang harus dibayar dan sisa
$sisa_bayar = 0.00;
$dp_amount = 0.00;
$tipe_pembayaran_db = 'Pelunasan'; // Tipe untuk tabel 'pembayaran'
$payment_status_db = 'menunggu_verifikasi';

if ($payment_type_post === 'dp') {
    $amount_to_pay_now = $total_keseluruhan / 2;
    $sisa_bayar = $total_keseluruhan - $amount_to_pay_now;
    $dp_amount = $amount_to_pay_now;
    $tipe_pembayaran_db = 'DP';
    $payment_status_db = 'menunggu_verifikasi'; // Tetap menunggu verifikasi meskipun DP
} else {
    $amount_to_pay_now = $total_keseluruhan;
    $sisa_bayar = 0.00;
    $dp_amount = 0.00; // Tidak ada DP karena Lunas
    $tipe_pembayaran_db = 'Pelunasan';
    $payment_status_db = 'menunggu_verifikasi'; // Tetap menunggu verifikasi
}

// Validasi terakhir jika amount dari POST tidak cocok dengan perhitungan server
if (abs((float)$payment_amount_post - $amount_to_pay_now) > 0.01) { // Toleransi 1 sen
    // Jika tidak cocok, hapus file yang baru diupload
    if ($bukti_file_name && file_exists($upload_path)) {
        unlink($upload_path);
    }
    $_SESSION['booking_error'] = "Total pembayaran tidak cocok. Silakan coba lagi.";
    header('Location: payment.php?' . $_SERVER['QUERY_STRING']);
    exit;
}


// =================================================================
// 4. TRANSAKSI DATABASE (KRUSIAL)
// =================================================================
// Kita gunakan Transaksi: Jika salah satu gagal, semua akan dibatalkan (rollback).

mysqli_begin_transaction($conn);

try {
    // Ambil item pertama untuk referensi utama booking
    // (Sesuai struktur DB Anda, 1 booking = 1 lapangan & 1 tanggal)
    $first_item = $items_to_book[0];
    $booking_id_lapangan = $first_item['id_lapangan'];
    $booking_tanggal = $first_item['tanggal'];

    // ----- QUERY 1: INSERT ke tabel 'booking' -----
    $sql_booking = "INSERT INTO booking 
                        (id_user, id_lapangan, tanggal, tipe_booking, status, 
                         dp_amount, total_amount, remaining_amount, payment_status, payment_method) 
                    VALUES (?, ?, ?, 'reguler', 'menunggu', ?, ?, ?, ?, ?)";
    
    $stmt_booking = $conn->prepare($sql_booking);
    $stmt_booking->bind_param(
        "iissddss", 
        $user_id, 
        $booking_id_lapangan, 
        $booking_tanggal, 
        $dp_amount, 
        $total_keseluruhan, 
        $sisa_bayar, 
        $payment_status_db, 
        $metode_pembayaran_post
    );
    $stmt_booking->execute();
    $new_booking_id = $conn->insert_id; // Ambil ID booking baru

    if ($new_booking_id === 0) {
        throw new Exception("Gagal membuat data booking utama.");
    }

    // ----- QUERY 2: INSERT ke tabel 'detail_booking' (Loop) -----
    $sql_detail = "INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga) VALUES (?, ?, ?)";
    $stmt_detail = $conn->prepare($sql_detail);
    
    foreach ($items_to_book as $item) {
        $stmt_detail->bind_param("iid", $new_booking_id, $item['id_jadwal_waktu'], $item['harga']);
        $stmt_detail->execute();
    }

    // ----- QUERY 3: UPDATE 'jadwal_detail' (Loop) -----
    // Ini penting untuk menandai slot sudah dibooking
    $sql_update_jadwal = "UPDATE jadwal_detail jd
                          JOIN jadwal_harian jh ON jd.id_jadwal_harian = jh.id_jadwal_harian
                          SET jd.status = 'dibooking', jd.id_booking = ?
                          WHERE jd.id_jadwal_waktu = ? 
                          AND jh.tanggal = ? 
                          AND jh.id_lapangan = ?
                          AND jd.status = 'tersedia'"; 
    
    $stmt_update = $conn->prepare($sql_update_jadwal);
    foreach ($items_to_book as $item) {
        $stmt_update->bind_param(
            "iisi", 
            $new_booking_id, 
            $item['id_jadwal_waktu'], 
            $item['tanggal'], 
            $item['id_lapangan']
        );
        $stmt_update->execute();

        // === PENGAMAN TAMBAHAN ===
        // Cek apakah update berhasil (jika 0, berarti slot sudah diambil orang lain)
        if ($stmt_update->affected_rows === 0) {
            // Ini akan memicu catch(Exception $e) dan me-rollback transaksi
            throw new Exception("Slot pada jam " . $item['jam'] . " baru saja dipesan oleh orang lain. Silakan pilih jam lain.");
        }
        // === AKHIR PENGAMAN ===
    }

    // ----- QUERY 4: INSERT ke tabel 'pembayaran' -----
    $sql_payment = "INSERT INTO pembayaran 
                        (booking_id, tipe, bukti_pembayaran, amount, method, status_verifikasi, tanggal_upload) 
                    VALUES (?, ?, ?, ?, ?, 'menunggu', NOW())";
    
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bind_param(
        "issds", // <-- INI PERBAIKANNYA
        $new_booking_id, 
        $tipe_pembayaran_db, 
        $bukti_file_name,      // <-- Sekarang 's' (string)
        $amount_to_pay_now,    // <-- Sekarang 'd' (double)
        $metode_pembayaran_post
    );
    $stmt_payment->execute();

    // Jika semua berhasil, commit transaksi
    mysqli_commit($conn);

    // =================================================================
    // 5. BERSIHKAN SESSION & REDIRECT
    // =================================================================
    
    unset($_SESSION['keranjang']);
    unset($_SESSION['produk_tambahan']);

    $_SESSION['booking_success'] = "Booking Anda (ID: #$new_booking_id) telah berhasil dibuat dan sedang menunggu verifikasi oleh Admin.";
    header('Location: ../DashPengguna.php');
    exit;

} catch (Exception $e) {
    // =================================================================
    // 6. JIKA GAGAL (ROLLBACK)
    // =================================================================
    
    mysqli_rollback($conn); // Batalkan semua kueri DB

    // Hapus file yang sudah diupload jika transaksi gagal
    if ($bukti_file_name && file_exists($upload_path)) {
        unlink($upload_path);
    }

    // Kirim pesan error kembali ke halaman verifikasi
    $_SESSION['booking_error'] = "Database error: " . $e->getMessage();
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}
?>