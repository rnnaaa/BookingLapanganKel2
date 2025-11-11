<?php
// php/register_process.php
session_start();
require __DIR__ . '/../../config/database.php';
require 'mail_helper.php'; // Pastikan file ini ada di folder 'php/'

// Helper function jika belum ada (dari file login Anda)
if (!function_exists('esc')) {
    function esc($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

// ambil & sanitasi (basic)
$nama = trim($_POST['nama']);
$username = trim($_POST['username']);
$email = trim($_POST['email']);
$phone = trim($_POST['phone']); // Kita tetap ambil 'phone' dari form
$pekerjaan = $_POST['pekerjaan'] ?? null;
$pekerjaan_lain = isset($_POST['pekerjaan_lain']) ? trim($_POST['pekerjaan_lain']) : null;
$pass = $_POST['password'];
$pass2 = $_POST['password2'];

if ($pass !== $pass2) {
    $_SESSION['err'] = "Password tidak cocok.";
    header('Location: ../register.php');
    exit;
}
if (strlen($pass) < 6) {
    $_SESSION['err'] = "Password minimal 6 karakter.";
    header('Location: ../register.php');
    exit;
}

// PERBAIKAN: Cek ke 'id_user' untuk konsistensi
$stmt = $conn->prepare("SELECT id_user FROM users WHERE username=? OR email=? LIMIT 1");
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['err'] = "Username atau email sudah terdaftar.";
    header('Location: ../register.php');
    exit;
}
$stmt->close();

// hash password
$hash = password_hash($pass, PASSWORD_DEFAULT);

// generate OTP 6 digit dan expiry 10 menit
$otp = random_int(100000, 999999);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// simpan user (is_verified = 0)
// === PERBAIKAN PENTING PADA KODE SQL ===
$sql = "INSERT INTO users (
            username, 
            nama,           -- PERBAIKAN: dari 'nama_lengkap'
            pekerjaan, 
            pekerjaan_lain, 
            email, 
            no_hp,          -- PERBAIKAN: dari 'phone'
            password, 
            otp_code, 
            otp_expires, 
            is_verified, 
            role,
            last_login      -- PERBAIKAN: Menambahkan 'last_login'
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'user', NOW() -- PERBAIKAN: Menambahkan NOW()
        )";
// === AKHIR PERBAIKAN SQL ===

$stmt = $conn->prepare($sql);
// bind_param tetap 9 string (NOW() tidak di-bind)
$stmt->bind_param('sssssssss', $username, $nama, $pekerjaan, $pekerjaan_lain, $email, $phone, $hash, $otp, $expires);
$ok = $stmt->execute();

if (!$ok) {
    // Tampilkan error SQL jika gagal
    $_SESSION['err'] = "Gagal registrasi: " . $stmt->error;
    header('Location: ../register.php');
    exit;
}

// kirim email OTP
$subject = "Kode Verifikasi — BookingLapangan";
$body = "<p>Halo <b>".esc($nama)."</b>,</p>
         <p>Gunakan kode verifikasi berikut untuk mengaktifkan akun Anda. Kode berlaku 10 menit.</p>
         <h2 style='letter-spacing:4px;'>$otp</h2>
         <p>Jika Anda tidak merasa mendaftar, abaikan email ini.</p>";

$sent = send_email($email, $subject, $body);
if (!$sent['ok']) {
    $_SESSION['info'] = "Registrasi berhasil. Namun email verifikasi gagal dikirim: " . ($sent['error'] ?? '');
} else {
    $_SESSION['success'] = "Registrasi berhasil. Kode verifikasi dikirim ke email Anda.";
}

// redirect ke halaman verifikasi
header('Location: ../verify.php?email='.urlencode($email));
exit;
?>