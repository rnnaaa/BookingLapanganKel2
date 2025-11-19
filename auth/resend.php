<?php
session_start();

// Helper function
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
  <title>Kirim Ulang Kode — BookingLapangan</title>
  
  <link rel="stylesheet" href="../assets/css/auth.css">
  
</head>
<body>

  <main>
    <div class="auth-card max-w-md">

      <h1>Kirim Ulang Kode</h1>
      <p class="subtitle" style="margin-top: -1rem; margin-bottom: 1.5rem;">
          Masukkan email terdaftar untuk menerima kode verifikasi baru.
      </p>

      <?php if(!empty($_SESSION['error'])){ echo "<div class='alert alert-error'>".esc($_SESSION['error'])."</div>"; unset($_SESSION['error']); } ?>
      <?php if(!empty($_SESSION['success'])){ echo "<div class='alert alert-success'>".esc($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>

      <form method="POST" action="php/resend_otp.php">
        
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" name="email" id="email" class="form-control" required value="<?php echo esc($email); ?>">
        </div>
        
        <div>
          <button type="submit" class="btn-primary">
            Kirim Ulang Kode
          </button>
        </div>
      </form>

      <p class="auth-footer">
        Sudah punya kode? 
        <a href="verify.php?email=<?php echo urlencode($email); ?>" class="form-link" style="font-weight: 500;">Kembali ke Verifikasi</a>
      </p>

    </div>
  </main>

  </body>
</html>