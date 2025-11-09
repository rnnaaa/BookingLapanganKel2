<?php
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_member = $_POST['id_member'];
    $id_lapangan = $_POST['id_lapangan'];
    $tanggal = $_POST['tanggal_booking'];
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];

    // Cek lapangan dan harga member
    $lap = mysqli_query($conn, "SELECT harga_per_jam_member FROM lapangan WHERE id_lapangan = '$id_lapangan'");
    $harga = ($lap && mysqli_num_rows($lap) > 0) ? mysqli_fetch_assoc($lap)['harga_per_jam_member'] : 0;

    // Validasi jadwal bentrok
    $cek = mysqli_query($conn, "SELECT * FROM member_jadwal 
        WHERE id_lapangan = '$id_lapangan'
        AND tanggal_booking = '$tanggal'
        AND ('$jam_mulai' BETWEEN jam_mulai AND jam_selesai OR '$jam_selesai' BETWEEN jam_mulai AND jam_selesai)");
    if (mysqli_num_rows($cek) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Jadwal bentrok dengan jadwal lain di lapangan ini.']);
        exit;
    }

    // Simpan data
    $query = "INSERT INTO member_jadwal (id_member, id_lapangan, tanggal_booking, jam_mulai, jam_selesai, harga_per_jam_member, status)
              VALUES ('$id_member', '$id_lapangan', '$tanggal', '$jam_mulai', '$jam_selesai', '$harga', 'aktif')";
    if (mysqli_query($conn, $query)) {
        echo json_encode(['status' => 'success', 'message' => 'Jadwal berhasil ditambahkan!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal menambahkan jadwal: ' . mysqli_error($conn)]);
    }
}
?>
