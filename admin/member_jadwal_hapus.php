<?php
require_once __DIR__ . '/../config/database.php';
$id = $_POST['id'] ?? 0;

if ($id == 0) {
  echo json_encode(["status" => "error", "message" => "ID tidak valid."]);
  exit;
}

$hapus = mysqli_query($conn, "DELETE FROM member_jadwal WHERE id_member_jadwal='$id'");
if ($hapus) {
  echo json_encode(["status" => "success", "message" => "Jadwal berhasil dihapus."]);
} else {
  echo json_encode(["status" => "error", "message" => "Gagal menghapus data."]);
}
