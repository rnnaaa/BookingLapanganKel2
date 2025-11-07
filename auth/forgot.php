<?php
// forgot.php
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Lupa Password</title><link rel="stylesheet" href="css/auth.css"></head>
<body>
<div class="auth-box">
  <h2>Lupa Password</h2>
  <form method="POST" action="php/send_reset_link.php">
    <label>Email terdaftar</label>
    <input name="email" type="email" required>
    <div class="actions">
      <button class="btn" type="submit">Kirim Link Reset</button>
      <a class="link" href="login.php">Kembali</a>
    </div>
  </form>
</div>
</body></html>
