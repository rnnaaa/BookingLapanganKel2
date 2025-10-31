<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

if (!isset($_GET['id'])) {
  $_SESSION['toast_error'] = "ID lapangan tidak ditemukan.";
  header('Location: lapangan.php');
  exit;
}

$id = intval($_GET['id']);
$query = mysqli_query($conn, "SELECT * FROM lapangan WHERE id_lapangan = $id");
if (mysqli_num_rows($query) == 0) {
  $_SESSION['toast_error'] = "Data lapangan tidak ditemukan.";
  header('Location: lapangan.php');
  exit;
}

$data = mysqli_fetch_assoc($query);

// Jika disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_lapangan = trim($_POST['nama_lapangan']);
  $tipe = trim($_POST['tipe']);
  $harga_per_jam = floatval($_POST['harga_per_jam']);
  $harga_member = floatval($_POST['harga_member']);
  $status = $_POST['status'];
  $fotoName = $data['foto'];

  // Upload foto baru jika ada
  if (!empty($_FILES['foto']['name'])) {
    $targetDir = "../uploads/lapangan/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $validExt = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array($ext, $validExt)) {
      $newName = 'lap_' . time() . '.' . $ext;
      $targetFile = $targetDir . $newName;

      if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
        // Hapus foto lama
        if (!empty($data['foto']) && file_exists($targetDir . $data['foto'])) {
          unlink($targetDir . $data['foto']);
        }
        $fotoName = $newName;
      }
    }
  }

  $sql = "UPDATE lapangan SET 
          nama_lapangan='$nama_lapangan',
          tipe='$tipe',
          harga_per_jam='$harga_per_jam',
          harga_member='$harga_member',
          status='$status',
          foto='$fotoName',
          updated_at=NOW()
        WHERE id_lapangan='$id'";

  if (mysqli_query($conn, $sql)) {
    $_SESSION['toast_success'] = "Data lapangan berhasil diperbarui!";
    header("Location: lapangan.php");
    exit;
  } else {
    $_SESSION['toast_error'] = "Gagal memperbarui data lapangan.";
  }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Data Lapangan</h1>
      <a href="lapangan.php" class="btn btn-outline-secondary shadow-sm">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-warning text-dark">
          <h3 class="card-title mb-0"><i class="fas fa-pen"></i> Form Edit Lapangan</h3>
        </div>

        <form method="POST" enctype="multipart/form-data" id="formEditLapangan">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Nama Lapangan <span class="text-danger">*</span></label>
                  <input type="text" name="nama_lapangan" class="form-control" 
                    value="<?= htmlspecialchars($data['nama_lapangan']) ?>" required>
                </div>

                <div class="form-group">
                  <label>Tipe Lapangan</label>
                  <input type="text" name="tipe" class="form-control"
                    value="<?= htmlspecialchars($data['tipe']) ?>" required>
                </div>

                <div class="form-group">
                  <label>Harga / Jam (Reguler)</label>
                  <input type="number" name="harga_per_jam" class="form-control"
                    value="<?= $data['harga_per_jam'] ?>" required>
                </div>

                <div class="form-group">
                  <label>Harga / Jam (Member)</label>
                  <input type="number" name="harga_member" class="form-control"
                    value="<?= $data['harga_member'] ?>" required>
                </div>

                <div class="form-group">
                  <label>Status</label>
                  <select name="status" class="form-control">
                    <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                  </select>
                </div>
              </div>

              <div class="col-md-6 text-center">
                <label>Foto Lapangan</label>
                <div class="position-relative d-inline-block mb-3">
                  <img id="previewFoto" 
                       src="<?= !empty($data['foto']) ? '../uploads/lapangan/'.$data['foto'] : '../assets/img/no-image.png' ?>" 
                       class="rounded border" 
                       style="width:220px; height:150px; object-fit:cover;">
                  <div id="fotoOverlay" class="position-absolute w-100 h-100 top-0 start-0 d-flex justify-content-center align-items-center bg-dark bg-opacity-50 text-white" style="opacity:0; transition:0.3s;">
                    <i class="fas fa-camera fa-2x"></i>
                  </div>
                </div>
                <input type="file" name="foto" id="fotoInput" class="form-control" accept="image/*">
                <small class="text-muted d-block mt-1">Format: JPG, PNG, WEBP (maks 2MB)</small>
              </div>
            </div>
          </div>

          <div class="card-footer text-right">
            <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
            <a href="lapangan.php" class="btn btn-secondary"><i class="fas fa-times mr-1"></i> Batal</a>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<!-- Preview Foto + Validasi -->
<script>
$(document).ready(function() {
  const input = $("#fotoInput");
  const preview = $("#previewFoto");
  const overlay = $("#fotoOverlay");

  // Hover efek kamera
  preview.parent().hover(
    function() { overlay.css("opacity", "1"); },
    function() { overlay.css("opacity", "0"); }
  );

  // Preview otomatis
  input.on("change", function() {
    const file = this.files[0];
    if (file) {
      const allowed = ['image/jpeg', 'image/png', 'image/webp'];
      if (!allowed.includes(file.type)) {
        toastr.error("Format gambar tidak didukung!");
        $(this).val('');
        return;
      }
      if (file.size > 2 * 1024 * 1024) {
        toastr.error("Ukuran gambar maksimal 2MB!");
        $(this).val('');
        return;
      }
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.hide().attr("src", e.target.result).fadeIn(300);
      }
      reader.readAsDataURL(file);
    }
  });
});
</script>
<?php ob_end_flush(); ?>
