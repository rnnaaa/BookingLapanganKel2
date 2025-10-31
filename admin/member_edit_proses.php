<?php
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id_member = $_POST['id_member'];
  $id_user = $_POST['id_user'];
  $jenis = $_POST['jenis_member'];
  $tgl_mulai = $_POST['tgl_mulai'];
  $tgl_berakhir = $_POST['tgl_berakhir'];
  $status = $_POST['status'];
  $ket = $_POST['keterangan'];

  mysqli_begin_transaction($conn);
  try {
    // Update data member
    mysqli_query($conn, "UPDATE member 
                         SET id_user='$id_user', jenis_member='$jenis', 
                             tgl_mulai='$tgl_mulai', tgl_berakhir='$tgl_berakhir', 
                             status='$status', keterangan='$ket'
                         WHERE id_member='$id_member'");

    // Hapus jadwal lama
    mysqli_query($conn, "DELETE FROM member_jadwal WHERE id_member='$id_member'");

    // Insert jadwal baru
    $hari = $_POST['hari'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $id_lapangan = $_POST['id_lapangan'];

    for ($i = 0; $i < count($hari); $i++) {
      mysqli_query($conn, "INSERT INTO member_jadwal (id_member, hari, jam_mulai, jam_selesai, id_lapangan, status)
                           VALUES ('$id_member', '{$hari[$i]}', '{$jam_mulai[$i]}', '{$jam_selesai[$i]}', '{$id_lapangan[$i]}', 'aktif')");
    }

    mysqli_commit($conn);
    $_SESSION['toast_success'] = "Perubahan data member berhasil disimpan!";
    header("Location: member.php");
  } catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['toast_error'] = "Gagal menyimpan perubahan: " . $e->getMessage();
    header("Location: member_edit.php?id=$id_member");
  }
}
?>
