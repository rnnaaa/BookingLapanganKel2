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
<html>
<head>
  <meta charset="utf-8">
  <title>Verifikasi OTP — BookingLapangan</title>
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
<div class="auth-box">
  <h2>Verifikasi Kode</h2>
  <?php if(!empty($_SESSION['err'])){ echo "<div class='err'>".esc($_SESSION['err'])."</div>"; unset($_SESSION['err']); } ?>
  <?php if(!empty($_SESSION['success'])){ echo "<div class='info'>".esc($_SESSION['success'])."</div>"; unset($_SESSION['success']); } ?>
  <form method="POST" action="php/verify_otp_process.php">
    <label>Email</label>
    <input name="email" type="email" required value="<?php echo esc($email); ?>">
    <label>Masukkan Kode (6 digit)</label>
    <input name="otp" pattern="\d{6}" required>
    <div class="actions">
      <button class="btn" type="submit">Verifikasi</button>
      <a class="link" href="resend.php?email=<?php echo urlencode($email); ?>">Kirim Ulang Kode</a>
    </div>
  </form>
</div>
</body>
</html>
