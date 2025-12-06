<?php
require_once 'auth_check.php';
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
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
      (nama_lapangan, tipe, harga_per_jam, deskripsi, harga_per_jam_member, foto, status, created_at) 
    VALUES 
      ('$nama', '$tipe', '$harga', '$deskripsi', '$harga_member', '$fotoName', '$status', NOW())
  ";

  if (mysqli_query($conn, $query)) {
    include_once('jadwal_sinkronisasi.php');
    $_SESSION['toast_success'] = "Lapangan berhasil ditambahkan dan jadwal otomatis dibuat!";
    header("Location: lapangan.php");
    exit;
  } else {
    $_SESSION['toast_error'] = "Gagal menambahkan data lapangan: " . mysqli_error($conn);
  }
}
?>

<style>
:root {
    --primary-blue: linear-gradient(135deg, #0e5c91 0%, #2196f3 100%);
    --success-blue: linear-gradient(135deg, #1e88e5 0%, #42a5f5 100%);
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
    border-left: 4px solid #2196f3;
}

.page-title {
    font-weight: 700;
    color: #0d47a1;
    margin: 0;
}

.form-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(14, 92, 145, 0.15);
    border: none;
}

.card-header-gradient {
    background: linear-gradient(90deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
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

.upload-zone {
    border: 2px dashed #2196f3;
    border-radius: 12px;
    padding: 2rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background: #e3f2fd;
}

.upload-zone:hover {
    background: #bbdefb;
    border-color: #0e5c91;
}

.upload-zone i {
    font-size: 3rem;
    color: #2196f3;
    margin-bottom: 1rem;
}

.preview-container {
    position: relative;
    display: inline-block;
    margin-top: 1rem;
}

.preview-image {
    width: 200px;
    height: 150px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
    border: 2px solid #2196f3;
}

.info-box {
    background: var(--light-blue);
    color: white;
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
}

.info-box i {
    font-size: 1.5rem;
    margin-right: 1rem;
}

.btn-save {
    background: var(--success-blue);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.75rem 2rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(30, 136, 229, 0.3);
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(30, 136, 229, 0.4);
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
}
</style>

<div class="content-wrapper">
  <section class="content-header mb-4">
    <div class="container-fluid">
      <div class="page-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <div class="mb-3 mb-md-0">
            <h1 class="page-title">
              <i class="fas fa-plus-circle me-2" style="color: #2196f3;"></i> 
              Tambah Data Lapangan
            </h1>
            <p class="text-muted mb-0 mt-2">
              <i class="fas fa-info-circle me-2"></i>
              Tambahkan lapangan baru untuk sistem booking
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
      <form action="" method="POST" enctype="multipart/form-data" id="formAddLapangan">
        <div class="card form-card">
          <div class="card-header card-header-gradient text-white">
            <h5 class="mb-0">
              <i class="fas fa-futbol me-2"></i> Formulir Tambah Lapangan
            </h5>
          </div>

          <div class="form-section">
            
            <div class="info-box">
              <div class="d-flex align-items-center">
                <i class="fas fa-lightbulb"></i>
                <div>
                  <strong class="d-block mb-1">Tips Pengisian Form</strong>
                  <small>Isi semua data dengan lengkap. Jadwal booking akan otomatis dibuat setelah lapangan ditambahkan.</small>
                </div>
              </div>
            </div>

            <div class="row">
              <!-- Nama Lapangan -->
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-tag"></i>
                    Nama Lapangan <span class="text-danger">*</span>
                  </label>
                  <input type="text" name="nama_lapangan" class="form-control" 
                         placeholder="Contoh: Lapangan A" required>
                </div>
              </div>

              <!-- Tipe Lapangan -->
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-layer-group"></i>
                    Tipe Lapangan <span class="text-danger">*</span>
                  </label>
                  <select name="tipe" class="form-select" required>
                    <option value="">-- Pilih Tipe --</option>
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
                  <label class="form-label">
                    <i class="fas fa-money-bill-wave"></i>
                    Harga Sewa / Jam (Reguler) <span class="text-danger">*</span>
                  </label>
                  <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="harga_per_jam" class="form-control" 
                           placeholder="50000" min="0" required>
                  </div>
                </div>
              </div>

              <!-- Harga Member -->
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-crown"></i>
                    Harga Sewa / Jam (Member)
                  </label>
                  <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="number" name="harga_member" class="form-control" 
                           placeholder="45000" min="0">
                  </div>
                  <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Kosongkan jika sama dengan harga reguler
                  </small>
                </div>
              </div>

              <!-- Status -->
              <div class="col-md-6">
                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-toggle-on"></i>
                    Status Lapangan <span class="text-danger">*</span>
                  </label>
                  <select name="status" class="form-select" required>
                    <option value="aktif" selected>Aktif</option>
                    <option value="nonaktif">Nonaktif</option>
                  </select>
                  <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Hanya lapangan aktif yang bisa dibooking
                  </small>
                </div>
              </div>

              <!-- Deskripsi -->
              <div class="col-md-12">
                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-align-left"></i>
                    Deskripsi / Catatan
                  </label>
                  <textarea name="deskripsi" class="form-control" rows="4" 
                            placeholder="Tulis deskripsi lapangan, fasilitas, atau catatan khusus..."></textarea>
                </div>
              </div>

              <!-- Foto -->
              <div class="col-md-12">
                <div class="form-group">
                  <label class="form-label">
                    <i class="fas fa-image"></i>
                    Foto Lapangan
                  </label>
                  <div class="upload-zone" onclick="document.getElementById('fotoInput').click()">
                    <i class="fas fa-cloud-upload-alt d-block"></i>
                    <h6 style="color: #0d47a1;">Klik untuk Upload Foto</h6>
                    <small class="text-muted">Format: JPG, PNG, WEBP | Maksimal 2MB</small>
                  </div>
                  <input type="file" name="foto" id="fotoInput" class="d-none" 
                         accept="image/*" onchange="previewPhoto(this)">
                  
                  <div id="previewContainer" class="preview-container d-none">
                    <img id="photoPreview" src="" class="preview-image" alt="Preview">
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
              <i class="fas fa-save me-2"></i> Simpan Data
            </button>
          </div>
        </div>
      </form>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Photo preview
function previewPhoto(input) {
    const file = input.files[0];
    if (file) {
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Format Tidak Valid',
                text: 'Hanya file JPG, PNG, dan WEBP yang diperbolehkan!'
            });
            input.value = '';
            return;
        }
        
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'Ukuran Terlalu Besar',
                text: 'Ukuran file maksimal 2MB!'
            });
            input.value = '';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('photoPreview').src = e.target.result;
            document.getElementById('previewContainer').classList.remove('d-none');
        }
        reader.readAsDataURL(file);
    }
}

// Form validation
document.getElementById('formAddLapangan').addEventListener('submit', function(e) {
    const namaLapangan = this.elements['nama_lapangan'].value.trim();
    const tipe = this.elements['tipe'].value;
    const harga = this.elements['harga_per_jam'].value;
    
    if (!namaLapangan || !tipe || !harga) {
        e.preventDefault();
        Swal.fire({
            icon: 'warning',
            title: 'Data Tidak Lengkap',
            text: 'Mohon lengkapi semua field yang wajib diisi!'
        });
        return false;
    }
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