<?php
require_once __DIR__ . '/../config/database.php';
session_start();

$id_pembayaran = intval($_GET['id'] ?? 0);
$aksi = $_GET['aksi'] ?? '';

if (!$id_pembayaran || !in_array($aksi, ['valid', 'tolak'])) {
  $_SESSION['toast_error'] = "Parameter tidak valid!";
  header("Location: pembayaran.php");
  exit;
}

$q = mysqli_query($conn, "
  SELECT p.*, b.id_booking, b.payment_status, b.status AS status_booking
  FROM pembayaran p
  JOIN booking b ON p.booking_id = b.id_booking
  WHERE p.id_pembayaran = '$id_pembayaran'
");
$p = mysqli_fetch_assoc($q);

if (!$p) {
  $_SESSION['toast_error'] = "Data pembayaran tidak ditemukan!";
  header("Location: pembayaran.php");
  exit;
}

mysqli_begin_transaction($conn);
try {
  if ($aksi === 'valid') {
    mysqli_query($conn, "UPDATE pembayaran SET status_verifikasi='valid', verified_at=NOW() WHERE id_pembayaran='$id_pembayaran'");
    if ($p['tipe'] === 'DP') {
      mysqli_query($conn, "UPDATE booking SET payment_status='dp_bayar', status='disetujui', updated_at=NOW() WHERE id_booking='{$p['id_booking']}'");
    } else {
      mysqli_query($conn, "UPDATE booking SET payment_status='lunas', status='selesai', updated_at=NOW() WHERE id_booking='{$p['id_booking']}'");
    }
  } else {
    mysqli_query($conn, "UPDATE pembayaran SET status_verifikasi='tidak_valid', verified_at=NOW() WHERE id_pembayaran='$id_pembayaran'");
    mysqli_query($conn, "UPDATE booking SET payment_status='menunggu_verifikasi', status='menunggu', updated_at=NOW() WHERE id_booking='{$p['id_booking']}'");
  }

  mysqli_commit($conn);
  $_SESSION['toast_success'] = "Status pembayaran berhasil diperbarui.";
} catch (Exception $e) {
  mysqli_rollback($conn);
  $_SESSION['toast_error'] = "Terjadi kesalahan: " . $e->getMessage();
}
header("Location: pembayaran.php");
exit;
?>
