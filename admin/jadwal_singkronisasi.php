<?php
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

$hari_map = [
    'Monday' => 'Senin',
    'Tuesday' => 'Selasa',
    'Wednesday' => 'Rabu',
    'Thursday' => 'Kamis',
    'Friday' => 'Jumat',
    'Saturday' => 'Sabtu',
    'Sunday' => 'Minggu'
];

// ===============================
// 1️⃣ JAM OPERASIONAL DEFAULT
// ===============================
$jam_operasional_default = [
    ['hari' => 'Senin', 'jam_buka' => '07:00:00', 'jam_tutup' => '23:00:00'],
    ['hari' => 'Selasa', 'jam_buka' => '07:00:00', 'jam_tutup' => '23:00:00'],
    ['hari' => 'Rabu', 'jam_buka' => '07:00:00', 'jam_tutup' => '23:00:00'],
    ['hari' => 'Kamis', 'jam_buka' => '07:00:00', 'jam_tutup' => '23:00:00'],
    ['hari' => 'Jumat', 'jam_buka' => '07:00:00', 'jam_tutup' => '23:00:00'],
    ['hari' => 'Sabtu', 'jam_buka' => '07:00:00', 'jam_tutup' => '23:00:00'],
    ['hari' => 'Minggu', 'jam_buka' => '08:00:00', 'jam_tutup' => '22:00:00'],
];

foreach ($jam_operasional_default as $j) {
    $hari = $j['hari'];
    $cek = mysqli_query($conn, "SELECT id_operasional FROM jam_operasional WHERE hari='$hari'");
    if (mysqli_num_rows($cek) == 0) {
        $sql = "INSERT INTO jam_operasional (hari, jam_buka, jam_tutup, created_at)
                VALUES ('$hari', '{$j['jam_buka']}', '{$j['jam_tutup']}', NOW())";
        mysqli_query($conn, $sql);
    }
}

// ===============================
// 2️⃣ HAPUS JADWAL HARIAN LAMA
// ===============================
mysqli_query($conn, "DELETE FROM jadwal_harian WHERE tanggal < CURDATE()");

// ===============================
// 3️⃣ GENERATE JADWAL HARIAN 7 HARI KE DEPAN
// ===============================
$jumlah_harian = 0;
for ($i = 0; $i < 7; $i++) {
    $tanggal = date('Y-m-d', strtotime("+$i day"));
    $hari_en = date('l', strtotime($tanggal));
    $hari_id = $hari_map[$hari_en];

    $lapangan_q = mysqli_query($conn, "SELECT id_lapangan FROM lapangan WHERE status='aktif'");
    while ($lap = mysqli_fetch_assoc($lapangan_q)) {
        $id_lapangan = $lap['id_lapangan'];
        $cek = mysqli_query($conn, "
            SELECT id_jadwal_harian FROM jadwal_harian 
            WHERE tanggal='$tanggal' AND id_lapangan='$id_lapangan'
        ");
        if (mysqli_num_rows($cek) == 0) {
            $sql = "INSERT INTO jadwal_harian (id_lapangan, tanggal, hari, status_hari, created_at)
                    VALUES ('$id_lapangan', '$tanggal', '$hari_id', 'tersedia', NOW())";
            if (mysqli_query($conn, $sql)) $jumlah_harian++;
        }
    }
}

// ===============================
// 4️⃣ GENERATE JADWAL WAKTU PER LAPANGAN
// ===============================
$jumlah_waktu = 0;
$jam_ops = mysqli_query($conn, "SELECT * FROM jam_operasional");
while ($j = mysqli_fetch_assoc($jam_ops)) {
    $hari = $j['hari'];
    $jam_tutup = strtotime($j['jam_tutup']);

    $lapangan_q = mysqli_query($conn, "SELECT id_lapangan, harga_per_jam FROM lapangan WHERE status='aktif'");
    while ($lap = mysqli_fetch_assoc($lapangan_q)) {
        $id_lapangan = $lap['id_lapangan'];
        $harga = $lap['harga_per_jam'];

        $jam_mulai = strtotime($j['jam_buka']);
        while ($jam_mulai < $jam_tutup) {
            $start = date('H:i:s', $jam_mulai);
            $end = date('H:i:s', strtotime('+1 hour', $jam_mulai));

            $cek_slot = mysqli_query($conn, "
                SELECT id_jadwal_waktu FROM jadwal_waktu 
                WHERE id_lapangan='$id_lapangan' AND jam_mulai='$start' AND jam_selesai='$end'
            ");
            if (mysqli_num_rows($cek_slot) == 0) {
                $sql = "INSERT INTO jadwal_waktu (id_lapangan, jam_mulai, jam_selesai, harga_per_jam, created_at)
                        VALUES ('$id_lapangan', '$start', '$end', '$harga', NOW())";
                if (mysqli_query($conn, $sql)) $jumlah_waktu++;
            }

            $jam_mulai = strtotime('+1 hour', $jam_mulai);
        }
    }
}

// ===============================
// 5️⃣ CATAT LOG SINKRONISASI
// ===============================
$jumlah_lapangan = mysqli_num_rows(mysqli_query($conn, "SELECT id_lapangan FROM lapangan WHERE status='aktif'"));
$pesan = "Sinkronisasi berhasil: {$jumlah_lapangan} lapangan aktif, {$jumlah_harian} jadwal harian baru, {$jumlah_waktu} jadwal waktu baru.";

mysqli_query($conn, "
    INSERT INTO log_sinkronisasi (waktu_sinkron, jumlah_lapangan, jumlah_jadwal_harian, jumlah_jadwal_waktu, pesan)
    VALUES (NOW(), '$jumlah_lapangan', '$jumlah_harian', '$jumlah_waktu', '$pesan')
");

// ===============================
// 6️⃣ TAMPILKAN HASIL
// ===============================
echo "<div style='font-family: Arial; margin: 20px;'>
<h3 style='color: green;'>✅ Jadwal otomatis berhasil diperbarui sampai 7 hari ke depan!</h3>
<p>{$pesan}</p>
<p><b>Catatan:</b> Semua data sinkronisasi telah dicatat di tabel <code>log_sinkronisasi</code>.</p>
<p><b>Tips:</b> Pasang file ini di cron job agar berjalan otomatis setiap malam (misalnya jam 00:05).</p>
</div>";
?>
