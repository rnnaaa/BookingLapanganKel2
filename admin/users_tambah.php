<?php
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');
date_default_timezone_set('Asia/Jakarta');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $nama = trim($_POST['nama']);
  $email = trim($_POST['email']);
  $no_hp = trim($_POST['no_hp']);
  $password = trim($_POST['password']);
  $role = $_POST['role'];

  $errors = [];

  if (!preg_match("/^[a-zA-Z\s']{3,}$/", $nama))
    $errors[] = "Nama hanya boleh huruf dan spasi, minimal 3 karakter.";
  if (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = "Format email tidak valid.";
  if (!preg_match('/^08[0-9]{8,11}$/', $no_hp))
    $errors[] = "Nomor HP harus diawali 08 dan terdiri dari 10–13 digit.";
  if (strlen($password) < 6)
    $errors[] = "Password minimal 6 karakter.";
  if (!in_array($role, ['admin','user']))
    $errors[] = "Role tidak valid.";

  $cek = mysqli_query($conn, "SELECT id_user FROM users WHERE email='$email'");
  if (mysqli_num_rows($cek) > 0)
    $errors[] = "Email sudah digunakan.";

  if (empty($errors)) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (nama, email, no_hp, password, role, created_at) 
              VALUES ('$nama', '$email', '$no_hp', '$hashed', '$role', NOW())";
    if (mysqli_query($conn, $query)) {
      $_SESSION['toast_success'] = "Pengguna berhasil ditambahkan.";
      echo "<script>window.location='users.php';</script>";
      exit;
    } else {
      $_SESSION['toast_error'] = "Gagal menyimpan data pengguna.";
    }
  } else {
    $msg = implode('<br>', $errors);
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>Swal.fire({icon:'error', title:'Gagal!', html:`$msg`});</script>";
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
                <input type="text" name="nama" class="form-control" required pattern="[A-Za-z\s']{3,}" placeholder="Masukkan nama lengkap">
              </div>
              <div class="col-md-6 mb-3">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" class="form-control" required placeholder="contoh@email.com">
              </div>
              <div class="col-md-6 mb-3">
                <label>No HP <span class="text-danger">*</span></label>
                <input type="text" name="no_hp" class="form-control" required pattern="^08[0-9]{8,11}$" placeholder="081234567890">
              </div>
              <div class="col-md-6 mb-3">
                <label>Password <span class="text-danger">*</span></label>
                <input type="password" name="password" class="form-control" required minlength="6" placeholder="Minimal 6 karakter">
              </div>
              <div class="col-md-6 mb-3">
                <label>Role Pengguna <span class="text-danger">*</span></label>
                <select name="role" class="form-control" required>
                  <option value="">-- Pilih Role --</option>
                  <option value="user">User</option>
                  <option value="admin">Admin</option>
                </select>
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

<?php include_once('../includes/footer.php'); ?>
  