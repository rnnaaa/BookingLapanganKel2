<?php
//jadwal_waktu.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
// include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <!-- ======== HEADER ======== -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-clock mr-2"></i> Jadwal Waktu Lapangan</h1>
    </div>
  </section>

  <!-- ======== CONTENT ======== -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header text-white" 
             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
                    box-shadow: inset 0 -2px 8px rgba(0, 0, 0, 0.15);">
          <h3 class="card-title mb-0">
            <i class="fas fa-clock mr-2"></i> Daftar Jadwal Waktu
          </h3>
        </div>

        <div class="card-body">
          <table id="tblWaktu" class="table table-bordered table-striped table-hover w-100 align-middle">
            <thead class="text-center bg-light">
              <tr>
                <th>No</th>
                <th>Lapangan</th>
                <th>Jam</th>
                <th>Dibuat</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              $query = mysqli_query($conn, "
                SELECT jw.*, l.nama_lapangan 
                FROM jadwal_waktu jw
                JOIN lapangan l ON jw.id_lapangan = l.id_lapangan
                ORDER BY l.nama_lapangan, jw.jam_mulai
              ");
              while ($row = mysqli_fetch_assoc($query)):
              ?>
                <tr>
                  <td class="text-center"><?= $no++ ?></td>
                  <td><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                  <td class="text-center">
                    <?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?>
                  </td>
                  <td class="text-center"><?= date('d-m-Y H:i', strtotime($row['created_at'])) ?></td>
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
