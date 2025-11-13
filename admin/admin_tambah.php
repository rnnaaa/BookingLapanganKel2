<?php
ob_start(); // aktifkan output buffering
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
date_default_timezone_set('Asia/Jakarta');

$successMsg = '';
$errorMsg = '';

// === PROSES SIMPAN ADMIN BARU ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama       = trim($_POST['nama']);
  $email      = trim($_POST['email']);
  $username   = trim($_POST['username']);
  $password   = $_POST['password'];
  $confirmPwd = $_POST['confirm_password'];
  $no_hp      = trim($_POST['no_hp']);
  $role       = 'admin';
  $status     = 'aktif';
  $fotoProfil = null;

  // Validasi dasar
  if (empty($nama) || empty($email) || empty($username) || empty($password)) {
    $errorMsg = "Semua field wajib diisi!";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errorMsg = "Format email tidak valid!";
  } elseif ($password !== $confirmPwd) {
    $errorMsg = "Konfirmasi password tidak sesuai!";
  } else {
    // Cek email unik
    $check = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
      $errorMsg = "Email sudah digunakan oleh pengguna lain!";
    } else {
      // === Upload Foto Profil ===
      if (!empty($_FILES['foto_profil']['name'])) {
        $uploadDir = "../uploads/users/";
        if (!file_exists($uploadDir)) {
          mkdir($uploadDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES["foto_profil"]["name"]);
        $targetFile = $uploadDir . $fileName;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileType, $allowed)) {
          $errorMsg = "Format foto harus JPG, JPEG, PNG, atau GIF!";
        } elseif ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
          $errorMsg = "Ukuran foto maksimal 2MB!";
        } elseif (move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $targetFile)) {
          $fotoProfil = $fileName;
        } else {
          $errorMsg = "Gagal mengunggah foto profil!";
        }
      }

      // Jika tidak ada error upload
      if (!$errorMsg) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
          INSERT INTO users (nama, username, email, password, no_hp, role, status, foto_profil, created_at)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param('ssssssss', $nama, $username, $email, $hashedPassword, $no_hp, $role, $status, $fotoProfil);

        if ($stmt->execute()) {
          $successMsg = "Admin baru berhasil ditambahkan!";
        } else {
          $errorMsg = "Gagal menambahkan admin: " . $conn->error;
        }
      }
    }
    $check->close();
  }
}
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <!-- Header -->
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-user-shield mr-2"></i> Tambah Admin Baru</h1>
      <a href="admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <!-- Content -->
  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
          <h3 class="card-title mb-0"><i class="fas fa-plus-circle mr-2"></i> Form Tambah Administrator</h3>
        </div>

        <div class="card-body position-relative">
          <!-- Spinner Loading -->
          <div id="loadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" style="background: rgba(255,255,255,0.8); z-index: 100;">
            <div class="text-center">
              <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
              <p class="fw-bold text-dark">Menyimpan data...</p>
            </div>
          </div>

          <?php if ($successMsg): ?>
            <div class="alert alert-success alert-dismissible fade show">
              <i class="fas fa-check-circle me-2"></i> <?= $successMsg ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <script>
              setTimeout(() => { window.location.href = 'admin.php'; }, 1500);
            </script>
          <?php elseif ($errorMsg): ?>
            <div class="alert alert-danger alert-dismissible fade show">
              <i class="fas fa-exclamation-circle me-2"></i> <?= $errorMsg ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" id="formAdmin">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" class="form-control" maxlength="25" oninput="this.value=this.value.replace(/[^0-9]/g,'');" placeholder="Hanya angka">
              </div>

              <div class="col-md-6 mb-3 position-relative">
                <label class="form-label">Password</label>
                <div class="input-group">
                  <input type="password" name="password" id="password" class="form-control" required minlength="6">
                  <button type="button" class="btn btn-outline-secondary" id="togglePwd"><i class="fas fa-eye"></i></button>
                </div>
              </div>

              <div class="col-md-6 mb-3 position-relative">
                <label class="form-label">Konfirmasi Password</label>
                <div class="input-group">
                  <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                  <button type="button" class="btn btn-outline-secondary" id="toggleConfirmPwd"><i class="fas fa-eye"></i></button>
                </div>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Foto Profil</label>
                <input type="file" name="foto_profil" class="form-control" accept=".jpg,.jpeg,.png,.gif">
                <small class="text-muted">Maksimal 2MB (JPG, PNG, GIF)</small>
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="Admin" readonly>
              </div>
            </div>

            <div class="mt-4 text-end">
              <button type="submit" class="btn btn-success px-4">
                <i class="fas fa-save me-2"></i> Simpan
              </button>
              <button type="reset" class="btn btn-outline-secondary px-4">Reset</button>
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
  $('#formAdmin').on('submit', function(){
    $('#loadingOverlay').removeClass('d-none').addClass('d-flex');
  });

  // === Jika PHP kirim pesan sukses, tampilkan loading lalu redirect ===
  <?php if (!empty($successMsg)): ?>
    $('#loadingOverlay').removeClass('d-none').addClass('d-flex');
    setTimeout(() => {
      $('#loadingOverlay').html(`
        <div class="text-center">
          <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
          <p class="fw-bold text-dark mt-3">Berhasil disimpan!</p>
        </div>
      `);
      setTimeout(() => {
        window.location.href = 'admin.php';
      }, 1000);
    }, 800);
  <?php endif; ?>
});
</script>

