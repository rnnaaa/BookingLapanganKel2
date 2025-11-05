<?php
require_once __DIR__ . '/../config/database.php';

$lapangan = mysqli_query($conn, "SELECT id_lapangan FROM lapangan");
while ($l = mysqli_fetch_assoc($lapangan)) {
  $id = $l['id_lapangan'];
  for ($i = 7; $i < 23; $i++) {
    $mulai = sprintf('%02d:00:00', $i);
    $selesai = sprintf('%02d:00:00', $i + 1);
    mysqli_query($conn, "
      INSERT INTO jadwal_waktu (id_lapangan, jam_mulai, jam_selesai, harga_per_jam, created_at, updated_at)
      VALUES ('$id', '$mulai', '$selesai', 50000, NOW())
    ");
  }
}
echo "✅ Jadwal waktu otomatis dibuat untuk semua lapangan.";
