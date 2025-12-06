<?php
require_once 'auth_check.php';
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama_lapangan = trim($_POST['nama_lapangan']);
  $tipe = trim($_POST['tipe']);
  $harga_per_jam = floatval($_POST['harga_per_jam']);
  $harga_member = floatval($_POST['harga_member']);
  $deskripsi = trim($_POST['deskripsi']);
  $status = $_POST['status'];
  $fotoName = $data['foto'];

  if (!empty($_FILES['foto']['name'])) {
    $targetDir = "../uploads/lapangan/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $validExt = ['jpg', 'jpeg', 'png', 'webp'];
    if (in_array($ext, $validExt)) {
      $newName = 'lap_' . time() . '.' . $ext;
      $targetFile = $targetDir . $newName;

      if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
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
          harga_per_jam_member='$harga_member',
          deskripsi='$deskripsi',
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

<style>
:root {
    --primary-blue: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    --info-blue: linear-gradient(135deg, #1565c0 0%, #1976d2 100%);
    --light-blue: linear-gradient(135deg, #4fc3f7 0%, #29b6f6 100%);
}

.content-wrapper {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    min-height: 100vh;
    padding: 2rem 1rem;
}

.page-header {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(14, 92, 145, 0.15);
    margin-bottom: 1.5rem;
    border-left: 4px solid #1976d2;
}

.page-title {
    font-weight: 700;
    color: #0d47a1;
    margin: 0;
}

.edit-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(14, 92, 145, 0.15);
    border: none;
}

.card-header-gradient {
    background: var(--info-blue);
    padding: 1.5rem;
    border: none;
}

.form-section {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    font-weight: 600;
    color: #0d47a1;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    font-size: 0.9rem;
}

.form-label i {
    margin-right: 0.5rem;
    color: #2196f3;
}

.form-control,
.form-select {
    border-radius: 8px;
    border: 2px solid #bbdefb;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
}

.form-control:focus,
.form-select:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 0.2rem rgba(33, 150, 243, 0.25);
}

.input-group-text {
    background: #e3f2fd;
    border: 2px solid #bbdefb;
    border-right: none;
    color: #0e5c91;
    font-weight: 600;
}

.photo-upload-section {
    background: #e3f2fd;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    border: 2px solid #2196f3;
}

.current-photo {
    position: relative;
    display: inline-block;
    margin-bottom: 1.5rem;
}

.photo-preview {
    width: 250px;
    height: 180px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
    transition: all 0.3s ease;
    border: 3px solid #2196f3;
}

.photo-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(33, 150, 243, 0.8);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.current-photo:hover .photo-overlay {
    opacity: 1;
}

.photo-overlay i {
    color: white;
    font-size: 2rem;
}

.change-photo-btn {
    background: var(--info-blue);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.625rem 1.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 2px 4px rgba(21, 101, 192, 0.3);
}

.change-photo-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(21, 101, 192, 0.4);
}

.info-alert {
    background: var(--light-blue);
    color: white;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.btn-save {
    background: var(--primary-blue);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(14, 92, 145, 0.3);
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(14, 92, 145, 0.4);
    color: white;
}

.btn-cancel {
    background: #607d8b;
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    background: #546e7a;
    transform: translateY(-2px);
    color: white;
}

@media (max-width: 768px) {
    .content-wrapper {
        padding: 1rem 0.5rem;
    }
    
    .form-section {
        padding: 1.5rem;
    }
    
    .photo-preview {
        width: 200px;
        height: 150px;
    }
}
</style>

<div class="content-wrapper">
  <section class="content-header mb-4">
    <div class="container-fluid">
      <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div class="mb-3 mb-md-0">
            <h1 class="page-title">
              <i class="fas fa-edit me-2" style="color: #1976d2;"></i> 
              Edit Data Lapangan
            </h1>
            <p class="text-muted mb-0 mt-2">
              <i class="fas fa-info-circle me-2"></i>
              Perbarui informasi lapangan: <strong><?= htmlspecialchars($data['nama_lapangan']) ?></strong>
            </p>
          </div>
          <a href="lapangan.php" class="btn btn-cancel">
            <i class="fas fa-arrow-left me-2"></i> Kembali
          </a>
        </div>
      </div>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card edit-card">
        <div class="card-header card-header-gradient text-white">
          <h5 class="mb-0">
            <i class="fas fa-pen me-2"></i> Form Edit Lapangan
          </h5>
        </div>

        <form method="POST" enctype="multipart/form-data" id="formEditLapangan">
          <div class="form-section">
            
            <div class="info-alert mb-4">
              <div class="d-flex align-items-center">
                <i class="fas fa-info-circle me-3" style="font-size: 1.5rem;"></i>
                <div>
                  <strong class="d-block mb-1">Perhatian</strong>
                  <small>Perubahan harga tidak akan mempengaruhi booking yang sudah ada sebelumnya.</small>
                </div>
              </div>
            </div>

            <div class="row">
              <!-- Left Column: Form Fields -->
              <div class="col-md-7">
                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-tag"></i>
                    Nama Lapangan <span class="text-danger">*</span>
                  </label>
                  <input type="text" name="nama_lapangan" class="form-control" 
                         value="<?= htmlspecialchars($data['nama_lapangan']) ?>" required>
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-layer-group"></i>
                    Tipe Lapangan <span class="text-danger">*</span>
                  </label>
                  <select name="tipe" class="form-select" required>
                    <option value="standar" <?= $data['tipe'] == 'standar' ? 'selected' : '' ?>>Standar</option>
                    <option value="sintetis" <?= $data['tipe'] == 'sintetis' ? 'selected' : '' ?>>Sintetis</option>
                    <option value="vinyl" <?= $data['tipe'] == 'vinyl' ? 'selected' : '' ?>>Vinyl</option>
                    <option value="karpet" <?= $data['tipe'] == 'karpet' ? 'selected' : '' ?>>Karpet</option>
                  </select>
                </div>

                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">
                        <i class="fas fa-money-bill-wave"></i>
                        Harga / Jam (Reguler)
                      </label>
                      <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="harga_per_jam" class="form-control"
                               value="<?= $data['harga_per_jam'] ?>" required>
                      </div>
                    </div>
                  </div>

                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="form-label">
                        <i class="fas fa-crown"></i>
                        Harga / Jam (Member)
                      </label>
                      <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" name="harga_member" class="form-control"
                               value="<?= $data['harga_per_jam_member'] ?>" required>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-toggle-on"></i>
                    Status Lapangan
                  </label>
                  <select name="status" class="form-select">
                    <option value="aktif" <?= $data['status'] == 'aktif' ? 'selected' : '' ?>>
                      Aktif
                    </option>
                    <option value="nonaktif" <?= $data['status'] == 'nonaktif' ? 'selected' : '' ?>>
                      Nonaktif
                    </option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-align-left"></i>
                    Deskripsi / Catatan
                  </label>
                  <textarea name="deskripsi" class="form-control" rows="4" 
                            placeholder="Tulis deskripsi lapangan..."><?= htmlspecialchars($data['deskripsi']) ?></textarea>
                </div>
              </div>

              <!-- Right Column: Photo Upload -->
              <div class="col-md-5">
                <div class="photo-upload-section">
                  <label class="form-label justify-content-center mb-3">
                    <i class="fas fa-image"></i>
                    Foto Lapangan
                  </label>
                  
                  <div class="current-photo">
                    <img id="previewFoto" 
                         src="<?= !empty($data['foto']) ? '../uploads/lapangan/'.$data['foto'] : '../assets/img/no-image.png' ?>" 
                         class="photo-preview"
                         alt="Foto Lapangan">
                    <div class="photo-overlay" onclick="document.getElementById('fotoInput').click()">
                      <i class="fas fa-camera"></i>
                    </div>
                  </div>
                  
                  <input type="file" name="foto" id="fotoInput" class="d-none" accept="image/*">
                  
                  <button type="button" class="change-photo-btn" 
                          onclick="document.getElementById('fotoInput').click()">
                    <i class="fas fa-camera me-2"></i> Ganti Foto
                  </button>
                  
                  <div class="mt-3">
                    <small class="text-muted d-block">
                      <i class="fas fa-info-circle me-1"></i>
                      Format: JPG, PNG, WEBP
                    </small>
                    <small class="text-muted d-block">Maksimal 2MB</small>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer bg-light text-end p-3">
            <a href="lapangan.php" class="btn btn-cancel me-2">
              <i class="fas fa-times me-2"></i> Batal
            </a>
            <button type="submit" class="btn btn-save">
              <i class="fas fa-save me-2"></i> Simpan Perubahan
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Photo preview
const fotoInput = document.getElementById('fotoInput');
const previewFoto = document.getElementById('previewFoto');

fotoInput.addEventListener('change', function() {
    const file = this.files[0];
    if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Format Tidak Valid',
                text: 'Hanya file JPG, PNG, dan WEBP yang diperbolehkan!'
            });
            this.value = '';
            return;
        }
        
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'Ukuran Terlalu Besar',
                text: 'Ukuran file maksimal 2MB!'
            });
            this.value = '';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            previewFoto.style.opacity = '0';
            setTimeout(() => {
                previewFoto.src = e.target.result;
                previewFoto.style.opacity = '1';
            }, 200);
        }
        reader.readAsDataURL(file);
    }
});

// Form confirmation
document.getElementById('formEditLapangan').addEventListener('submit', function(e) {
    e.preventDefault();
    
    Swal.fire({
        title: 'Konfirmasi Perubahan',
        text: 'Yakin ingin menyimpan perubahan data lapangan?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2196f3',
        cancelButtonColor: '#607d8b',
        confirmButtonText: '<i class="fas fa-save me-1"></i> Ya, Simpan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            this.submit();
        }
    });
});

// Toast notifications
<?php if(isset($_SESSION['toast_error'])): ?>
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'error',
        title: '<?= $_SESSION['toast_error'] ?>',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
    <?php unset($_SESSION['toast_error']); ?>
<?php endif; ?>
</script>
<?php ob_end_flush(); ?>