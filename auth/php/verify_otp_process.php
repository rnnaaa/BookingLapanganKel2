<?php
// php/verify_otp_process.php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../verify.php');
    exit;
}

$email = trim($_POST['email']);
$otp = trim($_POST['otp']);

$stmt = $conn->prepare("SELECT id, otp_code, otp_expires, is_verified FROM users WHERE email=? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    if ($row['is_verified'] == 1) {
        $_SESSION['success'] = "Akun sudah terverifikasi. Silakan login.";
        header('Location: ../login.php');
        exit;
    }
    if ($row['otp_code'] === $otp && strtotime($row['otp_expires']) > time()) {
        // verifikasi sukses
        $u = $row['id'];
        $stmt2 = $conn->prepare("UPDATE users SET is_verified=1, otp_code=NULL, otp_expires=NULL WHERE id=?");
        $stmt2->bind_param('i', $u);
        $stmt2->execute();
        $_SESSION['success'] = "Verifikasi berhasil. Silakan login.";
        header('Location: ../login.php');
        exit;
    } else {
        $_SESSION['err'] = "Kode salah atau sudah kadaluarsa (10 menit).";
        header('Location: ../verify.php?email='.urlencode($email));
        exit;
    }
} else {
    $_SESSION['err'] = "Email tidak ditemukan.";
    header('Location: ../verify.php');
    exit;
}
