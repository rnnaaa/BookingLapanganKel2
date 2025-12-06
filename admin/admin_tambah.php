<?php
// admin_tambah.php
require_once 'auth_check.php';
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');
date_default_timezone_set('Asia/Jakarta');

$successMsg = '';
$errorMsg = '';

// Helper: sanitize input
function input_trim($s) {
  return trim((string)$s);
}

// === PROSES SIMPAN ADMIN BARU ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    $nama       = input_trim($_POST['nama'] ?? '');
    $email      = input_trim($_POST['email'] ?? '');
    $username   = input_trim($_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirmPwd = $_POST['confirm_password'] ?? '';
    $no_hp      = input_trim($_POST['no_hp'] ?? '');
    $role       = 'admin';
    $status     = 'aktif';
    $fotoProfil = null;

    // Validasi dasar
    if (empty($nama) || empty($email) || empty($username) || empty($password)) {
      throw new Exception("Semua field wajib diisi!");
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      throw new Exception("Format email tidak valid!");
    }
    if ($password !== $confirmPwd) {
      throw new Exception("Konfirmasi password tidak sesuai!");
    }

    // Cek email unik
    $check = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
    if (!$check) throw new Exception("Prepare failed: " . $conn->error);
    $check->bind_param('s', $email);
    if (!$check->execute()) throw new Exception("Execute check failed: " . $check->error);
    $check->store_result();
    if ($check->num_rows > 0) {
      $check->close();
      throw new Exception("Email sudah digunakan oleh pengguna lain!");
    }
    $check->close();

    // === Upload Foto Profil (optional) ===
    if (!empty($_FILES['foto_profil']['name'])) {
      $uploadDir = __DIR__ . "/../uploads/users/";
      if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
          throw new Exception("Gagal membuat direktori upload.");
        }
      }

      $originalName = basename($_FILES["foto_profil"]["name"]);
      $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
      $allowed = ['jpg', 'jpeg', 'png', 'gif'];
      if (!in_array($fileExt, $allowed)) {
        throw new Exception("Format foto harus JPG, JPEG, PNG, atau GIF!");
      }
      if ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
        throw new Exception("Ukuran foto maksimal 2MB!");
      }

      $fileName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $fileExt;
      $targetFile = $uploadDir . $fileName;

      if (!move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $targetFile)) {
        throw new Exception("Gagal mengunggah foto profil!");
      }

      $fotoProfil = $fileName;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // === INSERT ===
    $stmt = $conn->prepare("
      INSERT INTO users 
      (nama, username, email, password, no_hp, role, status, foto_profil, is_verified, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())
    ");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $fotoBind = $fotoProfil ?? null;

    $stmt->bind_param('ssssssss', 
        $nama, 
        $username, 
        $email, 
        $hashedPassword, 
        $no_hp, 
        $role, 
        $status, 
        $fotoBind
    );
    
    if (!$stmt->execute()) {
      $err = $stmt->error;
      $stmt->close();
      throw new Exception("Gagal menambahkan admin: " . $err);
    }

    $stmt->close();

    header('Location: admin.php?added=1');
    exit;

  } catch (Exception $e) {
    $errorMsg = $e->getMessage();
  }
}
?>

<style>
/* Professional Admin Form Styling */
:root {
    --primary-gradient: linear-gradient(135deg, #0e5c91 0%, #1874ad 50%, #2196f3 100%);
    --success-gradient: linear-gradient(135deg, #28a745 0%, #218838 100%);
    --card-shadow: 0 4px 20px rgba(14, 92, 145, 0.15);
    --card-hover-shadow: 0 8px 30px rgba(14, 92, 145, 0.25);
}

.animate-fade-in {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Content Header Enhancement */
.content-header {
    margin-bottom: 2rem;
}

.content-header h1 {
    font-size: 1.875rem;
    font-weight: 700;
    color: #2c3e50;
    letter-spacing: -0.5px;
    margin: 0;
}

.content-header h1 i {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Button Back Enhancement */
.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(108, 117, 125, 0.3);
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
}

/* Main Card Enhancement */
.admin-form-card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.admin-form-card:hover {
    box-shadow: var(--card-hover-shadow);
}

.admin-form-card .card-header {
    background: var(--primary-gradient);
    padding: 1.75rem;
    border: none;
}

.admin-form-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

.admin-form-card .card-body {
    padding: 2.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

/* Loading Overlay Enhancement */
#loadingOverlay {
    border-radius: 20px;
    backdrop-filter: blur(5px);
}

#loadingOverlay .spinner-border {
    border-width: 4px;
}

/* Alert Enhancement */
.alert {
    border-radius: 12px;
    border: none;
    padding: 1.25rem 1.5rem;
    font-size: 0.938rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    margin-bottom: 1.5rem;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Form Labels */
.form-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    letter-spacing: 0.3px;
    display: block;
}

.form-label::after {
    content: ' *';
    color: #dc3545;
    font-weight: 700;
}

/* Form Controls */
.form-control {
    border: 2px solid #e3e6f0;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.938rem;
    transition: all 0.3s ease;
    background: #ffffff;
}

.form-control:focus {
    border-color: #2196f3;
    box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.1);
    background: #ffffff;
}

.form-control[readonly] {
    background: linear-gradient(135deg, #f8f9fc 0%, #e9ecf4 100%);
    color: #6c757d;
    font-weight: 600;
}

/* Input Group Enhancement */
.input-group {
    border-radius: 10px;
    overflow: hidden;
}

.input-group .form-control {
    border-right: none;
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-group .btn-outline-secondary {
    border: 2px solid #e3e6f0;
    border-left: none;
    background: linear-gradient(135deg, #f8f9fc 0%, #e9ecf4 100%);
    color: #5a5c69;
    transition: all 0.3s ease;
}

.input-group .btn-outline-secondary:hover {
    background: linear-gradient(135deg, #e9ecf4 0%, #d1d3e2 100%);
    color: #2196f3;
}

/* File Input Enhancement */
input[type="file"].form-control {
    padding: 0.5rem 1rem;
}

input[type="file"].form-control::file-selector-button {
    background: var(--primary-gradient);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    margin-right: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

input[type="file"].form-control::file-selector-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(33, 150, 243, 0.4);
}

/* Small Text */
small.text-muted {
    font-size: 0.813rem;
    color: #6c757d;
    display: block;
    margin-top: 0.25rem;
}

/* Button Group */
.btn-success {
    background: var(--success-gradient);
    border: none;
    border-radius: 10px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.btn-success:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

.btn-success:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-outline-secondary {
    border: 2px solid #e3e6f0;
    border-radius: 10px;
    padding: 0.75rem 2rem;
    font-weight: 600;
    letter-spacing: 0.3px;
    color: #6c757d;
    transition: all 0.3s ease;
}

.btn-outline-secondary:hover {
    background: linear-gradient(135deg, #f8f9fc 0%, #e9ecf4 100%);
    border-color: #2196f3;
    color: #2196f3;
}

/* Row Spacing */
.row .col-md-6 {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .content-header h1 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .content-header .btn-secondary {
        width: 100%;
    }
    
    .admin-form-card .card-body {
        padding: 1.5rem;
    }
    
    .btn-success,
    .btn-outline-secondary {
        width: 100%;
        margin-bottom: 0.5rem;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
      <div class="mb-3 mb-md-0">
        <h1><i class="fas fa-user-shield me-2"></i> Tambah Admin Baru</h1>
        <p class="text-muted mb-0 mt-2">Buat akun administrator baru untuk sistem</p>
      </div>
      <a href="admin.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
      </a>
    </div>
  </section>

  <!-- Content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card admin-form-card">
        <div class="card-header text-white">
          <h3 class="card-title mb-0">
            <i class="fas fa-plus-circle me-2"></i> Form Tambah Administrator
          </h3>
        </div>

        <div class="card-body position-relative">
          <!-- Spinner Loading -->
          <div id="loadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" style="background: rgba(255,255,255,0.95); z-index: 100;">
            <div class="text-center">
              <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
              </div>
              <p class="fw-bold text-dark">Menyimpan data administrator...</p>
              <small class="text-muted">Mohon tunggu sebentar</small>
            </div>
          </div>

          <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
              <i class="fas fa-exclamation-circle me-2"></i> 
              <strong>Error:</strong> <?= htmlspecialchars($errorMsg) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" id="formAdmin" novalidate>
            <div class="row g-4">
              <div class="col-md-6">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" 
                       name="nama" 
                       class="form-control" 
                       required 
                       placeholder="Masukkan nama lengkap"
                       value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Username</label>
                <input type="text" 
                       name="username" 
                       class="form-control" 
                       required 
                       placeholder="Pilih username unik"
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" 
                       name="email" 
                       class="form-control" 
                       required 
                       placeholder="email@example.com"
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">No. HP</label>
                <input type="text" 
                       name="no_hp" 
                       class="form-control" 
                       maxlength="25" 
                       oninput="this.value=this.value.replace(/[^0-9]/g,'');" 
                       placeholder="08xxxxxxxxxx" 
                       value="<?= isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : '' ?>">
                <small class="text-muted">Hanya angka, tanpa spasi atau tanda hubung</small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Password</label>
                <div class="input-group">
                  <input type="password" 
                         name="password" 
                         id="password" 
                         class="form-control" 
                         required 
                         minlength="6"
                         placeholder="Minimal 6 karakter">
                  <button type="button" class="btn btn-outline-secondary" id="togglePwd">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Konfirmasi Password</label>
                <div class="input-group">
                  <input type="password" 
                         name="confirm_password" 
                         id="confirm_password" 
                         class="form-control" 
                         required 
                         minlength="6"
                         placeholder="Ketik ulang password">
                  <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPwd">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>

              <div class="col-md-6">
                <label class="form-label">Foto Profil</label>
                <input type="file" 
                       name="foto_profil" 
                       class="form-control" 
                       accept=".jpg,.jpeg,.png,.gif">
                <small class="text-muted">
                  <i class="fas fa-info-circle me-1"></i>
                  Format: JPG, PNG, GIF | Maksimal 2MB
                </small>
              </div>

              <div class="col-md-6">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="Administrator" readonly>
                <small class="text-muted">Otomatis diatur sebagai Admin</small>
              </div>
            </div>

            <div class="mt-5 text-end">
              <button type="reset" class="btn btn-outline-secondary px-4 me-2">
                <i class="fas fa-redo me-1"></i> Reset
              </button>
              <button type="submit" id="btnSubmit" class="btn btn-success px-4">
                <i class="fas fa-save me-1"></i> Simpan Data
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </section>
</div>

<?php include_once('../includes/footer.php'); ?>

<script>
$(document).ready(function(){
  // === Show/Hide Password ===
  $('#togglePwd').click(function(){
    const input = $('#password');
    const icon = $(this).find('i');
    if(input.attr('type') === 'password'){
      input.attr('type', 'text');
      icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      input.attr('type', 'password');
      icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  $('#toggleConfirmPwd').click(function(){
    const input = $('#confirm_password');
    const icon = $(this).find('i');
    if(input.attr('type') === 'password'){
      input.attr('type', 'text');
      icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      input.attr('type', 'password');
      icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  // === Tampilkan overlay loading saat submit ===
  $('#formAdmin').on('submit', function(e){
    const pwd = $('#password').val();
    const conf = $('#confirm_password').val();
    
    if (pwd !== conf) {
      e.preventDefault();
      Swal.fire({ 
        icon: 'error', 
        title: 'Error Validasi', 
        text: 'Password dan konfirmasi password tidak cocok!',
        confirmButtonColor: '#2196f3'
      });
      return;
    }

    if (pwd.length < 6) {
      e.preventDefault();
      Swal.fire({ 
        icon: 'error', 
        title: 'Error Validasi', 
        text: 'Password minimal 6 karakter!',
        confirmButtonColor: '#2196f3'
      });
      return;
    }

    $('#btnSubmit').prop('disabled', true);
    $('#loadingOverlay').removeClass('d-none').addClass('d-flex');
  });

  // Auto hide alert
  setTimeout(function() {
    $('.alert').fadeOut('slow');
  }, 5000);
});
</script>