<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
session_start();

date_default_timezone_set('Asia/Jakarta');

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Silakan login terlebih dahulu.']);
    exit;
}

$id_user = $_SESSION['user_id'];
$id_lapangan = intval($data['id_lapangan']);
$tanggal = $data['tanggal_booking'];
$jam_mulai = $data['jam_mulai'];
$jam_selesai = $data['jam_selesai'];

try {
    // ambil id_member dari tabel member
    $res = $conn->query("SELECT id_member FROM member WHERE id_user = $id_user AND status='aktif'");
    if ($res->num_rows == 0) {
        echo json_encode(['status' => 'error', 'message' => 'Akun ini belum terdaftar sebagai member aktif.']);
        exit;
    }
    $row = $res->fetch_assoc();
    $id_member = $row['id_member'];

    // cek apakah slot sudah dibooking
    $cek = $conn->prepare("
        SELECT COUNT(*) AS jml FROM member_jadwal 
        WHERE id_lapangan=? AND tanggal_booking=? 
        AND jam_mulai=? AND jam_selesai=?
    ");
    $cek->bind_param('isss', $id_lapangan, $tanggal, $jam_mulai, $jam_selesai);
    $cek->execute();
    $count = $cek->get_result()->fetch_assoc()['jml'];

    if ($count > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Slot ini sudah dibooking.']);
        exit;
    }

    // simpan booking baru
    $stmt = $conn->prepare("
        INSERT INTO member_jadwal (id_member, id_lapangan, tanggal_booking, jam_mulai, jam_selesai, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'aktif', NOW())
    ");
    $stmt->bind_param('iisss', $id_member, $id_lapangan, $tanggal, $jam_mulai, $jam_selesai);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Slot berhasil dibooking.']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Kesalahan: ' . $e->getMessage()]);
}
?>
