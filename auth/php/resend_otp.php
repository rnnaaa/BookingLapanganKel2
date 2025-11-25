<?php
// php/resend_otp.php
session_start();
require '../../config/database.php';
require 'mail_helper.php';

// === PERBAIKAN 1: Tambahkan helper function 'esc()' yang hilang ===
if (!function_exists('esc')) {
    function esc($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
// === AKHIR PERBAIKAN 1 ===

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../resend.php');
    exit;
}
$email = trim($_POST['email']);

$stmt = $conn->prepare("SELECT id_user, nama FROM users WHERE email=? LIMIT 1");
$stmt->bind_param('s', $email);
$stmt->execute();
$res = $stmt->get_result();

if ($row = $res->fetch_assoc()) {
    
    // Ambil user_id dengan benar
    $user_id = $row['id_user']; 

    $otp = random_int(100000, 999999);
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    $stmt2 = $conn->prepare("UPDATE users SET otp_code=?, otp_expires=? WHERE id_user=?");
    
    // === PERBAIKAN 2: Gunakan $user_id, bukan $row['id'] ===
    $stmt2->bind_param('ssi', $otp, $expires, $user_id);
    // === AKHIR PERBAIKAN 2 ===
    
    $stmt2->execute();

    $subject = "Kode Verifikasi Baru — BookingLapangan";
    
    // Baris ini (sebelumnya error) sekarang akan berfungsi
    $body = "<p>Halo <b>".esc($row['nama'])."</b>,</p>
             <p>Berikut kode verifikasi baru Anda (berlaku 10 menit):</p>
             <h2 style='letter-spacing:4px;'>$otp</h2>";

    $sent = send_email($email, $subject, $body);
    
    if ($sent['ok']) {
        $_SESSION['success'] = "Kode verifikasi baru telah dikirim ke email Anda.";
        header('Location: ../verify.php?email='.urlencode($email));
        exit;
    } else {
        $_SESSION['err'] = "Gagal mengirim email: " . ($sent['error'] ?? 'Kesalahan server email');
        header('Location: ../resend.php?email='.urlencode($email));
        exit;
    }
} else {
    $_SESSION['err'] = "Email tidak ditemukan.";
    header('Location: ../resend.php');
    exit;
}
?>