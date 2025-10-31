<?php
require_once __DIR__ . '/../config/database.php';
session_start();

// Pastikan ada ID yang dikirim
if (!isset($_GET['id'])) {
  $_SESSION['toast_error'] = "ID lapangan tidak ditemukan.";
  header("Location: lapangan.php");
  exit;
}

$id = intval($_GET['id']);

// Cek apakah data ada
$query = mysqli_query($conn, "SELECT * FROM lapangan WHERE id_lapangan = $id");
if (mysqli_num_rows($query) === 0) {
  $_SESSION['toast_error'] = "Data lapangan tidak ditemukan.";
  header("Location: lapangan.php");
  exit;
}

$data = mysqli_fetch_assoc($query);
$fotoPath = "../uploads/lapangan/" . $data['foto'];

// Hapus dari database
$delete = mysqli_query($conn, "DELETE FROM lapangan WHERE id_lapangan = $id");

if ($delete) {
  // Hapus foto jika ada
  if (!empty($data['foto']) && file_exists($fotoPath)) {
    unlink($fotoPath);
  }

  $_SESSION['toast_success'] = "Data lapangan berhasil dihapus.";
  header("Location: lapangan.php");
  exit;
} else {
  $_SESSION['toast_error'] = "Gagal menghapus data lapangan.";
  header("Location: lapangan.php");
  exit;
}
?>
