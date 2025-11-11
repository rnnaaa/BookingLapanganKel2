<?php
require_once __DIR__ . '/../config/database.php';
date_default_timezone_set('Asia/Jakarta');

echo "<h2>🔄 Auto Booking Member Mingguan - Badmintoon</h2>";
echo "<p>Dijalankan pada: <b>" . date('d-m-Y H:i:s') . "</b></p>";

$hariKeAngka = [
  'Senin' => 1, 'Selasa' => 2, 'Rabu' => 3,
  'Kamis' => 4, 'Jumat' => 5, 'Sabtu' => 6, 'Minggu' => 7
];

$currentWeek = date('W');
$currentYear = date('Y');
$totalInsert = 0;

// Ambil member aktif
$qMember = "
  SELECT * FROM member
  WHERE status = 'aktif'
  AND CURDATE() BETWEEN tgl_mulai AND tgl_berakhir
";
$rMember = mysqli_query($conn, $qMember);

if (mysqli_num_rows($rMember) == 0) {
  echo "<p>⚠️ Tidak ada member aktif minggu ini.</p>";
  exit;
}

while ($m = mysqli_fetch_assoc($rMember)) {
  $id_member = $m['id_member'];
  $id_user = $m['id_user'];

  // Ambil jadwal aktif member
  $qJadwal = "
    SELECT mj.*, l.nama_lapangan, l.harga_per_jam
    FROM member_jadwal mj
    JOIN lapangan l ON mj.id_lapangan = l.id_lapangan
    WHERE mj.id_member = '$id_member' AND mj.status = 'aktif'
  ";
  $rJadwal = mysqli_query($conn, $qJadwal);

  while ($j = mysqli_fetch_assoc($rJadwal)) {
    $hari = $j['hari'];
    if (!isset($hariKeAngka[$hari])) continue;

    // Tanggal minggu ini
    $monday = new DateTime();
    $monday->setISODate($currentYear, $currentWeek, 1);
    $targetDate = clone $monday;
    $targetDate->modify('+' . ($hariKeAngka[$hari] - 1) . ' days');
    $tanggal = $targetDate->format('Y-m-d');

    $id_lapangan = $j['id_lapangan'];
    $jam_mulai = $j['jam_mulai'];
    $jam_selesai = $j['jam_selesai'];
    $harga_per_jam = $j['harga_per_jam_member'] ?: $j['harga_per_jam'];

    // Cek apakah booking minggu ini sudah ada
    $cek = mysqli_query($conn, "
      SELECT id_booking FROM booking
      WHERE id_member = '$id_member'
      AND tanggal = '$tanggal'
      AND id_lapangan = '$id_lapangan'
    ");
    if (mysqli_num_rows($cek) > 0) continue;

    // Hitung total (durasi jam * harga_per_jam)
    $start = strtotime($jam_mulai);
    $end = strtotime($jam_selesai);
    $durasiJam = round(($end - $start) / 3600);
    $total = $durasiJam * $harga_per_jam;

    // Insert ke tabel booking
    $insertBooking = "
      INSERT INTO booking (
        id_user, id_member, id_lapangan, tanggal,
        keterangan, status, total_amount, remaining_amount,
        payment_status, payment_method, created_at
      ) VALUES (
        '$id_user', '$id_member', '$id_lapangan', '$tanggal',
        'Booking otomatis mingguan member', 'disetujui',
        '$total', 0, 'lunas', 'otomatis', NOW()
      )
    ";
    if (mysqli_query($conn, $insertBooking)) {
      $id_booking = mysqli_insert_id($conn);
      $totalInsert++;

      // Masukkan detail_booking per jam
      $startTime = strtotime($jam_mulai);
      while ($startTime < strtotime($jam_selesai)) {
        $jamAwal = date('H:i:s', $startTime);
        $jamAkhir = date('H:i:s', $startTime + 3600);

        mysqli_query($conn, "
          INSERT INTO detail_booking (
            id_booking, id_lapangan, jam_mulai, jam_selesai
          ) VALUES (
            '$id_booking', '$id_lapangan', '$jamAwal', '$jamAkhir'
          )
        ");

        $startTime += 3600;
      }
    }
  }
}

echo "<hr><p>✅ Selesai! Total booking otomatis dibuat: <b>$totalInsert</b></p>";

if ($totalInsert > 0) {
  echo "<p>Semua booking member minggu ini berhasil dibuat otomatis 🎉</p>";
} else {
  echo "<p>ℹ️ Tidak ada booking baru yang perlu dibuat (semua sudah ada).</p>";
}
?>
