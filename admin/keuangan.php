<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-coins mr-2"></i> Manajemen Keuangan</h1>
      <a href="keuangan_tambah.php" class="btn btn-primary">
        <i class="fas fa-plus-circle"></i> Tambah Transaksi
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
          <h3 class="card-title"><i class="fas fa-list mr-2"></i> Data Transaksi Keuangan</h3>
        </div>
        <div class="card-body">
          <table id="example1" class="table table-bordered table-striped table-hover">
            <thead class="text-center bg-light">
              <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Sumber</th>
                <th>Deskripsi</th>
                <th>Nominal</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $no = 1;
              $query = mysqli_query($conn, "SELECT * FROM keuangan ORDER BY tanggal DESC");
              while ($row = mysqli_fetch_assoc($query)) :
                $warna = $row['jenis'] == 'pemasukan' ? 'text-success' : 'text-danger';
                $ikon = $row['jenis'] == 'pemasukan' ? 'fa-arrow-up' : 'fa-arrow-down';
              ?>
              <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td class="text-center"><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
                <td class="text-center"><i class="fas <?= $ikon ?> <?= $warna ?>"></i> <?= ucfirst($row['jenis']) ?></td>
                <td><?= htmlspecialchars($row['sumber']) ?></td>
                <td><?= htmlspecialchars($row['deskripsi'] ?? '-') ?></td>
                <td class="text-right font-weight-bold <?= $warna ?>">Rp <?= number_format($row['nominal'], 0, ',', '.') ?></td>
                <td class="text-center">
                  <a href="keuangan_edit.php?id=<?= $row['id_keuangan'] ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                  <a href="keuangan_hapus.php?id=<?= $row['id_keuangan'] ?>" class="btn btn-sm btn-danger btn-delete" title="Hapus"><i class="fas fa-trash"></i></a>
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
