<?php
// resend.php
session_start();
$email = isset($_GET['email']) ? $_GET['email'] : '';
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Kirim Ulang Kode</title><link rel="stylesheet" href="css/auth.css"></head>
<body>
<div class="auth-box">
  <h2>Kirim Ulang Kode Verifikasi</h2>
  <p>Masukkan email yang terdaftar untuk menerima kode verifikasi baru (berlaku 10 menit).</p>
  <form method="POST" action="php/resend_otp.php">
    <label>Email</label>
    <input name="email" type="email" required value="<?php echo htmlspecialchars($email); ?>">
    <div class="actions">
      <button class="btn" type="submit">Kirim Ulang</button>
      <a class="link" href="verify.php?email=<?php echo urlencode($email); ?>">Kembali</a>
    </div>
  </form>
</div>
</body></html>
