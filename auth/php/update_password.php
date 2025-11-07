<?php
// php/update_password.php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../login.php'); exit; }
$uid = $_SESSION['reset_user_id'] ?? null;
if (!$uid) { die("Session reset tidak ditemukan."); }

$pw = $_POST['password'] ?? '';
$pw2 = $_POST['password2'] ?? '';
if ($pw !== $pw2) { die("Password tidak cocok."); }
if (strlen($pw) < 6) { die("Password minimal 6 karakter."); }

$hash = password_hash($pw, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET password=?, reset_token=NULL, reset_expires=NULL WHERE id=?");
$stmt->bind_param('si', $hash, $uid);
if ($stmt->execute()) {
    unset($_SESSION['reset_user_id']);
    echo "Password berhasil diubah. <a href='../login.php'>Login</a>";
} else {
    echo "Gagal menyimpan password.";
}
