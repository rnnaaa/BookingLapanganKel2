<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

$id = $_GET['id'];
$query = mysqli_query($conn, "SELECT * FROM pengeluaran WHERE id_pengeluaran='$id'");
$data = mysqli_fetch_assoc($query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tanggal = $_POST['tanggal'];
  $kategori = $_POST['kategori'];
  $deskripsi = $_POST['deskripsi'];
  $jumlah = $_POST['jumlah'];
  $keterangan = $_POST['keterangan'];

  $update = "UPDATE pengeluaran SET tanggal='$tanggal', kategori='$kategori', deskripsi='$deskripsi', jumlah='$jumlah', keterangan='$keterangan' WHERE id_pengeluaran='$id'";
  if (mysqli_query($conn, $update)) {
    $_SESSION['toast_success'] = 'Data pengeluaran berhasil diperbarui!';
    header('Location: pengeluaran.php');
    exit;
  } else {
    $_SESSION['toast_error'] = 'Gagal memperbarui data!';
  }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Pengeluaran</h1>
      <a href="pengeluaran.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-warning text-white">
          <h3 class="card-title"><i class="fas fa-pen mr-2"></i> Form Edit Pengeluaran</h3>
        </div>
        <form method="POST">
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="<?= $data['tanggal'] ?>" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label>Kategori</label>
                <input type="text" name="kategori" value="<?= $data['kategori'] ?>" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label>Jumlah (Rp)</label>
                <input type="number" name="jumlah" value="<?= $data['jumlah'] ?>" class="form-control" required>
              </div>
              <div class="col-md-6 mt-3">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control"><?= $data['deskripsi'] ?></textarea>
              </div>
              <div class="col-md-6 mt-3">
                <label>Keterangan</label>
                <textarea name="keterangan" class="form-control"><?= $data['keterangan'] ?></textarea>
              </div>
            </div>
          </div>
          <div class="card-footer text-right">
            <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
