<?php
session_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header_user.php'); // versi navbar user

$id_user = $_SESSION['id_user']; // pastikan login sudah tersimpan

$query = "
  SELECT b.*, l.nama_lapangan
  FROM booking b
  JOIN lapangan l ON b.id_lapangan = l.id_lapangan
  WHERE b.id_user = '$id_user'
  ORDER BY b.created_at DESC
";
$result = mysqli_query($conn, $query);
?>

<div class="container py-4">
  <h3 class="mb-4"><i class="fas fa-calendar-check me-2"></i>Booking Saya</h3>

  <table class="table table-bordered table-hover">
    <thead class="bg-light text-center">
      <tr>
        <th>No</th>
        <th>Lapangan</th>
        <th>Tanggal</th>
        <th>Total</th>
        <th>Status Pembayaran</th>
        <th>Status Booking</th>
        <th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; while ($r = mysqli_fetch_assoc($result)): ?>
      <tr>
        <td class="text-center"><?= $no++ ?></td>
        <td><?= htmlspecialchars($r['nama_lapangan']) ?></td>
        <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
        <td class="text-end">Rp <?= number_format($r['total_amount'], 0, ',', '.') ?></td>
        <td class="text-center">
          <span class="badge 
            <?= $r['payment_status'] == 'dp_bayar' ? 'bg-warning text-dark' : 
               ($r['payment_status']=='lunas'?'bg-success':'bg-secondary') ?>">
            <?= ucfirst(str_replace('_',' ',$r['payment_status'])) ?>
          </span>
        </td>
        <td class="text-center">
          <span class="badge <?= $r['status']=='disetujui'?'bg-success':'bg-info' ?>">
            <?= ucfirst($r['status']) ?>
          </span>
        </td>
        <td class="text-center">
          <?php if ($r['payment_status']=='belum_bayar' || $r['payment_status']=='menunggu_verifikasi'): ?>
            <a href="pembayaran_upload.php?id=<?= $r['id_booking'] ?>&tipe=DP" class="btn btn-sm btn-primary">
              <i class="fas fa-upload"></i> Upload DP
            </a>
          <?php elseif ($r['payment_status']=='dp_bayar'): ?>
            <a href="pembayaran_upload.php?id=<?= $r['id_booking'] ?>&tipe=Pelunasan" class="btn btn-sm btn-success">
              <i class="fas fa-wallet"></i> Upload Pelunasan
            </a>
          <?php else: ?>
            <span class="text-muted"><em>-</em></span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
