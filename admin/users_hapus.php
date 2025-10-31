<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Cek apakah parameter id ada
if (!isset($_GET['id']) || empty($_GET['id'])) {
  $_SESSION['toast_error'] = "ID pengguna tidak valid.";
  header("Location: users.php");
  exit;
}

$id = intval($_GET['id']); // pastikan hanya angka

// Cek apakah data user masih ada
$check = mysqli_query($conn, "SELECT * FROM users WHERE id_user = '$id'");
if (mysqli_num_rows($check) === 0) {
  $_SESSION['toast_error'] = "Pengguna tidak ditemukan.";
  header("Location: users.php");
  exit;
}

// Jalankan proses hapus
$delete = mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id'");

if ($delete) {
  $_SESSION['toast_success'] = "Data pengguna berhasil dihapus!";
} else {
  $_SESSION['toast_error'] = "Gagal menghapus data pengguna!";
}

header("Location: users.php");
exit;
