<?php
session_start();
require '../config/database.php';

if (!function_exists('esc')) { function esc($str) { return htmlspecialchars($str, ENT_QUOTES, 'UTF-8'); } }
$email = isset($_GET['email']) ? $_GET['email'] : '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Verifikasi OTP — BookingLapangan</title>
  
  <link rel="stylesheet" href="../assets/css/auth.css">
  <style>
    body {
        margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center;
        background-color: #f3f4f6; padding: 1rem; box-sizing: border-box;
    }
    .auth-card {
        background: white; padding: 2rem; border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); width: 100%; max-width: 400px;
    }
    .form-control {
        width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.5rem;
        box-sizing: border-box; margin-top: 0.25rem; font-size: 1.1rem; letter-spacing: 2px; text-align: center;
    }
    .btn-primary {
        width: 100%; padding: 0.75rem; background-color: #2563eb; color: white;
        border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; margin-top: 1rem;
    }
    h1 { text-align: center; margin-bottom: 0.5rem; }
    .subtitle { text-align: center; color: #6b7280; font-size: 0.9rem; margin-bottom: 1.5rem; }
    .auth-footer { text-align: center; margin-top: 1.5rem; font-size: 0.9rem; }
    .form-link { color: #2563eb; text-decoration: none; }
  </style>
</head>
<body>

  <main style="width: 100%;">
    <div class="auth-card">

      <h1>Verifikasi Kode</h1>
      <p class="subtitle">Masukkan 6 digit kode OTP yang dikirim ke email Anda.</p>

      <?php if(!empty($_SESSION['err'])){ echo "<div style='color:red; text-align:center; margin-bottom:10px;'>".esc($_SESSION['err'])."</div>"; unset($_SESSION['err']); } ?>
      <?php if(!empty($_SESSION['success'])){ echo "<div style='color:green; text-align:center; margin-bottom:10px;'>".esc($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>

      <form method="POST" action="php/verify_otp_process.php">
        
        <div style="margin-bottom: 1rem;">
          <label for="email" style="display:block; margin-bottom:5px;">Email</label>
          <input type="email" name="email" id="email" class="form-control" style="font-size: 1rem; letter-spacing: normal;" required value="<?php echo esc($email); ?>" readonly>
        </div>
        
        <div style="margin-bottom: 1rem;">
          <label for="otp" style="display:block; margin-bottom:5px;">Kode OTP</label>
          <input type="text" name="otp" id="otp" class="form-control" 
                 pattern="[0-9]*" inputmode="numeric" maxlength="6" 
                 placeholder="000000" autocomplete="one-time-code" required>
        </div>

        <div>
          <button type="submit" class="btn-primary">Verifikasi</button>
        </div>
      </form>

      <p class="auth-footer">
        Tidak menerima kode? 
        <a href="resend.php?email=<?php echo urlencode($email); ?>" class="form-link">Kirim Ulang Kode</a>
      </p>

    </div>
  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });
        }
    });
  </script>

</body>
</html>