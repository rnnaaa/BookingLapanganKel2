<?php
// reset_password.php
session_start();
require '../config/database.php';

$token = $_GET['token'] ?? '';
if (!$token) die("Token tidak ditemukan.");

$stmt = $conn->prepare("SELECT id_user, reset_expires FROM users WHERE reset_token=? LIMIT 1");
$stmt->bind_param('s', $token);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if (strtotime($row['reset_expires']) < time()) {
        die("Link reset sudah kadaluarsa.");
    }
    $_SESSION['reset_user_id'] = $row['id_user'];
    $_SESSION['reset_token'] = $token;
} else { die("Token tidak valid."); }
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Reset Password</title><link rel="stylesheet" href="css/auth.css"></head>
<body>
<div class="auth-box">
  <h2>Ubah Password</h2>
  <form method="POST" action="php/update_password.php">
    <label>Password Baru</label>
    <input type="password" name="password" required>
    <label>Ulangi Password</label>
    <input type="password" name="password2" required>
    <div class="actions">
      <button class="btn" type="submit">Simpan</button>
      <a class="link" href="login.php">Batal</a>
    </div>
  </form>
</div>
</body></html>
