<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if (!isset($_GET['id'])) {
  $_SESSION['toast_error'] = "ID member tidak ditemukan!";
  header("Location: member.php");
  exit;
}

$id = intval($_GET['id']);

mysqli_begin_transaction($conn);
try {
  mysqli_query($conn, "DELETE FROM member_jadwal WHERE id_member = '$id'");
  mysqli_query($conn, "DELETE FROM member WHERE id_member = '$id'");
  mysqli_commit($conn);
  $_SESSION['toast_success'] = "Data member berhasil dihapus!";
} catch (Exception $e) {
  mysqli_rollback($conn);
  $_SESSION['toast_error'] = "Gagal menghapus member: " . $e->getMessage();
}
header("Location: member.php");
exit;
?>
