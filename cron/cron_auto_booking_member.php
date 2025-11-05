<?php
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

$today = date('Y-m-d');
$endDate = date('Y-m-d', strtotime('+7 days'));
$totalInserted = 0;

// Ambil semua member aktif
$qMember = mysqli_query($conn, "
  SELECT m.id_member, m.id_user
  FROM member m
  WHERE m.status = 'aktif'
");

while ($member = mysqli_fetch_assoc($qMember)) {
  $id_member = $member['id_member'];
  $id_user = $member['id_user'];

  // Ambil jadwal rutin member
  $qJadwal = mysqli_query($conn, "
    SELECT * FROM member_jadwal 
    WHERE id_member = '$id_member' AND status = 'aktif'
  ");

  while ($jadwal = mysqli_fetch_assoc($qJadwal)) {
    $hari = $jadwal['hari'];
    $id_lapangan = $jadwal['id_lapangan'];
    $jam_mulai = $jadwal['jam_mulai'];
    $jam_selesai = $jadwal['jam_selesai'];
    $harga = $jadwal['harga_per_jam_member'];

    // Daftar tanggal seminggu ke depan
    $periode = new DatePeriod(
      new DateTime($today),
      new DateInterval('P1D'),
      new DateTime($endDate)
    );

    foreach ($periode as $tanggal) {
      $namaHari = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
      ];
      $hariTanggal = $namaHari[$tanggal->format('l')];

      if ($hariTanggal === $hari) {
        $tglBooking = $tanggal->format('Y-m-d');

        // Cek duplikat
        $cek = mysqli_query($conn, "
          SELECT id_booking FROM booking 
          WHERE id_user='$id_user' 
            AND id_lapangan='$id_lapangan'
            AND tanggal='$tglBooking'
            AND tipe_booking='member'
        ");
        if (mysqli_num_rows($cek) > 0) continue;

        // Hitung total durasi
        $durasi = (strtotime($jam_selesai) - strtotime($jam_mulai)) / 3600;
        $total = $harga * $durasi;

        // Buat booking
        mysqli_query($conn, "
          INSERT INTO booking (
            id_user, id_lapangan, tanggal, total_amount, dp_amount, remaining_amount,
            payment_status, status, tipe_booking, created_at
          ) VALUES (
            '$id_user', '$id_lapangan', '$tglBooking', '$total', 0, '$total',
            'belum_bayar', 'disetujui', 'member', NOW()
          )
        ");
        $booking_id = mysqli_insert_id($conn);

        // Cari jam waktu yang beririsan (lebih fleksibel)
        $qWaktu = mysqli_query($conn, "
          SELECT id_jadwal_waktu 
          FROM jadwal_waktu
          WHERE id_lapangan='$id_lapangan'
          AND (
            (jam_mulai >= '$jam_mulai' AND jam_mulai < '$jam_selesai')
            OR (jam_selesai > '$jam_mulai' AND jam_selesai <= '$jam_selesai')
          )
        ");

        while ($w = mysqli_fetch_assoc($qWaktu)) {
          mysqli_query($conn, "
            INSERT INTO detail_booking (id_booking, id_jadwal_waktu, harga)
            VALUES ('$booking_id', '{$w['id_jadwal_waktu']}', '$harga')
          ");
        }

        $totalInserted++;
      }
    }
  }
}

// Simpan log cron
mysqli_query($conn, "
  INSERT INTO cron_log (tipe, jumlah_data, status, keterangan)
  VALUES ('booking_member', '$totalInserted', 'sukses', 'Auto generate booking rutin member mingguan')
");

echo "✅ Cron selesai — total booking dibuat: $totalInserted (" . date('d-m-Y H:i:s') . ")";
