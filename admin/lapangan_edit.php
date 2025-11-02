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
  // Pengambilan dan Sanitasi Data
  $nama_lapangan = mysqli_real_escape_string($conn, trim($_POST['nama_lapangan']));
  $tipe = mysqli_real_escape_string($conn, trim($_POST['tipe']));
  $harga_per_jam = floatval($_POST['harga_per_jam']);
  $harga_member = floatval($_POST['harga_member']);
  $status = $_POST['status'];
  $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
  
  // Ambil nama foto lama dari data yang sudah diambil
  $fotoName = $data['foto']; 
  $isNewPhotoUploaded = false;

  // --- LOGIKA UPLOAD FOTO BARU ---
  if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    // Kunci Perbaikan: Ganti targetDir ke '../assets/images/'
    $uploadDir = "../assets/images/"; 
    
    // Pastikan direktori ada
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            $_SESSION['toast_error'] = "Gagal membuat direktori upload: " . $uploadDir;
            header("Location: lapangan.php");
            exit;
        }
    }

    $file = $_FILES['foto'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Buat nama file unik
    $newFotoName = "lap_" . time() . "." . strtolower($ext);
    $targetFile = $uploadDir . $newFotoName;

    // Pindahkan file baru
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        // Jika berhasil, atur nama foto baru dan tandai bahwa ada foto baru diupload
        $fotoName = $newFotoName;
        $isNewPhotoUploaded = true;
    } else {
        $_SESSION['toast_error'] = "Gagal memindahkan file yang diunggah. Cek izin folder.";
    }
  }

  // --- LOGIKA PENGHAPUSAN FOTO LAMA (Hanya jika foto baru berhasil diupload) ---
  if ($isNewPhotoUploaded && !empty($data['foto']) && $data['foto'] !== $fotoName) {
      $oldFilePath = $uploadDir . $data['foto'];
      if (file_exists($oldFilePath)) {
          unlink($oldFilePath); // Hapus file lama dari server
      }
  }

  // --- SIMPAN PERUBAHAN KE DATABASE ---
  $query = "
    UPDATE lapangan SET 
      nama_lapangan = '$nama_lapangan', 
      tipe = '$tipe', 
      harga_per_jam = '$harga_per_jam', 
      harga_member = '$harga_member', 
      deskripsi = '$deskripsi',
      status = '$status',
      foto = " . ($fotoName ? "'$fotoName'" : "NULL") . ",
      updated_at = NOW()
    WHERE id_lapangan = $id
  ";

  if (mysqli_query($conn, $query)) {
    $_SESSION['toast_success'] = "Data lapangan berhasil diubah!";
    header("Location: lapangan.php");
    exit;
  } else {
    // Hapus foto baru yang terlanjur diupload jika query UPDATE gagal
    if ($isNewPhotoUploaded && file_exists($targetFile)) {
        unlink($targetFile);
    }
    $_SESSION['toast_error'] = "Gagal mengubah data lapangan: " . mysqli_error($conn);
  }
}
// ... (Bagian HTML dan Script di bawah ini tetap sama) ...
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Data Lapangan</h1>
      <a href="lapangan.php" class="btn btn-secondary shadow-sm">
        <i class="fas fa-arrow-left"></i> Kembali
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <form action="" method="POST" enctype="multipart/form-data">
        <div class="card shadow-lg border-0">
          <div class="card-header bg-warning text-white">
            <h3 class="card-title mb-0"><i class="fas fa-futbol mr-2"></i> Formulir Edit Lapangan</h3>
          </div>

          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Nama Lapangan <span class="text-danger">*</span></label>
                  <input type="text" name="nama_lapangan" class="form-control"
                         value="<?= htmlspecialchars($data['nama_lapangan']) ?>" required>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Tipe Lapangan</label>
                  <select name="tipe" class="form-control">
                    <option value="standar" <?= $data['tipe'] == 'standar' ? 'selected' : '' ?>>Standar</option>
                    <option value="sintetis" <?= $data['tipe'] == 'sintetis' ? 'selected' : '' ?>>Sintetis</option>
                    <option value="vinyl" <?= $data['tipe'] == 'vinyl' ? 'selected' : '' ?>>Vinyl</option>
                    <option value="karpet" <?= $data['tipe'] == 'karpet' ? 'selected' : '' ?>>Karpet</option>
                  </select>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Harga Sewa / Jam (Umum) <span class="text-danger">*</span></label>
                  <input type="number" name="harga_per_jam" class="form-control"
                         value="<?= $data['harga_per_jam'] ?>" required>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Harga Sewa / Jam (Member)</label>
                  <input type="number" name="harga_member" class="form-control"
                         value="<?= $data['harga_member'] ?? 0 ?>">
                </div>
              </div>
              
              <div class="col-md-12">
                <div class="form-group">
                  <label>Deskripsi / Catatan</label>
                  <textarea name="deskripsi" class="form-control" rows="3" placeholder="Tulis deskripsi lapangan..."><?= htmlspecialchars($data['deskripsi']) ?></textarea>
                </div>
              </div>


              <div class="col-md-6">
                <div class="form-group">
                  <label>Foto Lapangan Saat Ini</label>
                  <div class="d-flex align-items-end">
                    <?php
                      // Perbaikan Path untuk Tampilan Foto Lama
                      $currentFotoPath = !empty($data['foto']) 
                        ? "../assets/images/" . htmlspecialchars($data['foto'])
                        : "../assets/img/no-image.png";
                    ?>
                    <div class="position-relative mr-3">
                      <img src="<?= $currentFotoPath ?>" 
                           alt="Foto Lapangan Lama" 
                           id="previewFoto"
                           class="img-thumbnail shadow-sm"
                           style="width: 150px; height: 100px; object-fit: cover; cursor: pointer;">
                      
                      <div id="fotoOverlay" class="position-absolute w-100 h-100 bg-dark text-white d-flex align-items-center justify-content-center rounded-sm"
                           style="top: 0; left: 0; opacity: 0; transition: opacity 0.2s; cursor: pointer;">
                          <i class="fas fa-camera fa-2x"></i>
                      </div>
                    </div>
                    
                    <input type="file" name="foto" id="fotoInput" class="form-control-file d-none" accept="image/*">
                  </div>
                  <small class="form-text text-muted mt-2">Pilih file baru jika ingin mengganti foto.</small>
                </div>
              </div>

              <div class="col-md-6">
                <div class="form-group">
                  <label>Status</label>
                  <select name="status" class="form-control">
                    <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer text-right">
            <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> Simpan Perubahan</button>
            <a href="lapangan.php" class="btn btn-secondary"><i class="fas fa-times mr-1"></i> Batal</a>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<script>
$(document).ready(function() {
  const input = $("#fotoInput");
  const preview = $("#previewFoto");
  const overlay = $("#fotoOverlay");

  // Hover efek kamera (menggantikan yang lama)
  preview.parent().hover(
    function() { overlay.css("opacity", "0.5"); }, // Opacity diturunkan agar tidak terlalu gelap
    function() { overlay.css("opacity", "0"); }
  );

  // Klik pada preview memicu klik pada input file
  preview.parent().on("click", function() {
    input.trigger("click");
  });

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
      
      // Tampilkan preview
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.attr('src', e.target.result);
      }
      reader.readAsDataURL(file);
    }
  });
});
</script>
<?php ob_end_flush(); ?>