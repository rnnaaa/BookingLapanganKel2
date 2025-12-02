<?php
// member_jadwal_get_slot.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

$id_lapangan = intval($_GET['id_lapangan'] ?? 0);
$tanggal = $_GET['tanggal'] ?? '';

if (!$id_lapangan || !$tanggal) {
    echo json_encode(['status'=>'error','message'=>'Parameter tidak lengkap.']);
    exit;
}

$current_date = date('Y-m-d');
$current_time = date('H:i:s');

// 1. Validasi Tanggal Lewat (Security Layer 1)
if ($tanggal < $current_date) {
    echo json_encode(['status'=>'error','message'=>'Tanggal sudah lewat tidak dapat dipilih.']);
    exit;
}

// Cek jadwal harian
$stmt = $conn->prepare("
    SELECT id_jadwal_harian 
    FROM jadwal_harian 
    WHERE id_lapangan=? AND tanggal=?
    LIMIT 1
");
$stmt->bind_param("is", $id_lapangan, $tanggal);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['status'=>'error','message'=>'Belum ada jadwal untuk tanggal ini.']);
    exit;
}

$id_jadwal_harian = $row['id_jadwal_harian'];

$stmt = $conn->prepare("
    SELECT jd.id_detail, jw.jam_mulai, jw.jam_selesai, jd.status
    FROM jadwal_detail jd
    JOIN jadwal_waktu jw ON jd.id_jadwal_waktu = jw.id_jadwal_waktu
    WHERE jd.id_jadwal_harian = ?
    ORDER BY jw.jam_mulai ASC
");
$stmt->bind_param("i", $id_jadwal_harian);
$stmt->execute();
$res = $stmt->get_result();

$slots = [];
while ($r = $res->fetch_assoc()) {
    $status_final = $r['status'];
    
    // 2. Logika Cek Jam Lewat
    // Jika tanggal booking == hari ini DAN jam mulai slot < jam sekarang
    if ($tanggal === $current_date && $r['jam_mulai'] < $current_time) {
        $status_final = 'lewat'; // Status khusus untuk frontend
    }

    $slots[] = [
        'id_detail' => $r['id_detail'],
        'jam_mulai' => substr($r['jam_mulai'],0,5),
        'jam_selesai'=> substr($r['jam_selesai'],0,5),
        'status' => $status_final
    ];
}

$stmt->close();

echo json_encode(['status'=>'success','slots'=>$slots]);