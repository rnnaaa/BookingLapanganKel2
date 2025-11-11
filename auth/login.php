<?php
session_start();
require '../config/database.php'; // Asumsi path ini benar

// Helper function (karena esc() digunakan di HTML)
if (!function_exists('esc')) {
    function esc($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

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
        // 1. Verifikasi Password
        if (!password_verify($password, $row['password'])) {
            $err = "Password salah.";
        } 
        // 2. Cek Verifikasi Akun
        elseif ($row['is_verified'] == 0) {
            $err = "Akun belum diverifikasi. Cek email untuk kode verifikasi.";
        } 
        // 3. Cek Status Akun
        elseif ($row['status'] !== 'aktif') {
            $err = "Akun tidak aktif. Hubungi admin.";
        } 
        // 4. Sukses Login
        else {
            // === PERBAIKAN PENTING ===
            // Menyesuaikan session dengan yang digunakan di booking.php dan file lain
            $_SESSION['id_user'] = $row['id_user']; // Menggunakan 'id_user' dari database
            $_SESSION['nama'] = $row['nama'];     // Menggunakan 'nama' dari database
            $_SESSION['role'] = $row['role'];
            $_SESSION['foto_profil'] = $row['foto_profil'];

            // === TAMBAHAN: Update last_login ===
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?");
            $update_stmt->bind_param('i', $row['id_user']);
            $update_stmt->execute();
            $update_stmt->close();

            if ($row['role'] === 'admin') {
                // Arahkan ke dashboard admin
                header('Location: ../Admin/dashboard.php'); // Asumsi path folder Admin
                exit;
            } else {
                // Arahkan ke halaman utama pengguna (index.php)
                header('Location: ../index.php');
                exit;
            }
        }
    } else {
        $err = "Akun (Email atau Username) tidak ditemukan.";
    }
    $stmt->close();
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