<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-id-card mr-2"></i> Data Membership</h1>
      <a href="member_tambah.php" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle"></i> Tambah Member Baru
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title"><i class="fas fa-users mr-2"></i> Daftar Member Aktif & Nonaktif</h3>
        </div>
        <div class="card-body table-responsive">
          <table id="tblMember" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
              <tr>
                <th>No</th>
                <th>Nama Member</th>
                <th>Jenis</th>
                <th>Jadwal Mingguan</th>
                <th>Tgl Mulai</th>
                <th>Tgl Berakhir</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              $query = "
                SELECT 
                  m.*, u.nama AS nama_user,
                  GROUP_CONCAT(
                    CONCAT(
                      mj.hari, ' (', 
                      DATE_FORMAT(mj.jam_mulai, '%H:%i'), ' - ', DATE_FORMAT(mj.jam_selesai, '%H:%i'),
                      ') - ', l.nama_lapangan
                    ) SEPARATOR '<br>'
                  ) AS jadwal_mingguan
                FROM member m
                JOIN users u ON m.id_user = u.id_user
                LEFT JOIN member_jadwal mj ON m.id_member = mj.id_member
                LEFT JOIN lapangan l ON mj.id_lapangan = l.id_lapangan
                GROUP BY m.id_member
                ORDER BY m.tgl_mulai DESC
              ";
              $result = mysqli_query($conn, $query);
              while ($r = mysqli_fetch_assoc($result)):
                $statusBadge = $r['status'] == 'aktif' 
                  ? '<span class="badge bg-success"><i class="fas fa-check-circle"></i> Aktif</span>'
                  : '<span class="badge bg-secondary"><i class="fas fa-ban"></i> Nonaktif</span>';
              ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($r['nama_user']) ?></td>
                <td class="text-center"><?= htmlspecialchars($r['jenis_member']) ?></td>
                <td><?= $r['jadwal_mingguan'] ?: '<em class="text-muted">Belum ada jadwal</em>' ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($r['tgl_mulai'])) ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($r['tgl_berakhir'])) ?></td>
                <td class="text-center"><?= $statusBadge ?></td>
                <td class="text-center">
                  <a href="member_edit.php?id=<?= $r['id_member'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                  <a href="member_hapus.php?id=<?= $r['id_member'] ?>" 
                     onclick="return confirm('Yakin ingin menghapus data member ini?')" 
                     class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                </td>
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


