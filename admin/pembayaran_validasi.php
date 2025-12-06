<?php
// pembayaran_validasi.php
require_once 'auth_check.php';

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
    // =================================================================================
    // KASUS 1: ADMIN KLIK VALID / TOLAK (Verifikasi Bukti Upload User)
    // =================================================================================
    if ($aksi === 'valid' || $aksi === 'tolak') {
        $id_pembayaran_target = intval($_GET['id'] ?? 0);
        if (!$id_pembayaran_target) {
            throw new Exception("ID pembayaran tidak valid.");
        }

        // Ambil data pembayaran yang akan divalidasi
        $sql_p = "SELECT * FROM pembayaran WHERE id_pembayaran = ? LIMIT 1";
        $stmt_p = mysqli_prepare($conn, $sql_p);
        mysqli_stmt_bind_param($stmt_p, "i", $id_pembayaran_target);
        mysqli_stmt_execute($stmt_p);
        $res_p = mysqli_stmt_get_result($stmt_p);
        $p = mysqli_fetch_assoc($res_p);
        mysqli_stmt_close($stmt_p);

        if (!$p) throw new Exception("Data pembayaran tidak ditemukan.");
        if ($p['status_verifikasi'] !== 'menunggu') {
            throw new Exception("Pembayaran sudah diproses sebelumnya.");
        }

        $booking_id = intval($p['booking_id']);
        $amount_paid_now = floatval($p['amount']); // Nominal yang dibayar di transaksi ini
        $tipe_pembayaran_user = $p['tipe']; // 'DP' atau 'Pelunasan'

        // Ambil data booking terkait
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

        // --- JIKA AKSI VALID ---
        if ($aksi === 'valid') {
            // 1. Update status di tabel pembayaran jadi 'valid'
            $stmt = mysqli_prepare($conn, "UPDATE pembayaran SET status_verifikasi='valid', verified_by=?, verified_at=NOW(), updated_at=NOW() WHERE id_pembayaran = ? AND status_verifikasi = 'menunggu'");
            mysqli_stmt_bind_param($stmt, "ii", $admin_id, $id_pembayaran_target);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) === 0) {
                throw new Exception("Gagal update pembayaran (mungkin sudah diproses).");
            }
            mysqli_stmt_close($stmt);

            // 2. Catat ke Tabel Keuangan
            $tanggal = date('Y-m-d');
            $sumber_fixed = $tipe_pembayaran_user; 
            $kategori = 'Pemasukan ' . $sumber_fixed . ' (Verifikasi)';
            $user_display = $booking['nama_user'] ?? 'Walk-in';
            $lap_display = $booking['nama_lapangan'] ?? 'N/A';
            $keterangan = "Verifikasi Pembayaran {$sumber_fixed} Booking #{$booking_id} - {$user_display} ({$lap_display})";

            // Cek apakah sudah ada entri keuangan
            $cek_keuangan_sql = "SELECT id_keuangan FROM keuangan WHERE pembayaran_id = ?";
            $stmt_cek_keuangan = mysqli_prepare($conn, $cek_keuangan_sql);
            mysqli_stmt_bind_param($stmt_cek_keuangan, "i", $id_pembayaran_target);
            mysqli_stmt_execute($stmt_cek_keuangan);
            $res_cek_keuangan = mysqli_stmt_get_result($stmt_cek_keuangan);
            $exists_keuangan = mysqli_fetch_assoc($res_cek_keuangan);
            mysqli_stmt_close($stmt_cek_keuangan);

            if ($exists_keuangan) {
                // UPDATE keuangan
                $update_keuangan_sql = "UPDATE keuangan SET tanggal=?, jenis='pemasukan', kategori=?, keterangan=?, jumlah=?, sumber=?, booking_id=?, updated_at=NOW() WHERE pembayaran_id=?";
                $stmt_upd_keu = mysqli_prepare($conn, $update_keuangan_sql);
                // "sssdsii" -> 7 param
                mysqli_stmt_bind_param($stmt_upd_keu, "sssdsii", $tanggal, $kategori, $keterangan, $amount_paid_now, $sumber_fixed, $booking_id, $id_pembayaran_target);
                mysqli_stmt_execute($stmt_upd_keu);
                mysqli_stmt_close($stmt_upd_keu);
            } else {
                // INSERT keuangan
                $insert_keuangan_sql = "INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, pembayaran_id, created_at, updated_at)
                                        VALUES (?, 'pemasukan', ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $stmt_ins_keu = mysqli_prepare($conn, $insert_keuangan_sql);
                mysqli_stmt_bind_param($stmt_ins_keu, "sssdsii", $tanggal, $kategori, $keterangan, $amount_paid_now, $sumber_fixed, $booking_id, $id_pembayaran_target);
                mysqli_stmt_execute($stmt_ins_keu);
                mysqli_stmt_close($stmt_ins_keu);
            }

            // 3. Hitung Ulang Total yang SUDAH dibayar
            $sum_sql = "SELECT COALESCE(SUM(amount),0) AS total_paid FROM pembayaran WHERE booking_id = ? AND status_verifikasi = 'valid'";
            $stmt_sum = mysqli_prepare($conn, $sum_sql);
            mysqli_stmt_bind_param($stmt_sum, "i", $booking_id);
            mysqli_stmt_execute($stmt_sum);
            $res_sum = mysqli_stmt_get_result($stmt_sum);
            $row = mysqli_fetch_assoc($res_sum);
            mysqli_stmt_close($stmt_sum);
            
            $total_paid_all = floatval($row['total_paid'] ?? 0);
            $total_amount_booking = floatval($booking['total_amount'] ?? 0);
            $remaining = max(0, $total_amount_booking - $total_paid_all);

            // 4. Tentukan Payment Status & Booking Status
            $payment_status = "belum_bayar";
            $new_booking_status = $booking['status'];

            if ($total_paid_all >= $total_amount_booking && $total_amount_booking > 0) {
                $payment_status = "lunas";
                $new_booking_status = 'disetujui';
            } elseif ($total_paid_all > 0) {
                $payment_status = "dp_bayar";
                $new_booking_status = 'belum lunas';
            } else {
                 $payment_status = "belum_bayar";
                 $new_booking_status = 'menunggu';
            }

            // 5. Update tabel booking
            $dp_amount_recorded = ($payment_status == 'dp_bayar') ? $total_paid_all : 0.00; 

            $stmt = mysqli_prepare($conn, "UPDATE booking SET remaining_amount = ?, payment_status = ?, status = ?, dp_amount = ?, updated_at = NOW() WHERE id_booking = ?");
            mysqli_stmt_bind_param($stmt, "dssdi", $remaining, $payment_status, $new_booking_status, $dp_amount_recorded, $booking_id);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            mysqli_commit($conn);

            if ($new_booking_status === 'belum lunas') {
                $_SESSION['success'] = "<b>Pembayaran {$tipe_pembayaran_user} Valid!</b> Data tercatat di keuangan. Status booking: <b>BELUM LUNAS</b>.<br>Sisa tagihan: Rp " . number_format($remaining,0,',','.');
            } else {
                $_SESSION['success'] = "<b>Pembayaran Lunas!</b> Data tercatat di keuangan. Status booking: <b>DISETUJUI</b>.";
            }

        // --- JIKA AKSI TOLAK ---
        } elseif ($aksi === 'tolak') {
            // 1. Update status di tabel pembayaran jadi 'tidak_valid'
            $stmt = mysqli_prepare($conn, "UPDATE pembayaran SET status_verifikasi='tidak_valid', verified_by=?, verified_at=NOW(), updated_at=NOW() WHERE id_pembayaran = ? AND status_verifikasi = 'menunggu'");
            mysqli_stmt_bind_param($stmt, "ii", $admin_id, $id_pembayaran_target);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);

            // 2. Hapus entri keuangan yang terkait (jika ada)
            $delete_keuangan_sql = "DELETE FROM keuangan WHERE pembayaran_id = ?";
            $stmt_del_keu = mysqli_prepare($conn, $delete_keuangan_sql);
            mysqli_stmt_bind_param($stmt_del_keu, "i", $id_pembayaran_target);
            mysqli_stmt_execute($stmt_del_keu);
            mysqli_stmt_close($stmt_del_keu);

            // 3. Update Status Booking menjadi DITOLAK
            // Kita tetap hitung remaining (sisa) untuk konsistensi data, 
            // tapi STATUS dipaksa jadi 'ditolak'.
            
            $sum_sql = "SELECT COALESCE(SUM(amount),0) AS total_paid FROM pembayaran WHERE booking_id = ? AND status_verifikasi = 'valid'";
            $stmt_sum = mysqli_prepare($conn, $sum_sql);
            mysqli_stmt_bind_param($stmt_sum, "i", $booking_id);
            mysqli_stmt_execute($stmt_sum);
            $res_sum = mysqli_stmt_get_result($stmt_sum);
            $row = mysqli_fetch_assoc($res_sum);
            mysqli_stmt_close($stmt_sum);
            
            $total_paid_after_reject = floatval($row['total_paid'] ?? 0);
            $total_amount_booking = floatval($booking['total_amount'] ?? 0);
            $remaining_after_reject = max(0, $total_amount_booking - $total_paid_after_reject);

            // SET STATUS MENJADI DITOLAK
            $new_booking_status_after_reject = 'ditolak'; 
            $payment_status_after_reject = 'dibatalkan'; 
            
            // DP amount disesuaikan dengan yang valid saja (biasanya 0 jika semua ditolak)
            $dp_amount_recorded_after_reject = $total_paid_after_reject;

            $stmt_upd_booking_reject = mysqli_prepare($conn, "UPDATE booking SET remaining_amount = ?, payment_status = ?, status = ?, dp_amount = ?, updated_at = NOW() WHERE id_booking = ?");
            mysqli_stmt_bind_param($stmt_upd_booking_reject, "dssdi", $remaining_after_reject, $payment_status_after_reject, $new_booking_status_after_reject, $dp_amount_recorded_after_reject, $booking_id);
            mysqli_stmt_execute($stmt_upd_booking_reject);
            mysqli_stmt_close($stmt_upd_booking_reject);

            mysqli_commit($conn);
            $_SESSION['success'] = "❌ Pembayaran ditolak. Status booking telah diubah menjadi <b>DITOLAK</b>.";
        }

    // =================================================================================
    // KASUS 2: ADMIN KLIK TOMBOL "PROSES PELUNASAN" (Manual / Bayar di Tempat)
    // =================================================================================
    } elseif ($aksi === 'pelunasan') {
        $booking_id = intval($_GET['booking_id'] ?? 0);
        if (!$booking_id) throw new Exception("Booking ID tidak valid untuk pelunasan.");

        // Ambil data booking
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

        $total_amount_booking = floatval($booking['total_amount'] ?? 0);

        // Hitung yang sudah dibayar
        $sum_sql = "SELECT COALESCE(SUM(amount),0) AS total_paid FROM pembayaran WHERE booking_id = ? AND status_verifikasi = 'valid'";
        $stmt_sum = mysqli_prepare($conn, $sum_sql);
        mysqli_stmt_bind_param($stmt_sum, "i", $booking_id);
        mysqli_stmt_execute($stmt_sum);
        $res_sum = mysqli_stmt_get_result($stmt_sum);
        $row = mysqli_fetch_assoc($res_sum);
        mysqli_stmt_close($stmt_sum);
        $total_paid_before = floatval($row['total_paid'] ?? 0);

        $remaining_to_pay = max(0, $total_amount_booking - $total_paid_before);
        
        if ($remaining_to_pay <= 0) {
             $stmt_upd = mysqli_prepare($conn, "UPDATE booking SET remaining_amount = 0, payment_status = 'lunas', status = 'disetujui', updated_at = NOW() WHERE id_booking = ?");
             mysqli_stmt_bind_param($stmt_upd, "i", $booking_id);
             mysqli_stmt_execute($stmt_upd);
             mysqli_stmt_close($stmt_upd);
             mysqli_commit($conn);
             $_SESSION['success'] = "Booking #{$booking_id} sudah lunas sebelumnya. Status diperbarui menjadi DISETUJUI.";
             header("Location: pembayaran.php");
             exit;
        }
        
        // 1. Buat record pembayaran baru (Langsung Valid)
        $tipe = 'Pelunasan';
        $method = 'Tunai (Offline)';
        $status_ver = 'valid';
        
        $insert_p_sql = "INSERT INTO pembayaran (booking_id, tipe, bukti_pembayaran, amount, method, status_verifikasi, verified_by, verified_at, tanggal_upload, created_at, updated_at)
                         VALUES (?, ?, NULL, ?, ?, ?, ?, NOW(), NOW(), NOW(), NOW())";
        $stmt_ins = mysqli_prepare($conn, $insert_p_sql);
        mysqli_stmt_bind_param($stmt_ins, "isdssi", $booking_id, $tipe, $remaining_to_pay, $method, $status_ver, $admin_id);
        
        if (!mysqli_stmt_execute($stmt_ins)) {
            throw new Exception("Gagal insert pembayaran pelunasan.");
        }
        $new_payment_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt_ins);

        // 2. Catat ke Keuangan
        $tanggal = date('Y-m-d');
        $sumber_fixed = 'Pelunasan';
        $kategori = 'Pemasukan Pelunasan';
        $user_display = $booking['nama_user'] ?? 'Walk-in';
        $lap_display = $booking['nama_lapangan'] ?? 'N/A';
        $keterangan = "Pelunasan di Lokasi #{$booking_id} - {$user_display} ({$lap_display})";

        $insert_keuangan_sql = "INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, pembayaran_id, created_at, updated_at)
                                VALUES (?, 'pemasukan', ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt_insk = mysqli_prepare($conn, $insert_keuangan_sql);
        mysqli_stmt_bind_param($stmt_insk, "sssdsii", $tanggal, $kategori, $keterangan, $remaining_to_pay, $sumber_fixed, $booking_id, $new_payment_id);
        mysqli_stmt_execute($stmt_insk);
        mysqli_stmt_close($stmt_insk);

        // 3. Update Booking: Sisa 0, Lunas, DISETUJUI
        $stmt_upd = mysqli_prepare($conn, "UPDATE booking SET remaining_amount = 0, payment_status = 'lunas', status = 'disetujui', dp_amount = ?, updated_at = NOW() WHERE id_booking = ?");
        mysqli_stmt_bind_param($stmt_upd, "di", $total_amount_booking, $booking_id);
        mysqli_stmt_execute($stmt_upd);
        mysqli_stmt_close($stmt_upd);

        mysqli_commit($conn);
        $_SESSION['success'] = "✅ <b>Pelunasan Manual Berhasil!</b> Sisa tagihan Rp " . number_format($remaining_to_pay,0,',','.') . " telah dibayar. Status booking berubah menjadi <b>DISETUJUI</b>.";

    } else {
        throw new Exception("Aksi tidak dikenali.");
    }
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = "Gagal memproses: " . $e->getMessage();
}

header("Location: pembayaran.php");
exit;
?>