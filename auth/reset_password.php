<?php
// reset_password.php
session_start();
require '../config/database.php';

// Validasi Token
$token = $_GET['token'] ?? '';
if (!$token) {
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>Token tidak ditemukan.</div>");
}

$stmt = $conn->prepare("SELECT id_user, reset_expires FROM users WHERE reset_token=? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    if (strtotime($row['reset_expires']) < time()) {
        die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>Link reset password sudah kadaluarsa.<br><a href='forgot.php'>Minta link baru</a></div>");
    }
    $_SESSION['reset_user_id'] = $row['id_user'];
    $_SESSION['reset_token'] = $token;
} else { 
    die("<div style='font-family:sans-serif; text-align:center; padding:50px;'>Token tidak valid.</div>"); 
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Reset Password — Rush Badminton</title>
  <link rel="stylesheet" href="../assets/css/auth.css">
  
  <style>
      /* Style tambahan khusus untuk validasi error seperti gambar */
      .input-error {
          border-color: #ef4444 !important; /* Warna Merah */
          background-color: #fef2f2;
      }
      .input-success {
          border-color: #22c55e !important; /* Warna Hijau */
      }
      .error-text {
          color: #ef4444;
          font-size: 12px;
          margin-top: 4px;
          display: none; /* Tersembunyi default */
      }
      /* Disable button style */
      button:disabled {
          opacity: 0.6;
          cursor: not-allowed;
      }
  </style>
</head>
<body>

  <main>
    <div class="auth-card max-w-md">
      
      <h1>Atur Ulang Password</h1>
      <p style="text-align:center; color:#666; margin-bottom:20px; font-size:14px;">
        Silakan buat password baru untuk akun Anda.
      </p>

      <?php 
      if(isset($_SESSION['error'])) { 
          echo "<div class='alert alert-error'>".htmlspecialchars($_SESSION['error'])."</div>"; 
          unset($_SESSION['error']); 
      } 
      ?>

      <form id="resetForm" method="POST" action="php/update_password.php">
        
        <div class="form-group">
          <label for="password">Password Baru</label>
          <div class="password-wrapper">
            <input type="password" name="password" id="password" class="form-control" required placeholder="Masukkan password baru">
            
            <button type="button" class="password-toggle" data-toggle-password>
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <svg class="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem; display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A.75.75 0 003 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 003.98 8.223zM3.98 15.75A.75.75 0 003 16.5v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM6.02 5.03A.75.75 0 004.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 006.02 5.03zM6.02 18.97A.75.75 0 004.5 19.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM9.02 2.03A.75.75 0 007.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 009.02 2.03zM9.02 21.97A.75.75 0 007.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM12.02 0A.75.75 0 0010.5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 0zM12.02 24A.75.75 0 0010.5 24.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0012.02 24zM15.02 2.03A.75.75 0 0013.5 2.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0015.02 2.03zM15.02 21.97A.75.75 0 0013.5 22.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM18.02 5.03A.75.75 0 0016.5 5.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 0018.02 5.03zM18.02 18.97A.75.75 0 0016.5 19.75v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 8.223A.75.75 0 0019.5 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727zM21.02 15.75A.75.75 0 0019.5 16.5v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727z" />
              </svg>
            </button>
          </div>
          <p id="ruleError" class="error-text">Kombinasi harus: Min 6 char. Huruf besar. Angka.</p>
        </div>

        <div class="form-group">
          <label for="password2">Ulangi Password</label>
          <div class="password-wrapper">
            <input type="password" name="password2" id="password2" class="form-control" required placeholder="Konfirmasi password baru">
            
            <button type="button" class="password-toggle" data-toggle-password>
              <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <svg class="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1.25rem; height:1.25rem; display: none;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A.75.75 0 003 9v.75a.75.75 0 001.5 0v-.75A.75.75 0 003.98 8.223zM3.98 15.75A.75.75 0 003 16.5v.75a.75.75 0 001.5 0v-.75A.75.75 0 00-.52-.727z" />
              </svg>
            </button>
          </div>
          <p id="matchError" class="error-text">Password tidak cocok.</p>
        </div>

        <div style="margin-top: 2rem;">
          <button type="submit" id="btnSubmit" class="btn-primary" disabled>
            Simpan Password Baru
          </button>
        </div>
      </form>

      <p class="auth-footer">
        Batal mengubah? <a href="login.php" class="form-link" style="font-weight: 500;">Kembali ke Login</a>
      </p>

    </div>
  </main>

  <script src="../assets/js/auth.js"></script>
  <script>
      document.addEventListener('DOMContentLoaded', function() {
          const pass1 = document.getElementById('password');
          const pass2 = document.getElementById('password2');
          const ruleError = document.getElementById('ruleError');
          const matchError = document.getElementById('matchError');
          const btnSubmit = document.getElementById('btnSubmit');

          function validateForm() {
              const val1 = pass1.value;
              const val2 = pass2.value;

              // 1. Cek Rules (Min 6, Huruf Besar, Angka)
              // Regex: (?=.*\d) = harus ada angka, (?=.*[A-Z]) = harus ada huruf besar, .{6,} = min 6 char
              const rulesRegex = /^(?=.*\d)(?=.*[A-Z]).{6,}$/;
              const isRulesValid = rulesRegex.test(val1);

              if (val1.length > 0) {
                  if (!isRulesValid) {
                      pass1.classList.add('input-error');
                      pass1.classList.remove('input-success');
                      ruleError.style.display = 'block';
                  } else {
                      pass1.classList.remove('input-error');
                      pass1.classList.add('input-success');
                      ruleError.style.display = 'none';
                  }
              } else {
                  pass1.classList.remove('input-error', 'input-success');
                  ruleError.style.display = 'none';
              }

              // 2. Cek Cocok (Match)
              const isMatch = val1 === val2 && val1 !== '';
              
              if (val2.length > 0) {
                  if (!isMatch) {
                      pass2.classList.add('input-error');
                      pass2.classList.remove('input-success');
                      matchError.style.display = 'block';
                  } else {
                      pass2.classList.remove('input-error');
                      pass2.classList.add('input-success');
                      matchError.style.display = 'none';
                  }
              } else {
                  pass2.classList.remove('input-error', 'input-success');
                  matchError.style.display = 'none';
              }

              // 3. Enable/Disable Button
              if (isRulesValid && isMatch) {
                  btnSubmit.disabled = false;
              } else {
                  btnSubmit.disabled = true;
              }
          }

          // Listen event input
          pass1.addEventListener('input', validateForm);
          pass2.addEventListener('input', validateForm);
      });
  </script>

</body>
</html>