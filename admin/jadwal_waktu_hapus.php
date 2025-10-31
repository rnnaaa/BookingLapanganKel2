<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_GET['id'])) {
  $_SESSION['toast_error'] = 'ID jadwal tidak ditemukan.';
  header('Location: jadwal_waktu.php');
  exit;
}

$id = $_GET['id'];

// Ambil id_lapangan terkait
$get = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id_lapangan FROM jadwal_waktu WHERE id_jadwal_waktu='$id'"));
$id_lapangan = $get['id_lapangan'] ?? null;

if (!$id_lapangan) {
  $_SESSION['toast_error'] = 'Data tidak ditemukan.';
  header('Location: jadwal_waktu.php');
  exit;
}

// Hapus jadwal waktu
mysqli_query($conn, "DELETE FROM jadwal_waktu WHERE id_jadwal_waktu='$id'");

// Opsional: update status jadwal_harian (karena slot waktu berkurang)
mysqli_query($conn, "
  UPDATE jadwal_harian 
  SET status_hari='tersedia'
  WHERE id_lapangan='$id_lapangan' 
  AND status_hari='penuh'
");

// Logika: bila semua slot terhapus, status_hari bisa dikembalikan ke 'libur' jika diinginkan.

$_SESSION['toast_success'] = 'Jadwal waktu berhasil dihapus dan sinkron dengan jadwal harian.';
header('Location: jadwal_waktu.php');
exit;
