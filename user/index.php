<?php
require_once __DIR__ . '/../config/database.php';
include('includes/header.php');
include('includes/navbar.php');

// Ambil jadwal harian hari ini
$today = date('Y-m-d');
$query = "
  SELECT l.*, jh.status_hari
  FROM lapangan l
  LEFT JOIN jadwal_harian jh ON jh.id_lapangan = l.id_lapangan AND jh.tanggal = '$today'
  WHERE l.status='aktif'
";
$result = mysqli_query($conn, $query);
?>

<div class="container py-5">
  <h2 class="mb-4 text-center"><i class="fas fa-futbol mr-2"></i>Daftar Lapangan Badmintoon</h2>
  
  <div class="row">
    <?php while ($row = mysqli_fetch_assoc($result)): ?>
      <div class="col-md-4 mb-4">
        <div class="card shadow-lg border-0">
          <img src="../uploads/lapangan/<?= $row['foto'] ?: 'no-image.png' ?>" 
               class="card-img-top" style="height: 200px; object-fit: cover;">
          <div class="card-body text-center">
            <h5 class="card-title"><?= htmlspecialchars($row['nama_lapangan']) ?></h5>
            <p class="text-muted">Rp <?= number_format($row['harga_per_jam'],0,',','.') ?> / jam</p>
            <?php
              $status = $row['status_hari'] ?? 'tersedia';
              $badge = [
                'tersedia' => 'success',
                'penuh_booking' => 'danger',
                'penuh_member' => 'primary',
                'libur' => 'warning text-dark'
              ][$status] ?? 'secondary';
            ?>
            <span class="badge bg-<?= $badge ?>"><?= ucfirst(str_replace('_',' ',$status)) ?></span>
            <hr>
            <a href="jadwal.php?id=<?= $row['id_lapangan'] ?>" class="btn btn-outline-primary btn-sm">
              <i class="fas fa-calendar-alt"></i> Lihat Jadwal
            </a>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<?php include('includes/footer.php'); ?>
