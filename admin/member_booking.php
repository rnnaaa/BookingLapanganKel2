<?php
require_once __DIR__ . '/../config/database.php';
session_start();

/**
 * SCRIPT INI MENSINKRONKAN:
 * - Member aktif → jadwal booking mingguan otomatis
 * - Update jadwal_waktu jadi “dipesan” untuk slot member
 */

date_default_timezone_set('Asia/Jakarta');

// Ambil semua member aktif
$qMember = mysqli_query($conn, "
  SELECT m.id_member, m.id_user, m.tgl_mulai, m.tgl_berakhir, u.nama
  FROM member m
  JOIN users u ON m.id_user = u.id_user
  WHERE m.status = 'aktif'
");

$totalSync = 0;
while ($member = mysqli_fetch_assoc($qMember)) {
  // Jika masa aktif sudah lewat, ubah jadi nonaktif
  if (strtotime($member['tgl_berakhir']) < time()) {
    mysqli_query($conn, "UPDATE member SET status='nonaktif' WHERE id_member='{$member['id_member']}'");
    continue;
  }

  // Ambil jadwal mingguan member
  $qJadwal = mysqli_query($conn, "
    SELECT * FROM member_jadwal 
    WHERE id_member='{$member['id_member']}' AND status='aktif'
  ");

  while ($j = mysqli_fetch_assoc($qJadwal)) {
    $hari = strtolower($j['hari']);
    $lapangan = $j['id_lapangan'];

    // Tentukan tanggal minggu ini sesuai hari
    $hariMap = [
      'senin'=>1, 'selasa'=>2, 'rabu'=>3,
      'kamis'=>4, 'jumat'=>5, 'sabtu'=>6, 'minggu'=>7
    ];
    $today = date('N');
    $targetDay = $hariMap[$hari];
    $selisih = $targetDay - $today;
    if ($selisih < 0) $selisih += 7; // minggu depan
    $tanggalBooking = date('Y-m-d', strtotime("+$selisih day"));

    // Cek apakah booking sudah ada
    $cek = mysqli_query($conn, "
      SELECT * FROM booking 
      WHERE id_user='{$member['id_user']}' 
        AND id_lapangan='$lapangan'
        AND tanggal='$tanggalBooking'
        AND status <> 'dibatalkan'
    ");

    if (mysqli_num_rows($cek) == 0) {
      // Buat booking otomatis
      mysqli_query($conn, "
        INSERT INTO booking (id_user, id_lapangan, tanggal, total_amount, status, payment_status, created_at)
        VALUES ('{$member['id_user']}', '$lapangan', '$tanggalBooking', 0, 'disetujui', 'lunas', NOW())
      ");
      $id_booking = mysqli_insert_id($conn);

      // Tambah ke detail_booking
      mysqli_query($conn, "
        INSERT INTO detail_booking (id_booking, id_jadwal_waktu, jam_mulai, jam_selesai)
        VALUES ('$id_booking', NULL, '{$j['jam_mulai']}', '{$j['jam_selesai']}')
      ");

      // Tandai jadwal waktu lapangan sebagai “dipesan”
      mysqli_query($conn, "
        UPDATE jadwal_waktu 
        SET status='dipesan'
        WHERE id_lapangan='$lapangan'
          AND jam_mulai='{$j['jam_mulai']}'
          AND jam_selesai='{$j['jam_selesai']}'
      ");

      $totalSync++;
    }
  }
}

// Hasil sinkronisasi
$_SESSION['toast_success'] = "Sinkronisasi berhasil! $totalSync booking member otomatis dibuat minggu ini.";
header("Location: member.php");
exit;
?>
