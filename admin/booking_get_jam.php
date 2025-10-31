<?php
require_once __DIR__ . '/../config/database.php';

$id_lapangan = $_GET['id_lapangan'] ?? 0;
$tanggal = $_GET['tanggal'] ?? '';

if (!$id_lapangan || !$tanggal) {
  echo "<em class='text-muted'>Lapangan dan tanggal harus dipilih.</em>";
  exit;
}

// Ambil semua jam dari jadwal_waktu
$q = "
  SELECT jw.id_jadwal_waktu, jw.jam_mulai, jw.jam_selesai
  FROM jadwal_waktu jw
  JOIN jadwal_harian jh ON jw.id_jadwal_harian = jh.id_jadwal_harian
  WHERE jh.id_lapangan = '$id_lapangan'
";
$result = mysqli_query($conn, $q);

// Ambil jam yang sudah dibooking
$booked = [];
$b = mysqli_query($conn, "
  SELECT db.id_jadwal_waktu
  FROM booking b
  JOIN detail_booking db ON b.id_booking = db.id_booking
  WHERE b.id_lapangan='$id_lapangan' AND b.tanggal='$tanggal' 
  AND b.status IN ('menunggu','disetujui')
");
while ($r = mysqli_fetch_assoc($b)) {
  $booked[] = $r['id_jadwal_waktu'];
}

// Tampilkan slot jam
if (mysqli_num_rows($result) > 0) {
  while ($jw = mysqli_fetch_assoc($result)) {
    $disabled = in_array($jw['id_jadwal_waktu'], $booked) ? 'disabled' : '';
    $labelClass = $disabled ? 'btn-secondary' : 'btn-outline-primary';
    $timeRange = substr($jw['jam_mulai'], 0, 5) . ' - ' . substr($jw['jam_selesai'], 0, 5);

    echo "
      <label class='btn $labelClass m-1'>
        <input type='checkbox' class='jam-checkbox' name='jam[]' value='{$jw['id_jadwal_waktu']}' $disabled> 
        $timeRange
      </label>
    ";
  }
} else {
  echo "<em class='text-muted'>Tidak ada jadwal untuk lapangan ini.</em>";
}
