<?php
include '../config/database.php';
if (isset($_POST['register'])) {
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = 'user';

    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Email sudah terdaftar!";
    } else {
        $insert = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $nama, $email, $password, $role);
        if ($insert->execute()) {
            header("Location: login.php");
        } else {
            $error = "Pendaftaran gagal!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Register | Badmintoon</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <div class="card text-center">
    <h3 class="mb-3 text-primary">Badmintoon 🏸</h3>
    <h5 class="mb-3">Daftar Akun Baru</h5>
    <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <form method="post">
      <div class="mb-3 text-start">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" class="form-control" required>
      </div>
      <div class="mb-3 text-start">
        <label>Email</label>
        <input type="email" name="email" class="form-control" required>
      </div>
      <div class="mb-3 text-start">
        <label>Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" name="register" class="btn btn-primary w-100">Daftar</button>
    </form>
    <p class="mt-3 text-muted">Sudah punya akun? <a href="login.php">Login di sini</a></p>
  </div>
</body>
</html>
