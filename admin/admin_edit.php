<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
include('../includes/header.php');
include('../includes/topbar.php');
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
        if ($password !== $confirmPwd) {
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

            // generate nama unik
            $newFile = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $target = $uploadDir . $newFile;

            if (!move_uploaded_file($_FILES['foto_profil']['tmp_name'], $target)) {
                throw new Exception("Upload foto gagal!");
            }

            // hapus foto lama jika ada
            if (!empty($oldFoto) && file_exists($uploadDir . $oldFoto)) {
                unlink($uploadDir . $oldFoto);
            }

            $finalFoto = $newFile;
        }

        // === UPDATE STATEMENT ===
        if (!empty($password)) {
            // Jika password diganti
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
            // Tanpa password
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

<div class="content-wrapper animate__animated animate__fadeIn">
  <section class="content-header">
    <div class="container-fluid d-flex justify-content-between align-items-center">
      <h1><i class="fas fa-edit mr-2"></i> Edit Data Admin</h1>
      <a href="admin.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <div class="card shadow-lg border-0">
        <div class="card-header text-white" style="background: linear-gradient(90deg,#0e5c91,#2196f3);">
          <h3 class="card-title mb-0">Form Edit Administrator</h3>
        </div>

        <div class="card-body position-relative">

          <!-- Loading Overlay -->
          <div id="loadingOverlay" class="position-absolute top-0 start-0 w-100 h-100 d-none justify-content-center align-items-center" style="background: rgba(255,255,255,0.8); z-index:100;">
            <div class="text-center">
              <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;"></div>
              <p class="fw-bold text-dark">Memperbarui data...</p>
            </div>
          </div>

          <?php if (!empty($errorMsg)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
              <i class="fas fa-exclamation-circle me-2"></i> <?= htmlspecialchars($errorMsg) ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
          <?php endif; ?>

          <form method="POST" enctype="multipart/form-data" id="formEdit">

            <div class="row">
              <div class="col-md-6 mb-3">
                <label>Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($data['nama']) ?>">
              </div>

              <div class="col-md-6 mb-3">
                <label>Username</label>
                <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($data['username']) ?>">
              </div>

              <div class="col-md-6 mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($data['email']) ?>">
              </div>

              <div class="col-md-6 mb-3">
                <label>No HP</label>
                <input type="text" name="no_hp" maxlength="25" oninput="this.value=this.value.replace(/[^0-9]/g,'');"
                       class="form-control" value="<?= htmlspecialchars($data['no_hp']) ?>">
              </div>

              <div class="col-md-6 mb-3">
                <label>Password Baru (opsional)</label>
                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak ganti">
              </div>

              <div class="col-md-6 mb-3">
                <label>Konfirmasi Password Baru</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Kosongkan jika tidak ganti">
              </div>

              <div class="col-md-6 mb-3">
                <label>Foto Profil</label>
                <input type="file" name="foto_profil" class="form-control" accept=".jpg,.jpeg,.png,.gif">
                <small class="text-muted">Maks 2 MB</small>

                <?php if (!empty($data['foto_profil'])): ?>
                  <div class="mt-2">
                    <img src="../uploads/users/<?= htmlspecialchars($data['foto_profil']) ?>" width="80" class="rounded border">
                  </div>
                <?php endif; ?>
              </div>

              <div class="col-md-6 mb-3">
                <label>Role</label>
                <input type="text" class="form-control" value="Admin" readonly>
              </div>

            </div>

            <div class="mt-3 text-end">
              <button type="submit" id="btnUpdate" class="btn btn-success px-4">
                <i class="fas fa-save"></i> Perbarui
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
$("#formEdit").on("submit", function(){
  $("#btnUpdate").prop("disabled", true);
  $("#loadingOverlay").removeClass("d-none").addClass("d-flex");
});
</script>
