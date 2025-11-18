<?php
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

$id_lapangan = intval($_GET['id_lapangan'] ?? 0);
$tanggal = $_GET['tanggal'] ?? '';

if (!$id_lapangan || !$tanggal) {
    echo json_encode(['status'=>'error','message'=>'Parameter tidak lengkap.']);
    exit;
}

// cek jadwal harian
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
    echo json_encode(['status'=>'error','message'=>'Belum ada jadwal untuk tanggal ini. Jalankan sinkronisasi.']);
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
    $slots[] = [
        'id_detail' => $r['id_detail'],
        'jam_mulai' => substr($r['jam_mulai'],0,5),
        'jam_selesai'=> substr($r['jam_selesai'],0,5),
        'status' => $r['status']
    ];
}

$stmt->close();

echo json_encode(['status'=>'success','slots'=>$slots]);
