<?php
// verify.php
session_start();
require '../config/database.php';

// Helper function untuk keamanan (HTML escaping)
if (!function_exists('esc')) {
    function esc($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
$email = isset($_GET['email']) ? $_GET['email'] : '';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Verifikasi OTP — BookingLapangan</title>
  
  <link rel="stylesheet" href="../assets/css/auth.css">
  
</head>
<body>

  <main>
    <div class="auth-card max-w-md">

      <h1>Verifikasi Kode</h1>
      <p class="subtitle">Masukkan 6 digit kode OTP yang dikirim ke email Anda.</p>

      <?php if(!empty($_SESSION['err'])){ echo "<div class='alert alert-error'>".esc($_SESSION['err'])."</div>"; unset($_SESSION['err']); } ?>
      <?php if(!empty($_SESSION['success'])){ echo "<div class='alert alert-success'>".esc($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>

      <form method="POST" action="php/verify_otp_process.php">
        
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" name="email" id="email" class="form-control" required value="<?php echo esc($email); ?>">
        </div>
        
        <div class="form-group">
          <label for="otp">Masukkan Kode (6 digit)</label>
          <input type="text" name="otp" id="otp" class="form-control" pattern="\d{6}" inputmode="numeric" maxlength="6" required>
        </div>

        <div>
          <button type="submit" class="btn-primary">
            Verifikasi
          </button>
        </div>
      </form>

      <p class="auth-footer">
        Tidak menerima kode? 
        <a href="resend.php?email=<?php echo urlencode($email); ?>" class="form-link" style="font-weight: 500;">Kirim Ulang Kode</a>
      </p>

    </div>
  </main>

  </body>
</html>