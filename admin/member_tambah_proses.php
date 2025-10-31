<?php
require_once __DIR__ . '/../config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_user = $_POST['id_user'];
  $jenis = $_POST['jenis_member'];
  $tgl_mulai = $_POST['tgl_mulai'];
  $tgl_berakhir = $_POST['tgl_berakhir'];
  $status = $_POST['status'];
  $ket = $_POST['keterangan'];

  mysqli_begin_transaction($conn);
  try {
    // Cegah duplikasi member aktif
    $cek = mysqli_query($conn, "SELECT * FROM member WHERE id_user = '$id_user' AND status = 'aktif'");
    if (mysqli_num_rows($cek) > 0) {
      throw new Exception("User ini sudah memiliki membership aktif!");
    }

    // Tambah member baru
    mysqli_query($conn, "INSERT INTO member (id_user, jenis_member, tgl_mulai, tgl_berakhir, status, keterangan, created_at)
                         VALUES ('$id_user', '$jenis', '$tgl_mulai', '$tgl_berakhir', '$status', '$ket', NOW())");
    $id_member = mysqli_insert_id($conn);

    // Simpan jadwal mingguannya
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $id_lapangan = $_POST['id_lapangan'];

    for ($i = 0; $i < count($hari); $i++) {
      mysqli_query($conn, "INSERT INTO member_jadwal (id_member, hari, jam_mulai, jam_selesai, id_lapangan, status, created_at)
                           VALUES ('$id_member', '{$hari[$i]}', '{$jam_mulai[$i]}', '{$jam_selesai[$i]}', '{$id_lapangan[$i]}', 'aktif', NOW())");
    }

    mysqli_commit($conn);
    $_SESSION['toast_success'] = "Member baru berhasil ditambahkan!";
    header("Location: member.php");
  } catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['toast_error'] = "Gagal menambahkan member: " . $e->getMessage();
    header("Location: member_tambah.php");
  }
}
?>
