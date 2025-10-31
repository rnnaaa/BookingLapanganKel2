<?php
require_once __DIR__ . '/../config/database.php';
session_start();

$id = $_GET['id'] ?? 0;
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM jadwal_waktu WHERE id_jadwal_waktu='$id'"));

if (!$data) {
  $_SESSION['toast_error'] = 'Data tidak ditemukan.';
  header('Location: jadwal_waktu.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $jam_mulai = $_POST['jam_mulai'];
  $jam_selesai = $_POST['jam_selesai'];
  $harga_per_slot = $_POST['harga_per_slot'];

  mysqli_query($conn, "
    UPDATE jadwal_waktu 
    SET jam_mulai='$jam_mulai', jam_selesai='$jam_selesai', harga_per_slot='$harga_per_slot', updated_at=NOW()
    WHERE id_jadwal_waktu='$id'
  ");

  $_SESSION['toast_success'] = 'Jadwal waktu berhasil diperbarui.';
  header('Location: jadwal_waktu.php');
  exit;
}

include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Jadwal Waktu</h1>
      <a href="jadwal_waktu.php" class="btn btn-secondary shadow-sm">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-warning text-white">
          <h3 class="card-title mb-0"><i class="fas fa-edit mr-2"></i> Edit Data Jadwal Waktu</h3>
        </div>
        <div class="card-body">
          <form method="POST">
            <div class="row">
              <div class="col-md-6">
                <label>Jam Mulai</label>
                <input type="time" name="jam_mulai" class="form-control" value="<?= $data['jam_mulai'] ?>" required>
              </div>
              <div class="col-md-6">
                <label>Jam Selesai</label>
                <input type="time" name="jam_selesai" class="form-control" value="<?= $data['jam_selesai'] ?>" required>
              </div>
            </div>

            <div class="form-group mt-3">
              <label>Harga per Slot</label>
              <input type="number" name="harga_per_slot" class="form-control" value="<?= $data['harga_per_slot'] ?>" required>
            </div>

            <button type="submit" class="btn btn-warning mt-3 text-white">
              <i class="fas fa-save mr-1"></i> Simpan Perubahan
            </button>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>
