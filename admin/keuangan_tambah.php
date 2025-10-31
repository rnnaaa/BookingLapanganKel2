<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tanggal = $_POST['tanggal'];
  $jenis = $_POST['jenis'];
  $sumber = $_POST['sumber'];
  $deskripsi = $_POST['deskripsi'];
  $nominal = $_POST['nominal'];

  if (!empty($tanggal) && !empty($jenis) && !empty($sumber) && !empty($nominal)) {
    $query = "INSERT INTO keuangan (tanggal, jenis, sumber, deskripsi, nominal) VALUES ('$tanggal', '$jenis', '$sumber', '$deskripsi', '$nominal')";
    if (mysqli_query($conn, $query)) {
      $_SESSION['toast_success'] = 'Transaksi berhasil ditambahkan!';
      header('Location: keuangan.php');
      exit;
    } else {
      $_SESSION['toast_error'] = 'Gagal menambahkan data.';
    }
  } else {
    $_SESSION['toast_error'] = 'Semua field wajib diisi!';
  }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-plus-circle mr-2"></i> Tambah Transaksi</h1>
      <a href="keuangan.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-header bg-success text-white">
          <h3 class="card-title"><i class="fas fa-wallet mr-2"></i> Form Tambah Transaksi</h3>
        </div>
        <form action="" method="POST">
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <div class="form-group">
                  <label for="tanggal">Tanggal</label>
                  <input type="date" name="tanggal" id="tanggal" class="form-control" required>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="jenis">Jenis Transaksi</label>
                  <select name="jenis" id="jenis" class="form-control" required>
                    <option value="">-- Pilih Jenis --</option>
                    <option value="pemasukan">Pemasukan</option>
                    <option value="pengeluaran">Pengeluaran</option>
                  </select>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="sumber">Sumber</label>
                  <input type="text" name="sumber" id="sumber" class="form-control" placeholder="Misal: Booking, Sponsor..." required>
                </div>
              </div>
              <div class="col-md-8">
                <div class="form-group">
                  <label for="deskripsi">Deskripsi</label>
                  <textarea name="deskripsi" id="deskripsi" class="form-control" placeholder="Keterangan tambahan..."></textarea>
                </div>
              </div>
              <div class="col-md-4">
                <div class="form-group">
                  <label for="nominal">Nominal (Rp)</label>
                  <input type="number" name="nominal" id="nominal" class="form-control" required>
                </div>
              </div>
            </div>
          </div>
          <div class="card-footer text-right">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
