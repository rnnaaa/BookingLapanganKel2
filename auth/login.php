<?php
// login.php
session_start();
require '../config/database.php';


$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']); // email atau username
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE (email=? OR username=?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        if (!password_verify($password, $row['password'])) {
            $err = "Password salah.";
        } elseif ($row['is_verified'] == 0) {
            $err = "Akun belum diverifikasi. Cek email untuk kode verifikasi.";
        } elseif ($row['status'] !== 'aktif') {
            $err = "Akun tidak aktif. Hubungi admin.";
        } else {
            // sukses login
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user_name'] = $row['nama_lengkap'];
            $_SESSION['role'] = $row['role'];

            if ($row['role'] === 'admin') {
                header('Location: dash_admin.php');
                exit;
            } else {
                header('Location: dash_user.php');
                exit;
            }
        }
    } else {
        $err = "Akun tidak ditemukan.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login — BookingLapangan</title>
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
  <div class="auth-box">
    <h2>Masuk</h2>
    <?php if($err) echo "<div class='err'>".esc($err)."</div>"; ?>
    <?php if(!empty($_SESSION['success'])){ echo "<div class='info'>".esc($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>
    <form method="POST" action="">
      <label>Email atau Username</label>
      <input type="text" name="identifier" required>

      <label>Password</label>
      <input type="password" name="password" required>

      <div class="actions">
        <button type="submit" class="btn">Login</button>
        <a href="forgot.php" class="link">Lupa Password?</a>
      </div>
    </form>
    <p>Belum punya akun? <a href="register.php">Daftar</a></p>
  </div>
</body>
</html>
