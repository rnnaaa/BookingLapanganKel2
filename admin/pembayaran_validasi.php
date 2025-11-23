<?php
// pembayaran_validasi.php - PERBAIKAN LOGIKA + fitur pelunasan otomatis (admin)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';
session_start();

// Cek hak akses dan ID Admin
if (!isset($_SESSION['id_user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    $_SESSION['error'] = "Akses ditolak. Anda harus login sebagai Admin.";
    header("Location: ../auth/login.php");
    exit;
}

$aksi = $_GET['aksi'] ?? '';
$admin_id = intval($_SESSION['id_user'] ?? 0);

if (!$aksi) {
    $_SESSION['error'] = "Aksi tidak valid.";
    header("Location: pembayaran.php");
    exit;
}

mysqli_begin_transaction($conn);
try {
    if ($aksi === 'valid' || $aksi === 'tolak') {
        $id = intval($_GET['id'] ?? 0);
        if (!$id) {
            throw new Exception("ID pembayaran tidak valid.");
        }

        // Ambil pembayaran
        $sql_p = "SELECT * FROM pembayaran WHERE id_pembayaran = ? LIMIT 1";
        $stmt_p = mysqli_prepare($conn, $sql_p);
        mysqli_stmt_bind_param($stmt_p, "i", $id);
        mysqli_stmt_execute($stmt_p);
        $res_p = mysqli_stmt_get_result($stmt_p);
        $p = mysqli_fetch_assoc($res_p);
        mysqli_stmt_close($stmt_p);

        if (!$p) throw new Exception("Data pembayaran tidak ditemukan.");
        if ($p['status_verifikasi'] !== 'menunggu') {
            throw new Exception("Pembayaran sudah diproses sebelumnya.");
        }

        $booking_id = intval($p['booking_id']);
        $amount = floatval($p['amount']);
        $tipe_pembayaran = $p['tipe']; // DP / Pelunasan

        // Ambil booking (Ganti JOIN ke LEFT JOIN untuk mendukung Walk-in / User dihapus)
        $sql_b = "SELECT b.*, u.nama AS nama_user, l.nama_lapangan 
                  FROM booking b 
                  LEFT JOIN users u ON b.id_user=u.id_user 
                  LEFT JOIN lapangan l ON b.id_lapangan=l.id_lapangan 
                  WHERE b.id_booking = ? LIMIT 1";
        $stmt_b = mysqli_prepare($conn, $sql_b);
        mysqli_stmt_bind_param($stmt_b, "i", $booking_id);
        mysqli_stmt_execute($stmt_b);
        $res_b = mysqli_stmt_get_result($stmt_b);
        $booking = mysqli_fetch_assoc($res_b);
        mysqli_stmt_close($stmt_b);
        if (!$booking) throw new Exception("Data booking tidak ditemukan.");

        // 1) Jika valid -> update pembayaran, hitung total_paid, update booking payment_status & remaining
        if ($aksi === 'valid') {
            $stmt = mysqli_prepare($conn, "UPDATE pembayaran SET status_verifikasi='valid', verified_by=?, verified_at=NOW(), updated_at=NOW() WHERE id_pembayaran = ? AND status_verifikasi = 'menunggu'");
            mysqli_stmt_bind_param($stmt, "ii", $admin_id, $id);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) === 0) {
                throw new Exception("Gagal update pembayaran (mungkin sudah diproses).");
            }
            mysqli_stmt_close($stmt);

            // total yang sudah valid untuk booking
            $sum_sql = "SELECT COALESCE(SUM(amount),0) AS total_paid FROM pembayaran WHERE booking_id = ? AND status_verifikasi = 'valid'";
            $stmt_sum = mysqli_prepare($conn, $sum_sql);
            mysqli_stmt_bind_param($stmt_sum, "i", $booking_id);
            mysqli_stmt_execute($stmt_sum);
            $res_sum = mysqli_stmt_get_result($stmt_sum);
            $row = mysqli_fetch_assoc($res_sum);
            mysqli_stmt_close($stmt_sum);
            $total_paid = floatval($row['total_paid'] ?? 0);

            $total_amount = floatval($booking['total_amount'] ?? 0);
            $remaining = max(0, $total_amount - $total_paid);

            // tentukan payment_status
            if ($total_paid >= $total_amount && $total_amount > 0) {
                $payment_status = "lunas";
            } elseif ($total_paid > 0) {
                $payment_status = "dp_bayar";
            } else {
                $payment_status = "belum_bayar";
            }

            // update booking: remaining_amount, payment_status, jika lunas set disetujui
            $new_status = $booking['status'];
            if ($payment_status === 'lunas') $new_status = 'disetujui';

            $stmt = mysqli_prepare($conn, "UPDATE booking SET remaining_amount = ?, payment_status = ?, status = ?, updated_at = NOW() WHERE id_booking = ?");
            mysqli_stmt_bind_param($stmt, "dssi", $remaining, $payment_status, $new_status, $booking_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // KEUANGAN: ✅ INI SUDAH BENAR - hanya jika tipe = Pelunasan -> masukkan 1x jumlah keseluruhan (total_amount), cek anti-duplikat
            if (strtolower($tipe_pembayaran) === 'pelunasan') {
                $cek_sql = "SELECT id_keuangan FROM keuangan WHERE booking_id = ? LIMIT 1";
                $stmt_cek = mysqli_prepare($conn, $cek_sql);
                mysqli_stmt_bind_param($stmt_cek, "i", $booking_id);
                mysqli_stmt_execute($stmt_cek);
                $res_cek = mysqli_stmt_get_result($stmt_cek);
                $exists_keu = mysqli_fetch_assoc($res_cek);
                mysqli_stmt_close($stmt_cek);

                if (!$exists_keu) {
                    $tanggal = date('Y-m-d');
                    $kategori = 'Pelunasan';
                    $user_display = $booking['nama_user'] ?? 'Walk-in';
                    $lap_display = $booking['nama_lapangan'] ?? 'N/A';
                    $keterangan = "Pembayaran Pelunasan Booking #{$booking_id} - {$user_display} ({$lap_display})";
                    $sumber = 'Pelunasan';

                    $insert_sql = "INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, pembayaran_id, created_at)
                                 VALUES (?, 'pemasukan', ?, ?, ?, ?, ?, ?, NOW())";
                    $stmt_ins = mysqli_prepare($conn, $insert_sql);
                    mysqli_stmt_bind_param($stmt_ins, "sssdsii", $tanggal, $kategori, $keterangan, $total_amount, $sumber, $booking_id, $id);
                    mysqli_stmt_execute($stmt_ins);
                    mysqli_stmt_close($stmt_ins);
                }
            }

            mysqli_commit($conn);

            if (strtolower($tipe_pembayaran) === 'dp') {
                $_SESSION['success'] = "<b>Pembayaran DP berhasil divalidasi (sementara)!</b><br>Booking <b>#{$booking_id}</b><br>Nominal: <b>Rp " . number_format($amount,0,',','.') . "</b><br>Status Pembayaran: <b>{$payment_status}</b><br>Sisa: <b>Rp " . number_format($remaining,0,',','.') . "</b><br>ℹ️ Catatan: Untuk DP, pencatatan keuangan ditunda sampai pelunasan.";
            } else {
                $_SESSION['success'] = "<b>Pembayaran Pelunasan berhasil divalidasi!</b><br>Booking <b>#{$booking_id}</b><br>Nominal: <b>Rp " . number_format($amount,0,',','.') . "</b><br>Status Pembayaran: <b>{$payment_status}</b><br>Sisa: <b>Rp " . number_format($remaining,0,',','.') . "</b><br>✔️ Dicatat dalam keuangan (jumlah keseluruhan: Rp " . number_format($total_amount,0,',','.') . ").";
            }

        } elseif ($aksi === 'tolak') {
            // Logika Tolak tidak mengubah status booking/payment/keuangan, hanya mengubah status pembayaran
            $stmt = mysqli_prepare($conn, "UPDATE pembayaran SET status_verifikasi='tidak_valid', verified_by=?, verified_at=NOW(), updated_at=NOW() WHERE id_pembayaran = ? AND status_verifikasi = 'menunggu'");
            mysqli_stmt_bind_param($stmt, "ii", $admin_id, $id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);
            $_SESSION['success'] = "❌ Pembayaran ditolak. User harus upload ulang bukti.";
        }

    } elseif ($aksi === 'pelunasan') {
        // Proses pelunasan oleh admin (customer bayar di tempat)
        // Input: booking_id (GET)
        $booking_id = intval($_GET['booking_id'] ?? 0);
        if (!$booking_id) throw new Exception("Booking ID tidak valid untuk pelunasan.");

        // Ambil booking (Ganti JOIN ke LEFT JOIN untuk mendukung Walk-in / User dihapus)
        $sql_b = "SELECT b.*, u.nama AS nama_user, l.nama_lapangan 
                  FROM booking b 
                  LEFT JOIN users u ON b.id_user=u.id_user 
                  LEFT JOIN lapangan l ON b.id_lapangan=l.id_lapangan 
                  WHERE b.id_booking = ? LIMIT 1";
        $stmt_b = mysqli_prepare($conn, $sql_b);
        mysqli_stmt_bind_param($stmt_b, "i", $booking_id);
        mysqli_stmt_execute($stmt_b);
        $res_b = mysqli_stmt_get_result($stmt_b);
        $booking = mysqli_fetch_assoc($res_b);
        mysqli_stmt_close($stmt_b);
        if (!$booking) throw new Exception("Booking tidak ditemukan.");

        $total_amount = floatval($booking['total_amount'] ?? 0);

        // Hitung total_paid existing (valid)
        $sum_sql = "SELECT COALESCE(SUM(amount),0) AS total_paid FROM pembayaran WHERE booking_id = ? AND status_verifikasi = 'valid'";
        $stmt_sum = mysqli_prepare($conn, $sum_sql);
        mysqli_stmt_bind_param($stmt_sum, "i", $booking_id);
        mysqli_stmt_execute($stmt_sum);
        $res_sum = mysqli_stmt_get_result($stmt_sum);
        $row = mysqli_fetch_assoc($res_sum);
        mysqli_stmt_close($stmt_sum);
        $total_paid = floatval($row['total_paid'] ?? 0);

        $remaining = max(0, $total_amount - $total_paid);
        if ($remaining <= 0) {
            throw new Exception("Tidak ada sisa pembayaran untuk pelunasan (remaining = 0).");
        }
        
        // 1) Masukkan pembayaran baru bertipe 'Pelunasan' (tunai/offline) dan tandai langsung valid
        $tipe = 'Pelunasan';
        $method = 'Tunai (Offline)';
        $status_ver = 'valid';
        $tanggal_upload = date('Y-m-d H:i:s');

        $insert_p_sql = "INSERT INTO pembayaran (booking_id, tipe, bukti_pembayaran, amount, method, status_verifikasi, verified_by, verified_at, tanggal_upload, created_at, updated_at)
                         VALUES (?, ?, NULL, ?, ?, ?, ?, NOW(), ?, NOW(), NOW())";
        $stmt_ins = mysqli_prepare($conn, $insert_p_sql);
        // bind types: booking_id(i), tipe(s), amount(d), method(s), status_ver(s), verified_by(i), tanggal_upload(s)
        mysqli_stmt_bind_param($stmt_ins, "isdssis", $booking_id, $tipe, $remaining, $method, $status_ver, $admin_id, $tanggal_upload);
        if (!mysqli_stmt_execute($stmt_ins)) {
            $err = mysqli_stmt_error($stmt_ins);
            mysqli_stmt_close($stmt_ins);
            throw new Exception("Gagal insert pembayaran pelunasan: " . $err);
        }
        $new_payment_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_ins);

        if (!$new_payment_id) throw new Exception("Gagal membuat entri pembayaran pelunasan.");
        
        // 2) Masukkan ke tabel keuangan jika belum ada untuk booking ini (anti-duplikat)
        // ✅ Masih menggunakan total_amount agar entri keuangan mencerminkan total pendapatan dari booking ini
        $cek_sql = "SELECT id_keuangan FROM keuangan WHERE booking_id = ? LIMIT 1";
        $stmt_cek = mysqli_prepare($conn, $cek_sql);
        mysqli_stmt_bind_param($stmt_cek, "i", $booking_id);
        mysqli_stmt_execute($stmt_cek);
        $res_cek = mysqli_stmt_get_result($stmt_cek);
        $exists_keu = mysqli_fetch_assoc($res_cek);
        mysqli_stmt_close($stmt_cek);

        if (!$exists_keu) {
            $tanggal = date('Y-m-d');
            $kategori = 'Pelunasan';
            $user_display = $booking['nama_user'] ?? 'Walk-in';
            $lap_display = $booking['nama_lapangan'] ?? 'N/A';
            $keterangan = "Pembayaran Pelunasan Booking #{$booking_id} - {$user_display} ({$lap_display})";
            $sumber = 'Pelunasan';

            $insert_sql = "INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, pembayaran_id, created_at)
                           VALUES (?, 'pemasukan', ?, ?, ?, ?, ?, ?, NOW())";
            $stmt_insk = mysqli_prepare($conn, $insert_sql);
            // Jumlah yang dimasukkan adalah TOTAL HARGA BOOKING (bukan hanya sisa/remaining)
            mysqli_stmt_bind_param($stmt_insk, "sssdsii", $tanggal, $kategori, $keterangan, $total_amount, $sumber, $booking_id, $new_payment_id);
            mysqli_stmt_execute($stmt_insk);
            mysqli_stmt_close($stmt_insk);
        }

        // 3) Update booking: remaining_amount = 0, payment_status = 'lunas', status = 'disetujui'
        $stmt_upd = mysqli_prepare($conn, "UPDATE booking SET remaining_amount = 0, payment_status = 'lunas', status = 'disetujui', updated_at = NOW() WHERE id_booking = ?");
        mysqli_stmt_bind_param($stmt_upd, "i", $booking_id);
        mysqli_stmt_execute($stmt_upd);
        mysqli_stmt_close($stmt_upd);

        mysqli_commit($conn);
        $_SESSION['success'] = "✅ Pelunasan berhasil diproses. Booking #{$booking_id} LUNAS dan sudah dicatat ke tabel keuangan (Rp " . number_format($total_amount,0,',','.') . ").";

    } else {
        throw new Exception("Aksi tidak dikenali.");
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Gagal memproses: " . $e->getMessage();
}

header("Location: pembayaran.php");
exit;