<?php
session_start();
require_once __DIR__ . '/../config/database.php';
$id_user = $_SESSION['id_user'];

$q = mysqli_query($conn, "
  SELECT p.*, b.tanggal, l.nama_lapangan
  FROM pembayaran p
  JOIN booking b ON p.booking_id = b.id_booking
  JOIN lapangan l ON b.id_lapangan = l.id_lapangan
  WHERE b.id_user = '$id_user'
  ORDER BY p.created_at DESC
");
?>

<div class="container py-4">
  <h3 class="mb-4"><i class="fas fa-wallet me-2"></i>Riwayat Pembayaran Saya</h3>
  <table class="table table-bordered table-hover">
    <thead class="bg-light text-center">
      <tr>
        <th>No</th>
        <th>Lapangan</th>
        <th>Tanggal</th>
        <th>Tipe</th>
        <th>Nominal</th>
        <th>Status</th>
        <th>Bukti</th>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; while ($r = mysqli_fetch_assoc($q)): ?>
      <tr>
        <td class="text-center"><?= $no++ ?></td>
        <td><?= htmlspecialchars($r['nama_lapangan']) ?></td>
        <td class="text-center"><?= date('d/m/Y', strtotime($r['tanggal'])) ?></td>
        <td class="text-center"><?= ucfirst($r['tipe']) ?></td>
        <td class="text-end">Rp <?= number_format($r['amount'],0,',','.') ?></td>
        <td class="text-center">
          <span class="badge 
            <?= $r['status_verifikasi']=='valid'?'bg-success':($r['status_verifikasi']=='tidak_valid'?'bg-danger':'bg-warning text-dark') ?>">
            <?= ucfirst($r['status_verifikasi']) ?>
          </span>
        </td>
        <td class="text-center">
          <?php if ($r['bukti_path']): ?>
            <a href="../uploads/<?= htmlspecialchars($r['bukti_path']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
              <i class="fas fa-eye"></i> Lihat
            </a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endwhile; ?>
    </tbody>
  </table>
</div>
