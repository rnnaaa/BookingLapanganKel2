<?php
// pembayaran_validasi.php - PERBAIKAN LOGIKA
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/database.php';
session_start();

$id        = intval($_GET['id'] ?? 0);      // id_pembayaran
$aksi      = $_GET['aksi'] ?? '';           // valid / tolak
$admin_id  = $_SESSION['id_user'] ?? 0;     // User yang memverifikasi

if (!$id || !$aksi) {
  $_SESSION['error'] = "ID pembayaran atau aksi tidak valid.";
  header("Location: pembayaran.php");
  exit;
}

// Ambil pembayaran
$sql_p = "SELECT * FROM pembayaran WHERE id_pembayaran = ? LIMIT 1";
$stmt_p = mysqli_prepare($conn, $sql_p);
mysqli_stmt_bind_param($stmt_p, "i", $id);
mysqli_stmt_execute($stmt_p);
$res_p = mysqli_stmt_get_result($stmt_p);
$p = mysqli_fetch_assoc($res_p);
mysqli_stmt_close($stmt_p);

if (!$p) {
  $_SESSION['error'] = "Data pembayaran tidak ditemukan.";
  header("Location: pembayaran.php");
  exit;
}

if ($p['status_verifikasi'] !== 'menunggu') {
  $_SESSION['error'] = "Pembayaran sudah divalidasi / ditolak.";
  header("Location: pembayaran.php");
  exit;
}

$booking_id      = intval($p['booking_id']);
$amount          = floatval($p['amount']);
$tipe_pembayaran = $p['tipe']; // 'DP' / 'Pelunasan'

// Ambil booking
$sql_b = "SELECT b.*, u.nama AS nama_user, l.nama_lapangan FROM booking b JOIN users u ON b.id_user=u.id_user JOIN lapangan l ON b.id_lapangan=l.id_lapangan WHERE b.id_booking = ? LIMIT 1";
$stmt_b = mysqli_prepare($conn, $sql_b);
mysqli_stmt_bind_param($stmt_b, "i", $booking_id);
mysqli_stmt_execute($stmt_b);
$res_b = mysqli_stmt_get_result($stmt_b);
$booking = mysqli_fetch_assoc($res_b);
mysqli_stmt_close($stmt_b);

if (!$booking) {
  $_SESSION['error'] = "Data booking tidak ditemukan.";
  header("Location: pembayaran.php");
  exit;
}

$total_amount = floatval($booking['total_amount']);

// Mulai transaksi
mysqli_begin_transaction($conn);

try {
  if ($aksi === 'valid') {
    // 1) Tandai pembayaran jadi valid
    $stmt = mysqli_prepare($conn, "UPDATE pembayaran SET status_verifikasi='valid', verified_by=?, verified_at=NOW(), updated_at=NOW() WHERE id_pembayaran = ? AND status_verifikasi = 'menunggu'");
    mysqli_stmt_bind_param($stmt, "ii", $admin_id, $id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_affected_rows($stmt) === 0) {
      throw new Exception("Gagal update pembayaran (mungkin sudah diproses).");
    }
    mysqli_stmt_close($stmt);

    // 2) Hitung total yang sudah valid untuk booking
    $sum_sql = "SELECT COALESCE(SUM(amount),0) AS total_paid FROM pembayaran WHERE booking_id = ? AND status_verifikasi = 'valid'";
    $stmt_sum = mysqli_prepare($conn, $sum_sql);
    mysqli_stmt_bind_param($stmt_sum, "i", $booking_id);
    mysqli_stmt_execute($stmt_sum);
    $res_sum = mysqli_stmt_get_result($stmt_sum);
    $row = mysqli_fetch_assoc($res_sum);
    mysqli_stmt_close($stmt_sum);
    $total_paid = floatval($row['total_paid'] ?? 0);

    // 3) Hitung remaining
    $remaining = max(0, $total_amount - $total_paid);

    // 4) Tentukan payment_status
    if ($total_paid <= 0) $payment_status = "belum_bayar";
    elseif ($total_paid < $total_amount) $payment_status = "dp_bayar";
    else $payment_status = "lunas";

    // 5) Update booking: remaining_amount, payment_status, dan jika lunas set disetujui
    $new_status = $booking['status'];
    if ($payment_status === 'lunas') $new_status = 'disetujui';

    $stmt = mysqli_prepare($conn, "UPDATE booking SET remaining_amount = ?, payment_status = ?, status = ?, updated_at = NOW() WHERE id_booking = ?");
    mysqli_stmt_bind_param($stmt, "dssi", $remaining, $payment_status, $new_status, $booking_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // 6) KEUANGAN: hanya jika tipe = Pelunasan -> masukkan 1x jumlah keseluruhan (total_amount), cek anti-duplikat
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
        $keterangan = "Pembayaran Pelunasan Booking #{$booking_id} - {$booking['nama_user']} ({$booking['nama_lapangan']})";
        $sumber = 'Pelunasan';

        $insert_sql = "INSERT INTO keuangan (tanggal, jenis, kategori, keterangan, jumlah, sumber, booking_id, pembayaran_id, created_at)
                       VALUES (?, 'pemasukan', ?, ?, ?, ?, ?, ?, NOW())";
        $stmt_ins = mysqli_prepare($conn, $insert_sql);
        // params: tanggal (s), kategori (s), keterangan (s), jumlah (d), sumber (s), booking_id (i), pembayaran_id (i)
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
    $stmt = mysqli_prepare($conn, "UPDATE pembayaran SET status_verifikasi='tidak_valid', verified_by=?, verified_at=NOW(), updated_at=NOW() WHERE id_pembayaran = ? AND status_verifikasi = 'menunggu'");
    mysqli_stmt_bind_param($stmt, "ii", $admin_id, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conn);
    $_SESSION['success'] = "❌ Pembayaran ditolak. User harus upload ulang bukti.";
  } else {
    throw new Exception("Aksi tidak dikenali.");
  }
} catch (Exception $e) {
  mysqli_rollback($conn);
  $_SESSION['error'] = "Gagal memproses pembayaran: " . $e->getMessage();
}

header("Location: pembayaran.php");
exit;
