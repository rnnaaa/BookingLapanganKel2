<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Pastikan parameter ID dikirim
if (!isset($_GET['id'])) {
  $_SESSION['toast_error'] = "⚠️ ID jadwal tidak ditemukan!";
  header("Location: member_jadwal.php");
  exit;
}

$id = intval($_GET['id']);

// Cek apakah data jadwal ada
$cek = mysqli_query($conn, "SELECT * FROM member_jadwal WHERE id_member_jadwal = '$id'");
if (mysqli_num_rows($cek) == 0) {
  $_SESSION['toast_error'] = "⚠️ Jadwal tidak ditemukan!";
  header("Location: member_jadwal.php");
  exit;
}

// Hapus data
$hapus = mysqli_query($conn, "DELETE FROM member_jadwal WHERE id_member_jadwal = '$id'");

if ($hapus) {
  $_SESSION['toast_success'] = "✅ Jadwal rutin berhasil dihapus!";
} else {
  $_SESSION['toast_error'] = "❌ Terjadi kesalahan, data gagal dihapus!";
}

header("Location: member_jadwal.php");
exit;
