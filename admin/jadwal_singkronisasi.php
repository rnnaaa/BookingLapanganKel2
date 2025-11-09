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

// =====================================
// 1️⃣ Pastikan jam_operasional ada
// =====================================
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
        mysqli_query($conn, "
            INSERT INTO jam_operasional (hari, jam_buka, jam_tutup, created_at)
            VALUES ('$hari', '{$j['jam_buka']}', '{$j['jam_tutup']}', NOW())
        ");
    }
}

// =====================================
// 2️⃣ Bersihkan jadwal_harian lama
// =====================================
mysqli_query($conn, "DELETE FROM jadwal_harian WHERE tanggal < CURDATE()");

// =====================================
// 3️⃣ Generate jadwal_harian 7 hari ke depan
// =====================================
for ($i = 0; $i < 7; $i++) {
    $tanggal = date('Y-m-d', strtotime("+$i day"));
    $hari_en = date('l', strtotime($tanggal));
    $hari_id = $hari_map[$hari_en];

    $lapangan_q = mysqli_query($conn, "SELECT id_lapangan FROM lapangan WHERE status='aktif'");
    while ($lap = mysqli_fetch_assoc($lapangan_q)) {
        $id_lapangan = $lap['id_lapangan'];

        // Tambah jadwal_harian jika belum ada
        $cek = mysqli_query($conn, "
            SELECT id_jadwal_harian FROM jadwal_harian 
            WHERE tanggal='$tanggal' AND id_lapangan='$id_lapangan'
        ");
        if (mysqli_num_rows($cek) == 0) {
            mysqli_query($conn, "
                INSERT INTO jadwal_harian (id_lapangan, tanggal, hari, status_hari, created_at)
                VALUES ('$id_lapangan', '$tanggal', '$hari_id', 'tersedia', NOW())
            ");
        }

        // Ambil ID jadwal_harian
        $jadwal_harian = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT id_jadwal_harian FROM jadwal_harian 
            WHERE tanggal='$tanggal' AND id_lapangan='$id_lapangan'
        "));
        $id_jadwal_harian = $jadwal_harian['id_jadwal_harian'];

        // Ambil jam operasional untuk hari tersebut
        $jam_ops = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT * FROM jam_operasional WHERE hari='$hari_id'
        "));
        if (!$jam_ops) continue;

        $jam_mulai = strtotime($jam_ops['jam_buka']);
        $jam_tutup = strtotime($jam_ops['jam_tutup']);

        // Generate slot waktu per jam
        while ($jam_mulai < $jam_tutup) {
            $start = date('H:i:s', $jam_mulai);
            $end = date('H:i:s', strtotime('+1 hour', $jam_mulai));

            // Pastikan jadwal_waktu (master slot jam) sudah ada
            $cek_slot = mysqli_query($conn, "
                SELECT id_jadwal_waktu FROM jadwal_waktu 
                WHERE id_lapangan='$id_lapangan' AND jam_mulai='$start' AND jam_selesai='$end'
            ");
            if (mysqli_num_rows($cek_slot) == 0) {
                mysqli_query($conn, "
                    INSERT INTO jadwal_waktu (id_lapangan, jam_mulai, jam_selesai, created_at)
                    VALUES ('$id_lapangan', '$start', '$end', NOW())
                ");
            }

            // Ambil ID jadwal_waktu
            $jadwal_waktu = mysqli_fetch_assoc(mysqli_query($conn, "
                SELECT id_jadwal_waktu FROM jadwal_waktu 
                WHERE id_lapangan='$id_lapangan' AND jam_mulai='$start' AND jam_selesai='$end'
            "));
            $id_jadwal_waktu = $jadwal_waktu['id_jadwal_waktu'];

            // Tambahkan ke jadwal_detail kalau belum ada
            $cek_detail = mysqli_query($conn, "
                SELECT id_detail FROM jadwal_detail 
                WHERE id_jadwal_harian='$id_jadwal_harian' AND id_jadwal_waktu='$id_jadwal_waktu'
            ");
            if (mysqli_num_rows($cek_detail) == 0) {
                mysqli_query($conn, "
                    INSERT INTO jadwal_detail (id_jadwal_harian, id_jadwal_waktu, status, created_at)
                    VALUES ('$id_jadwal_harian', '$id_jadwal_waktu', 'tersedia', NOW())
                ");
            }

            $jam_mulai = strtotime('+1 hour', $jam_mulai);
        }
    }
}

echo "<div style='font-family: Arial; margin: 20px;'>
<h3 style='color: green;'>✅ Jadwal otomatis lengkap sampai 7 hari ke depan!</h3>
<p>Termasuk: <b>jadwal harian + slot per jam (jadwal_detail)</b></p>
<p>Setiap slot default: <b>tersedia</b></p>
<p><b>Harga:</b> akan diambil langsung dari tabel <code>lapangan</code> saat transaksi/booking.</p>
<p><b>Tips:</b> Pasang di cron job agar diperbarui otomatis tiap malam.</p>
</div>";
?>
