<?php
require_once 'auth_check.php';
ob_start(); // aktifkan output buffering agar header() bisa dipanggil setelah output
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
// include('../includes/topbar.php');
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

      // gunakan unique filename
      $fileName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $fileExt;
      $targetFile = $uploadDir . $fileName;

      if (!move_uploaded_file($_FILES["foto_profil"]["tmp_name"], $targetFile)) {
        throw new Exception("Gagal mengunggah foto profil!");
      }

      // simpan nama file relatif (agar bisa dipakai di web)
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
      // cek apakah error karena constraint (mis. last_login not null)
      $err = $stmt->error;
      $stmt->close();
      throw new Exception("Gagal menambahkan admin: " . $err);
    }

    $stmt->close();

    // sukses -> redirect ke admin.php supaya spinner tidak macet
    // gunakan header redirect (pastikan ob_start aktif)
    header('Location: admin.php?added=1');
    exit;

  } catch (Exception $e) {
    $errorMsg = $e->getMessage();
    // Pastikan overlay loading tidak macet: overlay default adalah d-none, jadi halaman error akan render normal.
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

          <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
              <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($errorMsg) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" id="formAdmin" novalidate>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required value="<?= isset($_POST['nama']) ? htmlspecialchars($_POST['nama']) : '' ?>">
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
              </div>

              <div class="col-md-6 mb-3">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" class="form-control" maxlength="25" oninput="this.value=this.value.replace(/[^0-9]/g,'');" placeholder="Hanya angka" value="<?= isset($_POST['no_hp']) ? htmlspecialchars($_POST['no_hp']) : '' ?>">
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
              <button type="submit" id="btnSubmit" class="btn btn-success px-4">
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

  // === Tampilkan overlay loading saat submit, dan disable tombol ===
  $('#formAdmin').on('submit', function(e){
    // simple client-side validation: pastikan password cocok sebelum showing overlay
    const pwd = $('#password').val();
    const conf = $('#confirm_password').val();
    if (pwd !== conf) {
      e.preventDefault();
      Swal.fire({ icon: 'error', title: 'Error', text: 'Password dan konfirmasi tidak cocok!' });
      return;
    }

    $('#btnSubmit').prop('disabled', true);
    $('#loadingOverlay').removeClass('d-none').addClass('d-flex');
  });
});
</script>
