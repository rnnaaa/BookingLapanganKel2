<?php
require_once __DIR__ . '/../config/database.php';
session_start();

// Pastikan parameter ada
if (!isset($_GET['id']) || !isset($_GET['aksi'])) {
  $_SESSION['toast_error'] = "Parameter tidak lengkap!";
  header("Location: pembayaran.php");
  exit;
}

$id = intval($_GET['id']);
$aksi = $_GET['aksi'];

// Ambil data pembayaran
$pembayaran = mysqli_query($conn, "
  SELECT * FROM pembayaran WHERE id_pembayaran = $id
");
$data = mysqli_fetch_assoc($pembayaran);

if (!$data) {
  $_SESSION['toast_error'] = "Data pembayaran tidak ditemukan!";
  header("Location: pembayaran.php");
  exit;
}

// Ambil ID booking untuk update status
$id_booking = $data['booking_id'];

// Proses aksi
if ($aksi === 'valid') {
  mysqli_query($conn, "
    UPDATE pembayaran SET 
      status_verifikasi='valid', 
      tanggal_verifikasi=NOW()
    WHERE id_pembayaran=$id
  ");

  // Update status booking sesuai tipe pembayaran
  if ($data['tipe'] === 'DP') {
    mysqli_query($conn, "UPDATE booking SET payment_status='dp_bayar' WHERE id_booking=$id_booking");
  } elseif ($data['tipe'] === 'Pelunasan') {
    mysqli_query($conn, "UPDATE booking SET payment_status='lunas', status='disetujui' WHERE id_booking=$id_booking");
  }

  $_SESSION['toast_success'] = "Pembayaran berhasil divalidasi ✅";
} 
elseif ($aksi === 'tolak') {
  mysqli_query($conn, "
    UPDATE pembayaran SET 
      status_verifikasi='tidak_valid', 
      tanggal_verifikasi=NOW()
    WHERE id_pembayaran=$id
  ");
  $_SESSION['toast_error'] = "Pembayaran ditolak ❌";
}

header("Location: pembayaran.php");
exit;
?>
