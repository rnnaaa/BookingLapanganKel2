<?php
// register.php (form page)
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Daftar — BookingLapangan</title>
  <link rel="stylesheet" href="css/auth.css">
</head>
<body>
<div class="auth-box">
  <h2>Daftar Akun</h2>
  <form id="regForm" method="POST" action="php/register_process.php">
    <label>Nama Lengkap</label>
    <input name="nama" required>

    <label>Username</label>
    <input name="username" required>

    <label>Email</label>
    <input name="email" type="email" required>

    <label>No. HP</label>
    <input name="phone" required>

    <label>Pekerjaan</label>
    <select id="pekerjaan" name="pekerjaan">
      <option value="Pelajar">Pelajar</option>
      <option value="Mahasiswa">Mahasiswa</option>
      <option value="Wirausaha">Wirausaha</option>
      <option value="Lainnya">Lainnya</option>
    </select>

    <div id="pekerjaan_lain_wrapper" style="display:none;">
      <label>Jika Lainnya, sebutkan</label>
      <input name="pekerjaan_lain" id="pekerjaan_lain">
    </div>

    <label>Password</label>
    <input name="password" type="password" required>

    <label>Ulangi Password</label>
    <input name="password2" type="password" required>

    <div class="actions">
      <button type="submit" class="btn">Daftar</button>
      <a href="login.php" class="link">Sudah punya akun? Login</a>
    </div>
  </form>
</div>
<script src="js/auth.js"></script>
</body>
</html>
