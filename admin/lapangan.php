<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-futbol mr-2"></i> Data Lapangan</h1>
      <a href="lapangan_tambah.php" class="btn btn-primary shadow-sm">
        <i class="fas fa-plus-circle mr-1"></i> Tambah Lapangan
      </a>
    </div>
  </section>

  <!-- Konten Utama -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h3 class="card-title mb-0">
            <i class="fas fa-table mr-2"></i> Daftar Lapangan Badminton
          </h3>
        </div>

        <div class="card-body">
          <table id="tblLapangan" class="table table-bordered table-hover table-striped align-middle">
            <thead class="bg-light text-center">
              <tr>
                <th style="width: 5%">No</th>
                <th>Foto</th>
                <th>Nama Lapangan</th>
                <th>Tipe</th>
                <th>Harga / Jam</th>
                <th>Harga Member</th>
                <th>Deskripsi</th>
                <th>Status</th>
                <th style="width: 12%">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              $query = mysqli_query($conn, "SELECT * FROM lapangan ORDER BY id_lapangan DESC");

              while ($r = mysqli_fetch_assoc($query)):
                $fotoPath = !empty($r['foto']) ? "../uploads/lapangan/" . htmlspecialchars($r['foto']) : "../assets/img/no-image.png";
                $statusBadge = ($r['status'] === 'aktif')
                  ? '<span class="badge bg-success"><i class="fas fa-check-circle mr-1"></i>Aktif</span>'
                  : '<span class="badge bg-secondary"><i class="fas fa-ban mr-1"></i>Nonaktif</span>';
              ?>
                <tr>
                  <td class="text-center"><?= $no++ ?></td>
                  <td class="text-center">
                    <img src="<?= $fotoPath ?>" alt="Foto Lapangan"
                         class="img-thumbnail shadow-sm"
                         style="width: 80px; height: 60px; object-fit: cover;">
                  </td>
                  <td><?= htmlspecialchars($r['nama_lapangan']) ?></td>
                  <td class="text-capitalize"><?= htmlspecialchars($r['tipe']) ?></td>
                  <td>Rp <?= number_format($r['harga_per_jam'], 0, ',', '.') ?></td>
                  <td>Rp <?= number_format($r['harga_member'], 0, ',', '.') ?></td>
                  <td><?= !empty($r['deskripsi'])
                        ? htmlspecialchars(strlen($r['deskripsi']) > 60
                            ? substr($r['deskripsi'], 0, 60) . '...'
                            : $r['deskripsi'])
                        : '<em class="text-muted">Tidak ada deskripsi</em>' ?>
                  </td>
                  <td class="text-center"><?= $statusBadge ?></td>
                  <td class="text-center">
                    <a href="lapangan_edit.php?id=<?= $r['id_lapangan'] ?>"
                       class="btn btn-sm btn-warning"
                       title="Edit Data">
                      <i class="fas fa-edit"></i>
                    </a>
                    <a href="lapangan_hapus.php?id=<?= $r['id_lapangan'] ?>"
                       class="btn btn-sm btn-danger btn-delete"
                       title="Hapus Data">
                      <i class="fas fa-trash-alt"></i>
                    </a>
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

<?php
include('../includes/footer.php');
ob_end_flush();
?>
