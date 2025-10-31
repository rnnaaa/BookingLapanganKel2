<?php
ob_start();
require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
include('../includes/sidebar.php');

if (!isset($_GET['id']) || empty($_GET['id'])) {
  header("Location: users.php");
  exit;
}

$id = $_GET['id'];
$result = mysqli_query($conn, "SELECT * FROM users WHERE id_user='$id'");
$user = mysqli_fetch_assoc($result);

if (!$user) {
  $_SESSION['toast_error'] = "Data pengguna tidak ditemukan!";
  header("Location: users.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nama = trim($_POST['nama']);
  $email = trim($_POST['email']);
  $no_hp = trim($_POST['no_hp']);
  $role = $_POST['role'];

  // Validasi server-side
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['toast_error'] = "Format email tidak valid.";
  } elseif (!preg_match('/^[0-9]{10,15}$/', $no_hp)) {
    $_SESSION['toast_error'] = "Nomor HP hanya boleh angka dan harus 10–15 digit.";
  } else {
    // Cek apakah email sudah digunakan oleh user lain
    $checkEmail = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND id_user != '$id'");
    if (mysqli_num_rows($checkEmail) > 0) {
      $_SESSION['toast_error'] = "Email sudah digunakan pengguna lain.";
    } else {
      $query = "UPDATE users SET 
                  nama='$nama', 
                  email='$email', 
                  no_hp='$no_hp', 
                  role='$role' 
                WHERE id_user='$id'";
      if (mysqli_query($conn, $query)) {
        $_SESSION['toast_success'] = "Data pengguna berhasil diperbarui!";
        header("Location: users.php");
        exit;
      } else {
        $_SESSION['toast_error'] = "Gagal memperbarui data pengguna!";
      }
    }
  }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Pengguna</h1>
      <a href="users.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">
      <div class="card shadow-sm">
        <div class="card-header bg-warning text-white">
          <h3 class="card-title"><i class="fas fa-user-edit mr-2"></i> Form Edit Pengguna</h3>
        </div>

        <form method="POST" id="formEditUser" novalidate>
          <div class="card-body">

            <!-- NAMA -->
            <div class="form-group">
              <label>Nama Lengkap</label>
              <input type="text" name="nama" id="nama" 
                     value="<?= htmlspecialchars($user['nama']) ?>" 
                     class="form-control" required minlength="3" maxlength="50"
                     placeholder="Masukkan nama lengkap">
              <small class="text-danger" id="err_nama"></small>
            </div>

            <!-- EMAIL -->
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="email" id="email" 
                     value="<?= htmlspecialchars($user['email']) ?>" 
                     class="form-control" required placeholder="contoh: nama@email.com">
              <small class="text-danger" id="err_email"></small>
            </div>

            <!-- NOMOR HP -->
            <div class="form-group">
              <label>No. HP</label>
              <input type="text" name="no_hp" id="no_hp" 
                     value="<?= htmlspecialchars($user['no_hp']) ?>" 
                     class="form-control" required minlength="10" maxlength="15"
                     oninput="this.value = this.value.replace(/[^0-9]/g, '');"
                     placeholder="Contoh: 081234567890">
              <small class="text-danger" id="err_no_hp"></small>
            </div>

            <!-- ROLE -->
            <div class="form-group">
              <label>Role</label>
              <select name="role" id="role" class="form-control" required>
                <option value="">-- Pilih Role --</option>
                <option value="user" <?= $user['role']=='user'?'selected':'' ?>>User</option>
                <option value="admin" <?= $user['role']=='admin'?'selected':'' ?>>Admin</option>
              </select>
              <small class="text-danger" id="err_role"></small>
            </div>

          </div>
          <div class="card-footer text-right">
            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Simpan Perubahan</button>
          </div>
        </form>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/footer.php'); ?>

<!-- ================= VALIDASI FRONTEND (REAL-TIME) ================= -->
<script>
document.getElementById('formEditUser').addEventListener('submit', function(e) {
  let valid = true;

  const nama = document.getElementById('nama');
  const email = document.getElementById('email');
  const no_hp = document.getElementById('no_hp');
  const role = document.getElementById('role');

  // Reset error
  document.querySelectorAll('small.text-danger').forEach(el => el.innerText = '');

  // Validasi nama
  if (nama.value.trim().length < 3) {
    document.getElementById('err_nama').innerText = "Nama minimal 3 karakter.";
    valid = false;
  }

  // Validasi email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email.value.trim())) {
    document.getElementById('err_email').innerText = "Format email tidak valid.";
    valid = false;
  }

  // Validasi nomor HP
  const hp = no_hp.value.trim();
  if (!/^[0-9]+$/.test(hp)) {
    document.getElementById('err_no_hp').innerText = "Nomor HP hanya boleh angka.";
    valid = false;
  } else if (hp.length < 10 || hp.length > 15) {
    document.getElementById('err_no_hp').innerText = "Nomor HP harus 10–15 digit.";
    valid = false;
  }

  // Validasi role
  if (role.value === "") {
    document.getElementById('err_role').innerText = "Pilih role pengguna.";
    valid = false;
  }

  if (!valid) e.preventDefault();
});
</script>
