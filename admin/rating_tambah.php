<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $user_id = $_POST['user_id'];
  $lapangan_id = $_POST['lapangan_id'];
  $nilai = $_POST['nilai'];
  $komentar = $_POST['komentar'];

  $query = "INSERT INTO rating (user_id, lapangan_id, nilai, komentar, created_at)
            VALUES ('$user_id', '$lapangan_id', '$nilai', '$komentar', NOW())";
  if (mysqli_query($conn, $query)) {
    echo "<script>alert('Rating berhasil ditambahkan!'); window.location='rating.php';</script>";
  } else {
    echo "<script>alert('Gagal menambahkan rating!');</script>";
  }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-star mr-2 text-warning"></i>Tambah Rating</h1>
      <a href="rating.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-header bg-warning text-white">
          <h3 class="card-title"><i class="fas fa-plus-circle mr-2"></i>Form Tambah Rating</h3>
        </div>
        <form method="POST">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Nama Pengguna</label>
                  <select name="user_id" class="form-control select2" required>
                    <option value="">-- Pilih User --</option>
                    <?php
                    $userQuery = mysqli_query($conn, "SELECT id_user, nama FROM users ORDER BY nama ASC");
                    while ($u = mysqli_fetch_assoc($userQuery)) {
                      echo "<option value='{$u['id_user']}'>{$u['nama']}</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Lapangan</label>
                  <select name="lapangan_id" class="form-control select2" required>
                    <option value="">-- Pilih Lapangan --</option>
                    <?php
                    $lapQuery = mysqli_query($conn, "SELECT id_lapangan, nama_lapangan FROM lapangan ORDER BY nama_lapangan ASC");
                    while ($l = mysqli_fetch_assoc($lapQuery)) {
                      echo "<option value='{$l['id_lapangan']}'>{$l['nama_lapangan']}</option>";
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <label>Nilai Rating</label>
                <select name="nilai" class="form-control" required>
                  <option value="">-- Pilih Nilai --</option>
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> ⭐</option>
                  <?php endfor; ?>
                </select>
              </div>
              <div class="col-md-12 mt-3">
                <div class="form-group">
                  <label>Komentar</label>
                  <textarea name="komentar" class="form-control" rows="3" placeholder="Tulis komentar..." required></textarea>
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
