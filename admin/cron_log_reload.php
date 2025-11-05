<?php
require_once __DIR__ . '/../config/database.php';

$no = 1;
$q = "SELECT * FROM cron_log ORDER BY tanggal_jalankan DESC";
$r = mysqli_query($conn, $q);

while ($row = mysqli_fetch_assoc($r)):
  $badge = ($row['status'] == 'sukses')
    ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Sukses</span>'
    : '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Gagal</span>';
?>
<tr>
  <td class="text-center"><?= $no++ ?></td>
  <td class="text-center"><?= ucfirst(str_replace('_', ' ', $row['tipe'])) ?></td>
  <td class="text-center"><span class="badge bg-primary"><?= $row['jumlah_data'] ?></span></td>
  <td class="text-center"><?= $badge ?></td>
  <td><?= htmlspecialchars($row['keterangan']) ?></td>
  <td class="text-center"><?= date('d-m-Y H:i:s', strtotime($row['tanggal_jalankan'])) ?></td>
</tr>
<?php endwhile; ?>
