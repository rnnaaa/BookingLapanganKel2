<?php
// admin_edit.php
require_once 'auth_check.php';
ob_start(); 
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/sidebar.php');
date_default_timezone_set('Asia/Jakarta');

$errorMsg = '';
$successMsg = '';

// Ambil ID Admin dari URL
$id_user = intval($_GET['id'] ?? 0);
if ($id_user <= 0) {
    die("ID Admin tidak valid!");
}

// =====================
// AMBIL DATA USER
// =====================
$stmt = $conn->prepare("SELECT * FROM users WHERE id_user = ? LIMIT 1");
$stmt->bind_param("i", $id_user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Admin tidak ditemukan!");
}

$data = $result->fetch_assoc();
$stmt->close();

$oldFoto = $data['foto_profil'];

// =====================
// PROSES UPDATE DATA
// =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nama       = trim($_POST['nama']);
        $email      = trim($_POST['email']);
        $username   = trim($_POST['username']);
        $no_hp      = trim($_POST['no_hp']);
        $password   = $_POST['password'];
        $confirmPwd = $_POST['confirm_password'];

        // Validasi
        if (!empty($password) && $password !== $confirmPwd) {
            throw new Exception("Konfirmasi password tidak cocok!");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Format email tidak valid!");
        }

        // Cek email unik (kecuali dirinya sendiri)
        $check = $conn->prepare("SELECT id_user FROM users WHERE email = ? AND id_user != ?");
        $check->bind_param("si", $email, $id_user);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            throw new Exception("Email sudah digunakan!");
        }
        $check->close();

        // === Upload Foto (opsional) ===
        $finalFoto = $oldFoto;

        if (!empty($_FILES['foto_profil']['name'])) {
            $uploadDir = __DIR__ . "/../uploads/users/";
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $ext = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (!in_array($ext, $allowed)) {
                throw new Exception("Format foto tidak valid!");
            }

            if ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
                throw new Exception("Ukuran foto maksimal 2MB!");
            }

            $newFile = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $target = $uploadDir . $newFile;

            if (!move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target)) {
                throw new Exception("Upload foto gagal!");
            }

            if (!empty($oldFoto) && file_exists($uploadDir . $oldFoto)) {
                unlink($uploadDir . $oldFoto);
            }

            $finalFoto = $newFile;
        }

        // === UPDATE STATEMENT ===
        if (!empty($password)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            $query = "
                UPDATE users SET
                    nama = ?, username = ?, email = ?, no_hp = ?, foto_profil = ?, 
                    password = ?, is_verified = 1
                WHERE id_user = ?
            ";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssi", $nama, $username, $email, $no_hp, $finalFoto, $hashed, $id_user);
        } else {
            $query = "
                UPDATE users SET
                    nama = ?, username = ?, email = ?, no_hp = ?, foto_profil = ?, 
                    is_verified = 1
                WHERE id_user = ?
            ";

            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssi", $nama, $username, $email, $no_hp, $finalFoto, $id_user);
        }

        if (!$stmt->execute()) {
            throw new Exception("Gagal update: " . $stmt->error);
        }

        $stmt->close();

        header("Location: admin.php?updated=1");
        exit;

    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}
?>

<style>
/* Professional Admin Edit Styling */
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

/* Content Header */
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

/* Button Back */
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

/* Main Card */
.edit-card {
    border-radius: 20px;
    border: none;
    box-shadow: var(--card-shadow);
    overflow: hidden;
    transition: all 0.3s ease;
}

.edit-card:hover {
    box-shadow: var(--card-hover-shadow);
}

.edit-card .card-header {
    background: var(--primary-gradient);
    padding: 1.75rem;
    border: none;
}

.edit-card .card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    letter-spacing: 0.3px;
}

.edit-card .card-body {
    padding: 2.5rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8f9fc 100%);
}

/* Alert */
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
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
    color: #721c24;
    border-left: 4px solid #dc3545;
}

/* Form Elements */
label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 0.5rem;
    letter-spacing: 0.3px;
    display: block;
}

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

/* File Input */
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

/* Photo Preview */
.photo-preview {
    margin-top: 1rem;
    padding: 1rem;
    background: linear-gradient(135deg, #f8f9fc 0%, #ffffff 100%);
    border-radius: 12px;
    border: 2px dashed #e3e6f0;
    display: inline-block;
}

.photo-preview img {
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.photo-preview img:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
}

/* Button */
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

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
}

/* Info Box */
.info-box {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    border-left: 4px solid #2196f3;
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    margin-bottom: 1.5rem;
}

.info-box i {
    color: #2196f3;
    font-size: 1.25rem;
    margin-right: 0.75rem;
}

.info-box p {
    margin: 0;
    color: #1976d2;
    font-size: 0.938rem;
    font-weight: 500;
}

/* Responsive */
@media (max-width: 768px) {
    .content-header h1 {
        font-size: 1.5rem;
        margin-bottom: 1rem;
    }
    
    .content-header .btn-secondary {
        width: 100%;
    }
    
    .edit-card .card-body {
        padding: 1.5rem;
    }
    
    .btn-success {
        width: 100%;
    }
}
</style>

<div class="content-wrapper animate-fade-in">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center flex-wrap">
      <div class="mb-3 mb-md-0">
        <h1><i class="fas fa-edit me-2"></i> Edit Data Admin</h1>
        <p class="text-muted mb-0 mt-2">Perbarui informasi administrator</p>
      </div>
      <a href="admin.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-1"></i> Kembali
      </a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <div class="card edit-card">
        <div class="card-header text-white">
          <h3 class="card-title mb-0">
            <i class="fas fa-user-edit me-2"></i> Form Edit Administrator
          </h3>
        </div>

        <div class="card-body position-relative">

          <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
              <i class="fas fa-exclamation-circle me-2"></i> 
              <strong>Error:</strong> <?= htmlspecialchars($errorMsg) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <div class="info-box">
            <i class="fas fa-info-circle"></i>
            <p>
              <strong>Info:</strong> Kosongkan field password jika tidak ingin mengubah password. Foto profil bersifat opsional.
            </p>
          </div>

          <form method="POST" enctype="multipart/form-data" id="formEdit">

            <div class="row g-4">
              <div class="col-md-6">
                <label>Nama Lengkap</label>
                <input type="text" 
                       name="nama" 
                       class="form-control" 
                       required 
                       placeholder="Masukkan nama lengkap"
                       value="<?= htmlspecialchars($data['nama']) ?>">
              </div>

              <div class="col-md-6">
                <label>Username</label>
                <input type="text" 
                       name="username" 
                       class="form-control" 
                       required 
                       placeholder="Username unik"
                       value="<?= htmlspecialchars($data['username']) ?>">
              </div>

              <div class="col-md-6">
                <label>Email</label>
                <input type="email" 
                       name="email" 
                       class="form-control" 
                       required 
                       placeholder="email@example.com"
                       value="<?= htmlspecialchars($data['email']) ?>">
              </div>

              <div class="col-md-6">
                <label>No HP</label>
                <input type="text" 
                       name="no_hp" 
                       maxlength="25" 
                       oninput="this.value=this.value.replace(/[^0-9]/g,'');"
                       class="form-control" 
                       placeholder="08xxxxxxxxxx"
                       value="<?= htmlspecialchars($data['no_hp']) ?>">
                <small class="text-muted">Hanya angka, tanpa spasi atau tanda hubung</small>
              </div>

              <div class="col-md-6">
                <label>Password Baru <span class="text-muted">(opsional)</span></label>
                <input type="password" 
                       name="password" 
                       class="form-control" 
                       placeholder="Kosongkan jika tidak ingin mengubah">
                <small class="text-muted">Minimal 6 karakter</small>
              </div>

              <div class="col-md-6">
                <label>Konfirmasi Password Baru</label>
                <input type="password" 
                       name="confirm_password" 
                       class="form-control" 
                       placeholder="Ketik ulang password baru">
              </div>

              <div class="col-md-6">
                <label>Foto Profil</label>
                <input type="file" 
                       name="foto_profil" 
                       class="form-control" 
                       accept=".jpg,.jpeg,.png,.gif">
                <small class="text-muted">
                  <i class="fas fa-info-circle me-1"></i>
                  Format: JPG, PNG, GIF | Maksimal 2MB
                </small>

                <?php if (!empty($data['foto_profil'])): ?>
                  <div class="photo-preview">
                    <img src="../uploads/users/<?= htmlspecialchars($data['foto_profil']) ?>" 
                         width="100" 
                         alt="Foto Profil">
                    <div class="mt-2 text-center">
                      <small class="text-muted">Foto saat ini</small>
                    </div>
                  </div>
                <?php endif; ?>
              </div>

              <div class="col-md-6">
                <label>Role</label>
                <input type="text" class="form-control" value="Administrator" readonly>
                <small class="text-muted">Role tidak dapat diubah</small>
              </div>

            </div>

            <div class="mt-5 text-end">
              <button type="submit" id="btnUpdate" class="btn btn-success px-4">
                <i class="fas fa-save me-1"></i> Perbarui Data
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
$(document).ready(function() {
  // Auto hide alert
  setTimeout(function() {
    $('.alert').fadeOut('slow');
  }, 5000);

  // Form validation
  $('#formEdit').on('submit', function(e) {
    const pwd = $('input[name="password"]').val();
    const conf = $('input[name="confirm_password"]').val();

    if (pwd !== '' && pwd !== conf) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Error Validasi',
        text: 'Password dan konfirmasi password tidak cocok!',
        confirmButtonColor: '#2196f3'
      });
      return false;
    }

    if (pwd !== '' && pwd.length < 6) {
      e.preventDefault();
      Swal.fire({
        icon: 'error',
        title: 'Error Validasi',
        text: 'Password minimal 6 karakter!',
        confirmButtonColor: '#2196f3'
      });
      return false;
    }

    // Show loading
    $('#btnUpdate').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Menyimpan...');
  });
});
</script>