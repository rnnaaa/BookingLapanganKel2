<?php
session_start();
require '../config/database.php'; 

// Helper function
if (!function_exists('esc')) {
    function esc($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']); // email atau username
    $password = $_POST['password'];

    // Query database (MySQL defaultnya case-insensitive saat mencari username)
    $sql = "SELECT * FROM users WHERE (email=? OR username=?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        
        // === 1. CEK CASE SENSITIVE (KHUSUS USERNAME) ===
        // Jika input TIDAK mengandung '@', berarti itu username.
        // Kita cek apakah input user sama persis (identik) dengan data di DB.
        if (strpos($identifier, '@') === false && $row['username'] !== $identifier) {
            $err = "Username atau Password salah (Perhatikan huruf besar/kecil).";
        }
        // === 2. CEK PASSWORD ===
        elseif (!password_verify($password, $row['password'])) {
            $err = "Password salah.";
        } 
        // === 3. CEK VERIFIKASI EMAIL ===
        elseif ($row['is_verified'] == 0) {
            $err = "Akun belum diverifikasi. Cek email untuk kode verifikasi.";
        } 
        // === 4. CEK STATUS AKUN ===
        elseif ($row['status'] !== 'aktif') {
            $err = "Akun tidak aktif. Hubungi admin.";
        } 
        // === 5. LOGIN SUKSES ===
        else {
            // Set Session
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['nama'] = $row['nama'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['foto_profil'] = $row['foto_profil'];
            $_SESSION['last_activity'] = time(); 
            $_SESSION['username'] = $row['username']; 
            
            // Update waktu login terakhir
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?");
            $update_stmt->bind_param('i', $row['id_user']);
            $update_stmt->execute();
            $update_stmt->close();

            // Redirect sesuai role
            if ($row['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
                exit;
            } else {
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
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login — BookingLapangan</title>
  
  <link rel="stylesheet" href="../assets/css/auth.css">

</head>
<body>

  <main>
    <div class="auth-card max-w-md">
      
      <h1>Masuk ke Akun Anda</h1>

      <?php if($err) echo "<div class='alert alert-error'>".esc($err)."</div>"; ?>
      <?php if(!empty($_SESSION['success'])){ echo "<div class='alert alert-success'>".esc($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="identifier">Email atau Username</label>
          <input type="text" name="identifier" id="identifier" class="form-control" required>
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-control" required>
            
            <button type="button" class="password-toggle" data-toggle-password>
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <svg class="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem; display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A.75.75 0 003 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 003.98 8.223zM3.98 15.75A.75.75 0 003 16.5v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM6.02 5.03A.75.75 0 004.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 006.02 5.03zM6.02 18.97A.75.75 0 004.5 19.75v.75a.75.75 0 001.5 0v-.75a.75.75 0 00-.52-.727zM9.02 2.03A.75.75 0 007.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 009.02 2.03zM9.02 21.97A.75.75 0 007.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM12.02 0A.75.75 0 0010.5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 0zM12.02 24A.75.75 0 0010.5 24.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 24zM15.02 2.03A.75.75 0 0013.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0015.02 2.03zM15.02 21.97A.75.75 0 0013.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM18.02 5.03A.75.75 0 0016.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0018.02 5.03zM18.02 18.97A.75.75 0 0016.5 19.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 8.223A.75.75 0 0019.5 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 15.75A.75.75 0 0019.5 16.5v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727z" />
              </svg>
            </button>
          </div>
        </div>

        <div class="form-actions">
          <a href="forgot.php" class="form-link">Lupa Password?</a>
        </div>

        <div>
          <button type="submit" class="btn-primary">
            Login
          </button>
        </div>
      </form>

      <p class="auth-footer">
        Belum punya akun? <a href="register.php" class="form-link" style="font-weight: 500;">Daftar di sini</a>
      </p>

    </div>
  </main>

  <script src="../assets/js/auth.js"></script>

</body>
</html>