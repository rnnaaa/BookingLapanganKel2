<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-history mr-2"></i> Riwayat Sinkronisasi Jadwal</h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white">
          <h3 class="card-title mb-0"><i class="fas fa-database mr-2"></i> Log Sinkronisasi</h3>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped w-100 text-center">
            <thead class="bg-light">
              <tr>
                <th>No</th>
                <th>Waktu Sinkronisasi</th>
                <th>Lapangan Aktif</th>
                <th>Jadwal Harian Baru</th>
                <th>Jadwal Waktu Baru</th>
                <th>Pesan</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $q = mysqli_query($conn, "SELECT * FROM log_sinkronisasi ORDER BY id_log DESC");
              $no = 1;
              while ($r = mysqli_fetch_assoc($q)): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= date('d-m-Y H:i:s', strtotime($r['waktu_sinkron'])) ?></td>
                <td><?= $r['jumlah_lapangan'] ?></td>
                <td><?= $r['jumlah_jadwal_harian'] ?></td>
                <td><?= $r['jumlah_jadwal_waktu'] ?></td>
                <td><?= htmlspecialchars($r['pesan']) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
