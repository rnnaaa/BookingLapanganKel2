<?php
// php/send_reset_link.php
session_start();
require '../../config/database.php';
require 'mail_helper.php';

if (!function_exists('esc')) {
    function esc($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../forgot.php'); exit; }
$email = trim($_POST['email']);

$stmt = $conn->prepare("SELECT id_user, nama FROM users WHERE email=? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $stmt2 = $conn->prepare("UPDATE users SET reset_token=?, reset_expires=? WHERE id_user=?");
    $stmt2->bind_param('ssi', $token, $expires, $row['id_user']);
    $stmt2->execute();

    $link = "http://localhost/BookingLapanganKel2/auth/reset_password.php?token=".$token;
    $subject = "Reset Password — BookingLapangan";
    $body = "<p>Halo <b>".esc($row['nama'])."</b>,</p>
             <p>Silakan klik link berikut untuk mengganti password Anda. Link berlaku 10 menit.</p>
             <p><a href='$link'>Reset Password</a></p>";

    $sent = send_email($email, $subject, $body);
    if ($sent['ok']) {
        $_SESSION['success'] = "Link reset telah dikirim ke email Anda.";
    } else {
        $_SESSION['err'] = "Gagal kirim email: " . ($sent['error'] ?? '');
    }
    header('Location: ../forgot.php');
    exit;
} else {
    $_SESSION['err'] = "Email tidak terdaftar.";
    header('Location: ../forgot.php');
    exit;
}
