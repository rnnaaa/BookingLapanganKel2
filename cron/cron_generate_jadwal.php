<?php
/**
 * Cron Job - Auto Generate Jadwal Harian Badmintoon
 * -----------------------------------------------
 * Fungsi:
 * 1. Generate jadwal_harian 7 hari ke depan (tanpa duplikat)
 * 2. Update status otomatis berdasarkan booking dan member aktif
 * 3. Dijalankan otomatis via CRON tiap malam
 */

require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

echo "=== 🕒 Mulai Auto Generate Jadwal Harian: " . date('d-m-Y H:i:s') . " ===\n";

// 1️⃣ Ambil semua lapangan aktif
$lapanganQuery = mysqli_query($conn, "SELECT id_lapangan FROM lapangan WHERE status='aktif'");
$today = date('Y-m-d');

// 2️⃣ Generate otomatis untuk 7 hari ke depan
for ($i = 0; $i < 7; $i++) {
    $tanggal = date('Y-m-d', strtotime("+$i day", strtotime($today)));

    mysqli_data_seek($lapanganQuery, 0); // reset pointer result
    while ($lap = mysqli_fetch_assoc($lapanganQuery)) {
        $id_lapangan = $lap['id_lapangan'];

        // Cek duplikat jadwal
        $cek = mysqli_query($conn, "
            SELECT 1 FROM jadwal_harian 
            WHERE id_lapangan='$id_lapangan' AND tanggal='$tanggal'
        ");

        if (mysqli_num_rows($cek) == 0) {
            mysqli_query($conn, "
                INSERT INTO jadwal_harian (id_lapangan, tanggal, status_hari, created_at)
                VALUES ('$id_lapangan', '$tanggal', 'tersedia', NOW())
            ");
            echo "✅ Tambah jadwal baru: Lapangan $id_lapangan - $tanggal\n";
        }
    }
}

// 3️⃣ Update status jadwal_harian berdasarkan data booking reguler
mysqli_query($conn, "
    UPDATE jadwal_harian jh
    JOIN (
        SELECT jw.id_lapangan, DATE(b.tanggal) AS tgl, COUNT(db.id_detail_booking) AS total_slot,
               (SELECT COUNT(*) FROM jadwal_waktu WHERE id_lapangan=jw.id_lapangan) AS total_jam
        FROM detail_booking db
        JOIN booking b ON db.id_booking=b.id_booking
        JOIN jadwal_waktu jw ON db.id_jadwal_waktu=jw.id_jadwal_waktu
        WHERE b.status IN ('menunggu','disetujui')
        GROUP BY jw.id_lapangan, tgl
    ) s ON s.id_lapangan=jh.id_lapangan AND s.tgl=jh.tanggal
    SET jh.status_hari = CASE 
        WHEN s.total_slot >= s.total_jam THEN 'penuh_booking'
        ELSE jh.status_hari
    END
");
echo "🔁 Sinkronisasi status berdasarkan booking reguler selesai.\n";

// 4️⃣ Update status jadwal_harian berdasarkan jadwal member aktif
mysqli_query($conn, "
    UPDATE jadwal_harian jh
    JOIN (
        SELECT mj.id_lapangan, mj.hari
        FROM member_jadwal mj
        JOIN member m ON mj.id_member=m.id_member
        WHERE m.status='aktif'
        GROUP BY mj.id_lapangan, mj.hari
    ) mbr ON mbr.id_lapangan=jh.id_lapangan
    SET jh.status_hari='penuh_member'
    WHERE DAYNAME(jh.tanggal)=mbr.hari
      AND jh.status_hari NOT IN ('libur','penuh_booking')
");
echo "👥 Sinkronisasi status berdasarkan jadwal member selesai.\n";

// 5️⃣ (Opsional) Tandai jadwal_harian lampau sebagai 'selesai'
mysqli_query($conn, "
    UPDATE jadwal_harian 
    SET status_hari='selesai' 
    WHERE tanggal < CURDATE() AND status_hari!='selesai'
");
echo "📆 Tandai jadwal lama sebagai selesai.\n";

echo "=== ✅ CRON JOB SELESAI: " . date('d-m-Y H:i:s') . " ===\n";
?>
