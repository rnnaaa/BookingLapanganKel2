<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

// Tambah user baru
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nama = trim($_POST['nama']);
  $email = trim($_POST['email']);
  $no_hp = trim($_POST['no_hp']);
  $password = trim($_POST['password']);
  $role = $_POST['role'];

  $errors = [];

  if (strlen($nama) < 3) $errors[] = "Nama minimal 3 karakter.";
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid.";
  if (!preg_match('/^08[0-9]{8,11}$/', $no_hp)) $errors[] = "Nomor HP harus dimulai 08 dan 10–13 digit.";
  if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter.";

  // Cek email unik
  $cek = mysqli_query($conn, "SELECT * FROM users WHERE email='$email'");
  if (mysqli_num_rows($cek) > 0) $errors[] = "Email sudah digunakan.";

  if (empty($errors)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $q = "INSERT INTO users (nama, email, no_hp, password, role, created_at) 
          VALUES ('$nama', '$email', '$no_hp', '$hashed', '$role', NOW())";
    mysqli_query($conn, $q);

    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
      Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Data pengguna berhasil ditambahkan.',
        timer: 2000,
        showConfirmButton: false
      }).then(() => window.location='users.php');
    </script>";
    exit;
  } else {
    $msg = implode('<br>', $errors);
    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        html: '$msg'
      });
    </script>";
  }
}
?>

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-user-plus mr-2"></i> Tambah Pengguna Baru</h1>
      <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-lg border-0">
        <div class="card-header bg-info text-white">
          <h3 class="card-title"><i class="fas fa-user mr-2"></i> Form Tambah Pengguna</h3>
        </div>

        <form method="POST" id="formUser" novalidate>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6 mb-3">
                <label>Nama Lengkap <span class="text-danger">*</span></label>
                <input type="text" name="nama" class="form-control" required minlength="3" placeholder="Masukkan nama lengkap">
                <div class="invalid-feedback">Nama minimal 3 karakter.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="contoh@email.com">
                <div class="invalid-feedback">Email tidak valid.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label>No HP <span class="text-danger">*</span></label>
                <input type="text" name="no_hp" class="form-control" required pattern="^08[0-9]{8,11}$" placeholder="081234567890">
                <div class="invalid-feedback">Nomor HP tidak valid.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label>Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
                <div class="invalid-feedback">Password minimal 6 karakter.</div>
              </div>
              <div class="col-md-6 mb-3">
                <label>Role Pengguna <span class="text-danger">*</span></label>
                <select name="role" class="form-control" required>
                  <option value="">-- Pilih Role --</option>
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
                <div class="invalid-feedback">Pilih role pengguna.</div>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(() => {
  const form = document.querySelector('#formUser');
  form.addEventListener('submit', (e) => {
    if (!form.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
      Swal.fire({
        icon: 'warning',
        title: 'Data belum lengkap!',
        text: 'Harap isi semua kolom dengan benar sebelum disimpan.'
      });
    }
    form.classList.add('was-validated');
  });
})();
</script>
