<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM keuangan WHERE id_keuangan='$id'");
$data = mysqli_fetch_assoc($result);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tanggal = $_POST['tanggal'];
  $jenis = $_POST['jenis'];
  $sumber = $_POST['sumber'];
  $deskripsi = $_POST['deskripsi'];
  $nominal = $_POST['nominal'];

  $update = "UPDATE keuangan SET tanggal='$tanggal', jenis='$jenis', sumber='$sumber', deskripsi='$deskripsi', nominal='$nominal' WHERE id_keuangan='$id'";
  if (mysqli_query($conn, $update)) {
    $_SESSION['toast_success'] = 'Transaksi berhasil diperbarui!';
    header('Location: keuangan.php');
    exit;
  } else {
    $_SESSION['toast_error'] = 'Gagal memperbarui data.';
  }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Transaksi</h1>
      <a href="keuangan.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-header bg-warning text-white">
          <h3 class="card-title"><i class="fas fa-pen mr-2"></i> Form Edit Transaksi</h3>
        </div>
        <form action="" method="POST">
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <label>Tanggal</label>
                <input type="date" name="tanggal" value="<?= $data['tanggal'] ?>" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label>Jenis Transaksi</label>
                <select name="jenis" class="form-control" required>
                  <option value="pemasukan" <?= $data['jenis']=='pemasukan'?'selected':'' ?>>Pemasukan</option>
                  <option value="pengeluaran" <?= $data['jenis']=='pengeluaran'?'selected':'' ?>>Pengeluaran</option>
                </select>
              </div>
              <div class="col-md-4">
                <label>Sumber</label>
                <input type="text" name="sumber" value="<?= $data['sumber'] ?>" class="form-control" required>
              </div>
              <div class="col-md-8 mt-2">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control"><?= $data['deskripsi'] ?></textarea>
              </div>
              <div class="col-md-4 mt-2">
                <label>Nominal (Rp)</label>
                <input type="number" name="nominal" value="<?= $data['nominal'] ?>" class="form-control" required>
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
