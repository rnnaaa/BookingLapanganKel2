<?php
require_once __DIR__ . '/../config/database.php';
session_start();

$id_user = $_POST['id_user'];
$id_lapangan = $_POST['id_lapangan'];
$tanggal = $_POST['tanggal'];
$payment_method = $_POST['payment_method'];
$total = str_replace('.', '', $_POST['total']);
$jam = $_POST['jam'] ?? [];

if (!$id_user || !$id_lapangan || !$tanggal || empty($jam)) {
  $_SESSION['toast_error'] = "Lengkapi semua data booking!";
  header("Location: booking_tambah.php");
  exit;
}

mysqli_begin_transaction($conn);

try {
  // Simpan ke tabel booking
  $q = "INSERT INTO booking (id_user, id_lapangan, tanggal, total_amount, status, payment_method, created_at)
        VALUES ('$id_user', '$id_lapangan', '$tanggal', '$total', 'menunggu', '$payment_method', NOW())";
  mysqli_query($conn, $q);
  $id_booking = mysqli_insert_id($conn);

  // Ambil harga per jam lapangan
  $h = mysqli_fetch_assoc(mysqli_query($conn, "SELECT harga_per_jam FROM lapangan WHERE id_lapangan='$id_lapangan'"));
  $harga = $h['harga_per_jam'];

  // Simpan ke detail_booking
  foreach ($jam as $id_jadwal) {
    $subtotal = $harga;
    mysqli_query($conn, "
      INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga_per_jam, subtotal)
      VALUES ('$id_booking', '$id_jadwal', '$harga', '$subtotal')
    ");
  }

  mysqli_commit($conn);
  $_SESSION['toast_success'] = "Booking baru berhasil disimpan!";
} catch (Exception $e) {
  mysqli_rollback($conn);
  $_SESSION['toast_error'] = "Terjadi kesalahan: " . $e->getMessage();
}

header("Location: booking.php");
exit;
