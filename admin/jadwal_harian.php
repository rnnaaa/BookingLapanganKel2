<?php
//jadwal_harian.php
require_once 'auth_check.php';
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
// include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-calendar-day mr-2"></i> Jadwal Harian Lapangan</h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header text-white" 
             style="background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
                    box-shadow: inset 0 -2px 8px rgba(0, 0, 0, 0.15);">
          <h3 class="card-title mb-0">
            <i class="fas fa-calendar-alt mr-2"></i> Daftar Jadwal Harian Lapangan
          </h3>
        </div>

        <div class="card-body">
          <table id="tblHarian" class="table table-bordered table-striped table-hover w-100 align-middle">
            <thead class="text-center bg-light">
              <tr>
                <th>No</th>
                <th>Lapangan</th>
                <th>Tanggal</th>
                <th>Hari</th>
                <th>Status Hari</th>
                <th>Dibuat</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              $sql = "
                SELECT 
                  jh.id_jadwal_harian,
                  l.nama_lapangan,
                  jh.tanggal,
                  jh.hari,
                  jh.status_hari,
                  jh.created_at
                FROM jadwal_harian jh
                JOIN lapangan l ON jh.id_lapangan = l.id_lapangan
                ORDER BY jh.tanggal DESC
              ";

              $q = mysqli_query($conn, $sql);
              while ($r = mysqli_fetch_assoc($q)):
                switch ($r['status_hari']) {
                  case 'tersedia': $badge = 'bg-success'; $icon = 'fa-check-circle'; break;
                  case 'penuh': $badge = 'bg-danger'; $icon = 'fa-times-circle'; break;
                  case 'libur': $badge = 'bg-warning text-dark'; $icon = 'fa-ban'; break;
                  default: $badge = 'bg-secondary'; $icon = 'fa-question-circle';
                }
              ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($r['tanggal'])) ?></td>
                <td class="text-center"><?= htmlspecialchars($r['hari']) ?></td>
                <td class="text-center">
                  <span class="badge <?= $badge ?>">
                    <i class="fas <?= $icon ?> mr-1"></i><?= ucfirst($r['status_hari']) ?>
                  </span>
                </td>
                <td class="text-center"><?= date('d-m-Y H:i', strtotime($r['created_at'])) ?></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); 
?>
