<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan']);
  exit;
}

$id = intval($_POST['id']);
$status = mysqli_real_escape_string($conn, $_POST['status']);

if (empty($id) || empty($status)) {
  echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
  exit;
}

$query = "UPDATE member SET status = '$status' WHERE id_member = '$id'";
if (mysqli_query($conn, $query)) {
  echo json_encode(['success' => true, 'message' => "Status member diubah menjadi $status."]);
} else {
  echo json_encode(['success' => false, 'message' => 'Gagal memperbarui status.']);
}
?>
