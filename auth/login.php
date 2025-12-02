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
            $_SESSION['user_name'] = $row['nama'];      
            $_SESSION['user_email'] = $row['email'];
            
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
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — BookingLapangan</title>
  
  <link rel="stylesheet" href="../assets/css/auth.css">
  <style>
    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: #f3f4f6;
        padding: 1rem;
        box-sizing: border-box;
    }
    .auth-card {
        background: white;
        padding: 2rem;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 400px; /* Lebih kecil untuk login */
    }
    .form-control {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        box-sizing: border-box;
        margin-top: 0.25rem;
    }
    .btn-primary {
        width: 100%;
        padding: 0.75rem;
        background-color: #2563eb;
        color: white;
        border: none;
        border-radius: 0.5rem;
        font-weight: 600;
        cursor: pointer;
        margin-top: 1rem;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .password-wrapper { position: relative; }
    .password-toggle {
        position: absolute;
        right: 10px; top: 50%; transform: translateY(-50%);
        background: none; border: none; cursor: pointer; color: #6b7280;
    }
    .form-actions {
        text-align: right;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }
    .form-link { color: #2563eb; text-decoration: none; }
    .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; }
    
    @media (max-width: 640px) {
        .auth-card { padding: 1.5rem; }
    }
  </style>
</head>
<body>

  <main style="width: 100%;">
    <div class="auth-card">
      
      <h1 style="text-align: center; margin-bottom: 1.5rem;">Masuk Akun</h1>

      <?php if($err) echo "<div class='alert alert-error' style='color:red; text-align:center; margin-bottom:10px;'>".esc($err)."</div>"; ?>
      <?php if(!empty($_SESSION['success'])){ echo "<div class='alert alert-success' style='color:green; text-align:center; margin-bottom:10px;'>".esc($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>

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
               <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem;"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
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

  <script src="../assets/js/auth.js"></script>
</body>
</html>