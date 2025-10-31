<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $tanggal = $_POST['tanggal'];
  $kategori = $_POST['kategori'];
  $deskripsi = $_POST['deskripsi'];
  $jumlah = $_POST['jumlah'];
  $keterangan = $_POST['keterangan'];

  if (!empty($tanggal) && !empty($kategori) && !empty($jumlah)) {
    $query = "INSERT INTO pengeluaran (tanggal, kategori, deskripsi, jumlah, keterangan, created_at)
              VALUES ('$tanggal', '$kategori', '$deskripsi', '$jumlah', '$keterangan', NOW())";
    if (mysqli_query($conn, $query)) {
      $_SESSION['toast_success'] = 'Pengeluaran berhasil ditambahkan!';
      header('Location: pengeluaran.php');
      exit;
    } else {
      $_SESSION['toast_error'] = 'Gagal menambahkan data!';
    }
  } else {
    $_SESSION['toast_error'] = 'Field wajib diisi!';
  }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-plus-circle mr-2"></i> Tambah Pengeluaran</h1>
      <a href="pengeluaran.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm border-0">
        <div class="card-header bg-danger text-white">
          <h3 class="card-title"><i class="fas fa-receipt mr-2"></i> Form Tambah Pengeluaran</h3>
        </div>
        <form method="POST">
          <div class="card-body">
            <div class="row">
              <div class="col-md-4">
                <label>Tanggal</label>
                <input type="date" name="tanggal" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label>Kategori</label>
                <input type="text" name="kategori" class="form-control" placeholder="Misal: Operasional, Perawatan..." required>
              </div>
              <div class="col-md-4">
                <label>Jumlah (Rp)</label>
                <input type="number" name="jumlah" class="form-control" required>
              </div>
              <div class="col-md-6 mt-3">
                <label>Deskripsi</label>
                <textarea name="deskripsi" class="form-control" placeholder="Tuliskan detail pengeluaran..."></textarea>
              </div>
              <div class="col-md-6 mt-3">
                <label>Keterangan</label>
                <textarea name="keterangan" class="form-control" placeholder="Tambahan catatan..."></textarea>
              </div>
            </div>
          </div>
          <div class="card-footer text-right">
            <button type="submit" class="btn btn-danger"><i class="fas fa-save"></i> Simpan</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
