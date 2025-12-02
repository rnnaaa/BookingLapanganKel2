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

    // Query database
    $sql = "SELECT * FROM users WHERE (email=? OR username=?) LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $identifier, $identifier);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($row = $res->fetch_assoc()) {
        // Cek Username Case Sensitive
        if (strpos($identifier, '@') === false && $row['username'] !== $identifier) {
            $err = "Username atau Password salah (Perhatikan huruf besar/kecil).";
        }
        // Cek Password
        elseif (!password_verify($password, $row['password'])) {
            $err = "Password salah.";
        } 
        // Cek Verifikasi Email
        elseif ($row['is_verified'] == 0) {
            $err = "Akun belum diverifikasi. Cek email untuk kode verifikasi.";
        } 
        // Cek Status
        elseif ($row['status'] !== 'aktif') {
            $err = "Akun tidak aktif. Hubungi admin.";
        } 
        // Login Sukses
        else {
            $_SESSION['id_user'] = $row['id_user'];
            $_SESSION['nama'] = $row['nama'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['foto_profil'] = $row['foto_profil'];
            $_SESSION['last_activity'] = time(); 
            $_SESSION['username'] = $row['username']; 
            $_SESSION['user_name'] = $row['nama'];      
            $_SESSION['user_email'] = $row['email'];
            
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id_user = ?");
            $update_stmt->bind_param('i', $row['id_user']);
            $update_stmt->execute();
            $update_stmt->close();

            if ($row['role'] === 'admin') {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../index.php');
            }
            exit;
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — BookingLapangan</title>
  
  <link rel="stylesheet" href="../assets/css/auth.css">
  <style>
    /* CSS Responsive & Layout */
    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f3f4f6;
        padding: 1rem;
        box-sizing: border-box;
        font-family: 'Inter', sans-serif;
    }
    .auth-card {
        background: white;
        padding: 2.5rem;
        border-radius: 16px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px;
    }
    h1 {
        text-align: center;
        margin-bottom: 2rem;
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
    }
    .form-group {
        margin-bottom: 1.25rem;
    }
    label {
        display: block;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
    }
    .form-control {
        width: 100%;
        padding: 0.75rem 1rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        box-sizing: border-box;
        font-size: 0.95rem;
        transition: border-color 0.2s;
    }
    .form-control:focus {
        outline: none;
        border-color: #2563eb;
        ring: 2px solid #2563eb;
    }
    
    /* Styling Khusus Password Wrapper */
    .password-wrapper { 
        position: relative; 
        display: flex;
        align-items: center;
    }
    .password-wrapper input {
        padding-right: 40px; /* Ruang untuk icon */
    }
    .password-toggle {
        position: absolute;
        right: 10px;
        background: none;
        border: none;
        cursor: pointer;
        color: #6b7280;
        padding: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: color 0.2s;
    }
    .password-toggle:hover {
        color: #374151;
    }
    
    .btn-primary {
        width: 100%;
        padding: 0.875rem;
        background-color: #2563eb;
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 0.5rem;
        font-size: 1rem;
        transition: background-color 0.2s;
    }
    .btn-primary:hover {
        background-color: #1d4ed8;
    }
    
    .form-actions {
        text-align: right;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
    }
    .form-link { 
        color: #2563eb; 
        text-decoration: none; 
        font-weight: 500;
    }
    .form-link:hover { text-decoration: underline; }
    
    .auth-footer { 
        text-align: center; 
        margin-top: 2rem; 
        font-size: 0.9rem; 
        color: #6b7280;
    }
    
    /* Alert Styles */
    .alert {
        padding: 0.75rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        font-size: 0.875rem;
        text-align: center;
    }
    .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .alert-success { background-color: #dcfce7; color: #166534; border: 1px solid #86efac; }

    @media (max-width: 640px) {
        .auth-card { padding: 1.5rem; }
    }
  </style>
</head>
<body>

  <main style="width: 100%;">
    <div class="auth-card">
      
      <h1>Masuk Akun</h1>

      <?php if($err) echo "<div class='alert alert-error'>".esc($err)."</div>"; ?>
      <?php if(!empty($_SESSION['success'])){ echo "<div class='alert alert-success'>".esc($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="identifier">Email atau Username</label>
          <input type="text" name="identifier" id="identifier" class="form-control" required placeholder="Masukkan email/username">
        </div>

        <div class="form-group">
          <label for="password">Password</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-control" required placeholder="Masukkan password">
            
            <button type="button" class="password-toggle" onclick="togglePasswordVisibility()">
               <svg id="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem;">
                   <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                   <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
               </svg>
               
               <svg id="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem; display:none;">
                   <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A.75.75 0 003 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 003.98 8.223zM3.98 15.75A.75.75 0 003 16.5v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM6.02 5.03A.75.75 0 004.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 006.02 5.03zM6.02 18.97A.75.75 0 004.5 19.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM9.02 2.03A.75.75 0 007.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 009.02 2.03zM9.02 21.97A.75.75 0 007.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM12.02 0A.75.75 0 0010.5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 0zM12.02 24A.75.75 0 0010.5 24.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 24zM15.02 2.03A.75.75 0 0013.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0015.02 2.03zM15.02 21.97A.75.75 0 0013.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM18.02 5.03A.75.75 0 0016.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0018.02 5.03zM18.02 18.97A.75.75 0 0016.5 19.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 8.223A.75.75 0 0019.5 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 15.75A.75.75 0 0019.5 16.5v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727z" />
               </svg>
            </button>
          </div>
        </div>

        <div class="form-actions">
          <a href="forgot.php" class="form-link">Lupa Password?</a>
        </div>

        <div>
          <button type="submit" class="btn-primary">Login</button>
        </div>
      </form>

      <p class="auth-footer">
        Belum punya akun? <a href="register.php" class="form-link">Daftar di sini</a>
      </p>

    </div>
  </main>

  <script>
      // FUNGSI TOGGLE PASSWORD
      function togglePasswordVisibility() {
          const passwordInput = document.getElementById('password');
          const iconEye = document.getElementById('icon-eye');
          const iconEyeSlash = document.getElementById('icon-eye-slash');

          if (passwordInput.type === 'password') {
              // Ubah jadi Text (Show)
              passwordInput.type = 'text';
              iconEye.style.display = 'none';
              iconEyeSlash.style.display = 'block';
          } else {
              // Ubah jadi Password (Hide)
              passwordInput.type = 'password';
              iconEye.style.display = 'block';
              iconEyeSlash.style.display = 'none';
          }
      }
  </script>
</body>
</html>