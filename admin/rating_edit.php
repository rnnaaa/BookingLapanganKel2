<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

$id = $_GET['id'];
$data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM rating WHERE rating_id='$id'"));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nilai = $_POST['nilai'];
  $komentar = $_POST['komentar'];
  mysqli_query($conn, "UPDATE rating SET nilai='$nilai', komentar='$komentar' WHERE id_rating='$id'");
  echo "<script>alert('Rating berhasil diperbarui!'); window.location='rating.php';</script>";
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i>Edit Rating</h1>
      <a href="rating.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-header bg-warning text-white">
          <h3 class="card-title"><i class="fas fa-pen mr-2"></i>Form Edit Rating</h3>
        </div>
        <form method="POST">
          <div class="card-body">
            <div class="form-group">
              <label>Nilai Rating</label>
              <select name="nilai" class="form-control" required>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <option value="<?= $i ?>" <?= ($i == $data['nilai']) ? 'selected' : '' ?>><?= $i ?> ⭐</option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Komentar</label>
              <textarea name="komentar" class="form-control" rows="3"><?= htmlspecialchars($data['komentar']) ?></textarea>
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
