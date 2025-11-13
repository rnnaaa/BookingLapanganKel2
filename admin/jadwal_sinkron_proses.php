<?php
session_start();
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: jadwal_sinkronisasi.php");
    exit;
}

try {
    $conn->begin_transaction();

    $hari_map = [
        'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis',
        'Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
    ];

    // === STEP 1: Pastikan jam_operasional ada ===
    $jam_operasional_default = [
        ['Senin','07:00:00','23:00:00'],['Selasa','07:00:00','23:00:00'],
        ['Rabu','07:00:00','23:00:00'],['Kamis','07:00:00','23:00:00'],
        ['Jumat','07:00:00','23:00:00'],['Sabtu','07:00:00','23:00:00'],
        ['Minggu','08:00:00','22:00:00']
    ];
    foreach ($jam_operasional_default as $j) {
        [$hari, $buka, $tutup] = $j;
        $cek = $conn->query("SELECT id_operasional FROM jam_operasional WHERE hari='$hari'");
        if ($cek->num_rows == 0) {
            $stmt = $conn->prepare("INSERT INTO jam_operasional (hari,jam_buka,jam_tutup,created_at) VALUES (?,?,?,NOW())");
            $stmt->bind_param("sss", $hari, $buka, $tutup);
            $stmt->execute();
        }
    }

    // === STEP 2: Hapus jadwal lama yang sudah lewat ===
    $conn->query("DELETE FROM jadwal_harian WHERE tanggal < CURDATE()");

    // === STEP 3: Ambil data yang ada ===
    $lapangan = $conn->query("SELECT id_lapangan FROM lapangan WHERE status='aktif'")->fetch_all(MYSQLI_ASSOC);

    $jam_ops = [];
    $r = $conn->query("SELECT * FROM jam_operasional");
    while($d=$r->fetch_assoc()) $jam_ops[$d['hari']] = $d;

    $existing_harian = [];
    $r = $conn->query("SELECT id_jadwal_harian,id_lapangan,tanggal FROM jadwal_harian");
    while($d=$r->fetch_assoc()) $existing_harian[$d['id_lapangan'].'|'.$d['tanggal']] = $d['id_jadwal_harian'];

    $existing_waktu = [];
    $r = $conn->query("SELECT id_jadwal_waktu,id_lapangan,jam_mulai,jam_selesai FROM jadwal_waktu");
    while($d=$r->fetch_assoc()) $existing_waktu[$d['id_lapangan'].'|'.$d['jam_mulai'].'|'.$d['jam_selesai']] = $d['id_jadwal_waktu'];

    $existing_detail = [];
    $r = $conn->query("SELECT id_jadwal_harian,id_jadwal_waktu FROM jadwal_detail");
    while($d=$r->fetch_assoc()) $existing_detail[$d['id_jadwal_harian'].'|'.$d['id_jadwal_waktu']] = true;

    // === STEP 4: Generate baru ===
    $insert_harian = [];
    $insert_waktu = [];
    $insert_detail = [];

    $jumlah_lapangan = $jumlah_harian = $jumlah_waktu = $jumlah_detail_baru = 0;

    for($i=0;$i<150;$i++){
        $tanggal = date('Y-m-d', strtotime("+$i day"));
        $hari_en = date('l', strtotime($tanggal));
        $hari_id = $hari_map[$hari_en];
        $ops = $jam_ops[$hari_id] ?? null;
        if(!$ops) continue;

        $mulai = strtotime($ops['jam_buka']);
        $tutup = strtotime($ops['jam_tutup']);

        foreach($lapangan as $lap){
            $id_lap = $lap['id_lapangan'];
            $jumlah_lapangan++;
            $key_harian = "$id_lap|$tanggal";
            if(!isset($existing_harian[$key_harian])){
                $insert_harian[] = "('$id_lap','$tanggal','$hari_id','tersedia',NOW())";
                $jumlah_harian++;
            }
            $time = $mulai;
            while($time < $tutup){
                $start = date('H:i:s',$time);
                $end = date('H:i:s',strtotime('+1 hour',$time));
                $key_w = "$id_lap|$start|$end";
                if(!isset($existing_waktu[$key_w])){
                    $insert_waktu[] = "('$id_lap','$start','$end',NOW())";
                    $jumlah_waktu++;
                }
                $time = strtotime('+1 hour',$time);
            }
        }
    }

    if($insert_harian)
        $conn->query("INSERT INTO jadwal_harian (id_lapangan,tanggal,hari,status_hari,created_at) VALUES ".implode(',',$insert_harian));
    if($insert_waktu)
        $conn->query("INSERT INTO jadwal_waktu (id_lapangan,jam_mulai,jam_selesai,created_at) VALUES ".implode(',',$insert_waktu));

    // Refresh ID baru
    $r = $conn->query("SELECT id_jadwal_harian,id_lapangan,tanggal FROM jadwal_harian");
    while($d=$r->fetch_assoc()) $existing_harian[$d['id_lapangan'].'|'.$d['tanggal']] = $d['id_jadwal_harian'];

    $r = $conn->query("SELECT id_jadwal_waktu,id_lapangan,jam_mulai,jam_selesai FROM jadwal_waktu");
    while($d=$r->fetch_assoc()) $existing_waktu[$d['id_lapangan'].'|'.$d['jam_mulai'].'|'.$d['jam_selesai']] = $d['id_jadwal_waktu'];

    // === DETAIL ===
    foreach($existing_harian as $key=>$id_harian){
        [$id_lap,$tanggal] = explode('|',$key);
        $hari_id = $hari_map[date('l',strtotime($tanggal))];
        $ops = $jam_ops[$hari_id] ?? null;
        if(!$ops) continue;
        $time = strtotime($ops['jam_buka']);
        $tutup = strtotime($ops['jam_tutup']);
        while($time < $tutup){
            $start = date('H:i:s',$time);
            $end = date('H:i:s',strtotime('+1 hour',$time));
            $id_w = $existing_waktu["$id_lap|$start|$end"] ?? null;
            if($id_w){
                $key_det = "$id_harian|$id_w";
                if(!isset($existing_detail[$key_det])){
                    $insert_detail[] = "('$id_harian','$id_w','tersedia',NOW())";
                    $jumlah_detail_baru++;
                }
            }
            $time = strtotime('+1 hour',$time);
        }
    }

    if($insert_detail)
        $conn->query("INSERT INTO jadwal_detail (id_jadwal_harian,id_jadwal_waktu,status,created_at) VALUES ".implode(',',$insert_detail));

    // === LOG ===
    $stmt = $conn->prepare("INSERT INTO log_sinkronisasi (waktu_sinkron,jumlah_lapangan,jumlah_jadwal_harian,jumlah_jadwal_waktu,pesan,created_at) VALUES (NOW(),?,?,?,?,NOW())");
    $pesan = "Sinkronisasi sukses — $jumlah_detail_baru slot baru dibuat.";
    $stmt->bind_param('iiis',$jumlah_lapangan,$jumlah_harian,$jumlah_waktu,$pesan);
    $stmt->execute();

    $conn->commit();
    $_SESSION['toast_success'] = "✅ Sinkronisasi berhasil — $jumlah_detail_baru slot baru dibuat!";
} catch(Exception $e){
    $conn->rollback();
    $_SESSION['toast_error'] = "❌ Gagal: ".$e->getMessage();
}

header("Location: jadwal_singkronisasi.php");
exit;
