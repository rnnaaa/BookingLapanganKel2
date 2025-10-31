<?php
ob_start(); 
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = mysqli_real_escape_string($conn, $_POST['nama_lapangan']);
  $tipe = mysqli_real_escape_string($conn, $_POST['tipe']);
  $harga = floatval($_POST['harga_per_jam']);
  $harga_member = floatval($_POST['harga_member']);
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
  $status = mysqli_real_escape_string($conn, $_POST['status']);

  // Upload foto
  $fotoName = null;
  if (!empty($_FILES['foto']['name'])) {
    $uploadDir = "../uploads/lapangan/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $ext = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
    $fotoName = "lap_" . time() . "." . strtolower($ext);
    move_uploaded_file($_FILES['foto']['tmp_name'], $uploadDir . $fotoName);
  }

  // Simpan ke database
  $query = "
    INSERT INTO lapangan 
      (nama_lapangan, tipe, harga_per_jam, deskripsi, harga_member, foto, status, created_at) 
    VALUES 
      ('$nama', '$tipe', '$harga', '$deskripsi', '$harga_member', '$fotoName', '$status', NOW())
  ";

  if (mysqli_query($conn, $query)) {
    $_SESSION['toast_success'] = "Data lapangan berhasil ditambahkan!";
    header("Location: lapangan.php");
    exit;
  } else {
    $_SESSION['toast_error'] = "Gagal menambahkan data lapangan: " . mysqli_error($conn);
  }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-plus-circle mr-2"></i> Tambah Data Lapangan</h1>
      <a href="lapangan.php" class="btn btn-secondary shadow-sm">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <form action="" method="POST" enctype="multipart/form-data">
        <div class="card shadow-lg border-0">
          <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0"><i class="fas fa-futbol mr-2"></i> Formulir Tambah Lapangan</h3>
          </div>

          <div class="card-body">
            <div class="row">
              <!-- Nama Lapangan -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Nama Lapangan <span class="text-danger">*</span></label>
                  <input type="text" name="nama_lapangan" class="form-control" required>
                </div>
              </div>

              <!-- Tipe Lapangan -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Tipe Lapangan</label>
                  <select name="tipe" class="form-control">
                    <option value="standar">Standar</option>
                    <option value="sintetis">Sintetis</option>
                    <option value="vinyl">Vinyl</option>
                    <option value="karpet">Karpet</option>
                  </select>
                </div>
              </div>

              <!-- Harga Umum -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Harga Sewa / Jam (Umum) <span class="text-danger">*</span></label>
                  <input type="number" name="harga_per_jam" class="form-control" required>
                </div>
              </div>

              <!-- Harga Member -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Harga Sewa / Jam (Member)</label>
                  <input type="number" name="harga_member" class="form-control" placeholder="0 jika sama seperti umum">
                </div>
              </div>

              <!-- Deskripsi -->
              <div class="col-md-12">
                <div class="form-group">
                  <label>Deskripsi / Catatan</label>
                  <textarea name="deskripsi" class="form-control" rows="3" placeholder="Tulis deskripsi lapangan..."></textarea>
                </div>
              </div>

              <!-- Foto -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Upload Foto Lapangan</label>
                  <input type="file" name="foto" class="form-control-file" accept="image/*">
                  <small class="form-text text-muted">Format: JPG/PNG | Maks. 2MB</small>
                </div>
              </div>

              <!-- Status -->
              <div class="col-md-6">
                <div class="form-group">
                  <label>Status</label>
                  <select name="status" class="form-control">
                    <option value="aktif">Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer text-right">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save mr-1"></i> Simpan Data
            </button>
            <a href="lapangan.php" class="btn btn-secondary">Batal</a>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
