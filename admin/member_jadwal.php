<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-calendar-week mr-2"></i> Jadwal Rutin Member</h1>
      <a href="member_jadwal_tambah.php" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle"></i> Tambah Jadwal
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title"><i class="fas fa-calendar-alt mr-2"></i> Daftar Jadwal Member</h3>
        </div>

        <div class="card-body">
          <table id="tblMemberJadwal" class="table table-bordered table-striped table-hover align-middle w-100">
            <thead class="bg-light text-center">
              <tr>
                <th>No</th>
                <th>Nama Member</th>
                <th>Lapangan</th>
                <th>Hari</th>
                <th>Jam</th>
                <th>Harga/Jam</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              $q = "
                SELECT 
                  mj.*, 
                  u.nama AS nama_user, 
                  l.nama_lapangan
                FROM member_jadwal mj
                JOIN member m ON mj.id_member = m.id_member
                JOIN users u ON m.id_user = u.id_user
                JOIN lapangan l ON mj.id_lapangan = l.id_lapangan
                ORDER BY FIELD(mj.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu'), mj.jam_mulai
              ";
              $result = mysqli_query($conn, $q);
              while ($row = mysqli_fetch_assoc($result)):
                $badge = $row['status'] == 'aktif'
                  ? '<span class="badge bg-success"><i class="fas fa-check"></i> Aktif</span>'
                  : '<span class="badge bg-secondary"><i class="fas fa-ban"></i> Nonaktif</span>';
              ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['nama_user']) ?></td>
                <td><?= htmlspecialchars($row['nama_lapangan']) ?></td>
                <td class="text-center"><?= $row['hari'] ?></td>
                <td class="text-center"><?= substr($row['jam_mulai'], 0, 5) ?> - <?= substr($row['jam_selesai'], 0, 5) ?></td>
                <td class="text-end">Rp <?= number_format($row['harga_per_jam_member'], 0, ',', '.') ?></td>
                <td class="text-center"><?= $badge ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['created_at'])) ?></td>
                <td class="text-center">
                  <a href="member_jadwal_edit.php?id=<?= $row['id_member_jadwal'] ?>" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                  <a href="member_jadwal_hapus.php?id=<?= $row['id_member_jadwal'] ?>" onclick="return confirm('Yakin ingin menghapus jadwal ini?')" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></a>
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


