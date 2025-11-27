<?php
// jadwal_singkron_proses.php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/database.php';

// Set Timezone & Error Reporting
date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Validasi Request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: jadwal_singkronisasi.php");
    exit;
}

try {
    // Mulai Transaksi (Semua sukses atau semua batal)
    $conn->begin_transaction();

    $hari_map = [
        'Monday'=>'Senin','Tuesday'=>'Selasa','Wednesday'=>'Rabu','Thursday'=>'Kamis',
        'Friday'=>'Jumat','Saturday'=>'Sabtu','Sunday'=>'Minggu'
    ];

    // ============================================================
    // STEP 1: Pastikan Jam Operasional Default Tersedia
    // ============================================================
    $jam_operasional_default = [
        ['Senin','07:00:00','23:00:00'],['Selasa','07:00:00','23:00:00'],
        ['Rabu','07:00:00','23:00:00'],['Kamis','07:00:00','23:00:00'],
        ['Jumat','07:00:00','23:00:00'],['Sabtu','07:00:00','23:00:00'],
        ['Minggu','08:00:00','22:00:00']
    ];
    
    // Siapkan statement check & insert operasional agar efisien
    $stmtCheckOp = $conn->prepare("SELECT id_operasional FROM jam_operasional WHERE hari=?");
    $stmtInsOp   = $conn->prepare("INSERT INTO jam_operasional (hari,jam_buka,jam_tutup,created_at) VALUES (?,?,?,NOW())");

    foreach ($jam_operasional_default as $j) {
        [$hari, $buka, $tutup] = $j;
        $stmtCheckOp->bind_param("s", $hari);
        $stmtCheckOp->execute();
        if ($stmtCheckOp->get_result()->num_rows == 0) {
            $stmtInsOp->bind_param("sss", $hari, $buka, $tutup);
            $stmtInsOp->execute();
        }
    }

    // ============================================================
    // STEP 2: Load Data Existing (Optimized / Hemat RAM)
    // ============================================================
    // Hanya ambil data Harian & Detail mulai hari ini ke depan.
    // Data masa lalu diabaikan agar memori server tidak penuh.
    
    $today = date('Y-m-d');

    // 2.1 Ambil Lapangan Aktif
    $lapangan = $conn->query("SELECT id_lapangan FROM lapangan WHERE status='aktif'")->fetch_all(MYSQLI_ASSOC);

    // 2.2 Ambil Jam Operasional
    $jam_ops = [];
    $r = $conn->query("SELECT * FROM jam_operasional");
    while($d = $r->fetch_assoc()) $jam_ops[$d['hari']] = $d;

    // 2.3 Cache Jadwal Harian (Hanya Future Dates)
    $existing_harian = [];
    $r = $conn->query("SELECT id_jadwal_harian, id_lapangan, tanggal FROM jadwal_harian WHERE tanggal >= '$today'");
    while($d = $r->fetch_assoc()) {
        $existing_harian[$d['id_lapangan'].'|'.$d['tanggal']] = $d['id_jadwal_harian'];
    }

    // 2.4 Cache Jadwal Waktu (Master Slot)
    // Tabel ini relatif kecil (Jml Lapangan x 15 jam), jadi load semua aman untuk mencegah duplikat.
    $existing_waktu = [];
    $r = $conn->query("SELECT id_jadwal_waktu, id_lapangan, jam_mulai, jam_selesai FROM jadwal_waktu");
    while($d = $r->fetch_assoc()) {
        $existing_waktu[$d['id_lapangan'].'|'.$d['jam_mulai'].'|'.$d['jam_selesai']] = $d['id_jadwal_waktu'];
    }

    // 2.5 Cache Detail (Hanya Future Dates via Join)
    $existing_detail = [];
    $sqlDetail = "SELECT d.id_jadwal_harian, d.id_jadwal_waktu 
                  FROM jadwal_detail d
                  JOIN jadwal_harian h ON d.id_jadwal_harian = h.id_jadwal_harian
                  WHERE h.tanggal >= '$today'";
    $r = $conn->query($sqlDetail);
    while($d = $r->fetch_assoc()) {
        $existing_detail[$d['id_jadwal_harian'].'|'.$d['id_jadwal_waktu']] = true;
    }

    // ============================================================
    // STEP 3: Generasi Data (Logic Loop)
    // ============================================================
    $insert_harian = [];
    $insert_waktu  = [];
    
    $counter_harian = 0;
    $counter_waktu  = 0;

    // Loop 5 Bulan (150 Hari)
    for($i = 0; $i < 150; $i++){
        $tanggal = date('Y-m-d', strtotime("+$i day"));
        $hari_en = date('l', strtotime($tanggal));
        $hari_id = $hari_map[$hari_en];
        
        $ops = $jam_ops[$hari_id] ?? null;
        if(!$ops) continue; // Skip jika hari tidak ada di operasional

        $mulai = strtotime($ops['jam_buka']);
        $tutup = strtotime($ops['jam_tutup']);

        foreach($lapangan as $lap){
            $id_lap = $lap['id_lapangan'];
            
            // --- A. Generate Jadwal Harian ---
            $key_harian = "$id_lap|$tanggal";
            if(!isset($existing_harian[$key_harian])){
                $insert_harian[] = "('$id_lap','$tanggal','$hari_id','tersedia',NOW())";
                // TANDAI sebagai 'pending' agar tidak di-insert lagi jika ada logic lain
                $existing_harian[$key_harian] = 'pending'; 
                $counter_harian++;
            }

            // --- B. Generate Jadwal Waktu (FIX UTAMA DISINI) ---
            $time = $mulai;
            while($time < $tutup){
                $start = date('H:i:s', $time);
                $end   = date('H:i:s', strtotime('+1 hour', $time)); // Durasi 1 jam
                
                $key_w = "$id_lap|$start|$end";
                
                // Cek apakah slot waktu ini sudah ada di DB atau di antrian insert?
                if(!isset($existing_waktu[$key_w])){
                    $insert_waktu[] = "('$id_lap','$start','$end',NOW())";
                    
                    // PENTING: Langsung tandai array memory sebagai 'pending'
                    // Ini mencegah loop hari berikutnya menambahkan jam yang sama lagi!
                    $existing_waktu[$key_w] = 'pending'; 
                    
                    $counter_waktu++;
                }
                
                $time = strtotime('+1 hour', $time);
            }
        }
    }

    // ============================================================
    // STEP 4: Eksekusi Insert Batch (Chunking)
    // ============================================================
    
    // 4.1 Insert Harian Baru
    if(!empty($insert_harian)) {
        // Pecah per 500 data agar query tidak kepanjangan
        foreach(array_chunk($insert_harian, 500) as $chunk) {
            $sql = "INSERT INTO jadwal_harian (id_lapangan,tanggal,hari,status_hari,created_at) VALUES " . implode(',', $chunk);
            $conn->query($sql);
        }
    }

    // 4.2 Insert Waktu Baru
    if(!empty($insert_waktu)) {
        foreach(array_chunk($insert_waktu, 500) as $chunk) {
            $sql = "INSERT INTO jadwal_waktu (id_lapangan,jam_mulai,jam_selesai,created_at) VALUES " . implode(',', $chunk);
            $conn->query($sql);
        }
    }

    // ============================================================
    // STEP 5: Refresh ID & Generate Detail (Relasi)
    // ============================================================
    
    // Kita harus mengambil ID asli dari database untuk data yang baru saja di-insert
    // supaya bisa masuk ke tabel relasi (jadwal_detail)
    
    // Refresh ID Harian (Hanya Masa Depan)
    $r = $conn->query("SELECT id_jadwal_harian, id_lapangan, tanggal FROM jadwal_harian WHERE tanggal >= '$today'");
    while($d = $r->fetch_assoc()) {
        $existing_harian[$d['id_lapangan'].'|'.$d['tanggal']] = $d['id_jadwal_harian'];
    }

    // Refresh ID Waktu (Semua)
    $r = $conn->query("SELECT id_jadwal_waktu, id_lapangan, jam_mulai, jam_selesai FROM jadwal_waktu");
    while($d = $r->fetch_assoc()) {
        $existing_waktu[$d['id_lapangan'].'|'.$d['jam_mulai'].'|'.$d['jam_selesai']] = $d['id_jadwal_waktu'];
    }

    // Loop Ulang untuk Detail
    $insert_detail = [];
    $counter_detail = 0;

    foreach($existing_harian as $key => $id_harian){
        if($id_harian === 'pending') continue; // Safety check

        [$id_lap, $tanggal] = explode('|', $key);
        
        $hari_en = date('l', strtotime($tanggal));
        $hari_id = $hari_map[$hari_en];
        
        $ops = $jam_ops[$hari_id] ?? null;
        if(!$ops) continue;

        $time = strtotime($ops['jam_buka']);
        $tutup = strtotime($ops['jam_tutup']);

        while($time < $tutup){
            $start = date('H:i:s', $time);
            $end   = date('H:i:s', strtotime('+1 hour', $time));

            $key_w_lookup = "$id_lap|$start|$end";
            $id_w = $existing_waktu[$key_w_lookup] ?? null;

            // Jika ID Waktu valid (bukan pending)
            if($id_w && $id_w !== 'pending'){
                $key_det = "$id_harian|$id_w";
                // Cek apakah relasi sudah ada?
                if(!isset($existing_detail[$key_det])){
                    $insert_detail[] = "('$id_harian','$id_w','tersedia',NOW())";
                    $existing_detail[$key_det] = true; // Tandai memory
                    $counter_detail++;
                }
            }
            $time = strtotime('+1 hour', $time);
        }
    }

    // 5.1 Insert Detail Batch
    if(!empty($insert_detail)){
        foreach(array_chunk($insert_detail, 1000) as $chunk){
            $sql = "INSERT INTO jadwal_detail (id_jadwal_harian,id_jadwal_waktu,status,created_at) VALUES " . implode(',', $chunk);
            $conn->query($sql);
        }
    }

    // ============================================================
    // STEP 6: Logging & Commit
    // ============================================================
    $jml_lap = count($lapangan);
    $pesan = "Sync Sukses. Lapangan: $jml_lap | Harian Baru: $counter_harian | Slot Master Baru: $counter_waktu | Slot Booking Baru: $counter_detail";
    
    $stmtLog = $conn->prepare("INSERT INTO log_sinkronisasi (waktu_sinkron,jumlah_lapangan,jumlah_jadwal_harian,jumlah_jadwal_waktu,pesan,created_at) VALUES (NOW(),?,?,?,?,NOW())");
    $stmtLog->bind_param('iiis', $jml_lap, $counter_harian, $counter_waktu, $pesan);
    $stmtLog->execute();

    $conn->commit();
    $_SESSION['toast_success'] = "✅ Sinkronisasi Selesai! ($counter_detail slot booking baru ditambahkan)";

} catch(Exception $e){
    $conn->rollback();
    $_SESSION['toast_error'] = "❌ Gagal Sinkronisasi: " . $e->getMessage();
}

header("Location: jadwal_singkronisasi.php");
exit;