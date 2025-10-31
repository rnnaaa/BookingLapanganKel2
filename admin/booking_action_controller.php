<?php
require_once __DIR__ . '/../config/database.php';
session_start();

$action = $_GET['action'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (!$id) {
  $_SESSION['toast_error'] = "ID Booking tidak ditemukan!";
  header("Location: booking.php");
  exit;
}

// Cek apakah booking ada
$cek = mysqli_query($conn, "SELECT * FROM booking WHERE id_booking=$id");
if (mysqli_num_rows($cek) == 0) {
  $_SESSION['toast_error'] = "Data booking tidak ditemukan!";
  header("Location: booking.php");
  exit;
}

// Proses aksi berdasarkan parameter
switch ($action) {
  case 'approve':
    mysqli_query($conn, "
      UPDATE booking SET 
        status='disetujui', 
        updated_at=NOW() 
      WHERE id_booking=$id
    ");
    $_SESSION['toast_success'] = "Booking disetujui ✅";
    break;

  case 'reject':
    mysqli_query($conn, "
      UPDATE booking SET 
        status='ditolak', 
        updated_at=NOW() 
      WHERE id_booking=$id
    ");
    $_SESSION['toast_error'] = "Booking ditolak ❌";
    break;

  case 'delete':
    // Hapus detail_booking dan pembayaran
    mysqli_query($conn, "DELETE FROM detail_booking WHERE id_booking=$id");
    mysqli_query($conn, "DELETE FROM pembayaran WHERE booking_id=$id");
    mysqli_query($conn, "DELETE FROM booking WHERE id_booking=$id");
    $_SESSION['toast_success'] = "Booking dihapus 🗑️";
    break;

  default:
    $_SESSION['toast_error'] = "Aksi tidak dikenal!";
    break;
}

header("Location: booking.php");
exit;
?>
