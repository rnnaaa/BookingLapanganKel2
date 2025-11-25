<?php
// php/register_process.php
session_start();
require __DIR__ . '/../../config/database.php';
require 'mail_helper.php'; 

if (!function_exists('esc')) {
    function esc($str) {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../register.php');
    exit;
}

// ambil & sanitasi
$nama = trim($_POST['nama']);
$username = trim($_POST['username']);
$email = trim($_POST['email']); 
$phone = trim($_POST['phone']); 
$pekerjaan = $_POST['pekerjaan'] ?? null;
$pekerjaan_lain = isset($_POST['pekerjaan_lain']) ? trim($_POST['pekerjaan_lain']) : null;
$pass = $_POST['password'];
$pass2 = $_POST['password2'];

// === 1. VALIDASI DOMAIN EMAIL ===
$allowed_domains = ['gmail.com', 'student.polije.ac.id'];
$email_parts = explode('@', strtolower($email));
$domain = end($email_parts);

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($domain, $allowed_domains)) {
    $_SESSION['error'] = "Domain email tidak valid. Gunakan @gmail.com atau @student.polije.ac.id";
    header('Location: ../register.php');
    exit;
}

// === 2. VALIDASI PASSWORD ===
if ($pass !== $pass2) {
    $_SESSION['error'] = "Password tidak cocok.";
    header('Location: ../register.php');
    exit;
}
if (strlen($pass) < 6) {
    $_SESSION['error'] = "Password minimal 6 karakter.";
    header('Location: ../register.php');
    exit;
}
// Cek Huruf Kecil
if (!preg_match('/[a-z]/', $pass)) {
    $_SESSION['error'] = "Password harus mengandung huruf kecil.";
    header('Location: ../register.php');
    exit;
}
// Cek Huruf Besar
if (!preg_match('/[A-Z]/', $pass)) {
    $_SESSION['error'] = "Password harus mengandung huruf KAPITAL.";
    header('Location: ../register.php');
    exit;
}
// Cek Angka
if (!preg_match('/[0-9]/', $pass)) {
    $_SESSION['error'] = "Password harus mengandung angka.";
    header('Location: ../register.php');
    exit;
}

// === 3. CEK DUPLIKASI ===
$stmt = $conn->prepare("SELECT id_user FROM users WHERE username=? OR email=? LIMIT 1");
$stmt->bind_param('ss', $username, $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = "Username atau email sudah terdaftar.";
    header('Location: ../register.php');
    exit;
}
$stmt->close();

// === 4. INSERT DATABASE ===
$hash = password_hash($pass, PASSWORD_DEFAULT);
$otp = random_int(100000, 999999);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$sql = "INSERT INTO users (
            username, nama, pekerjaan, pekerjaan_lain, email, no_hp, 
            password, otp_code, otp_expires, is_verified, role, last_login
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'user', NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssssss', $username, $nama, $pekerjaan, $pekerjaan_lain, $email, $phone, $hash, $otp, $expires);
$ok = $stmt->execute();

if (!$ok) {
    $_SESSION['error'] = "Gagal registrasi: " . $stmt->error;
    header('Location: ../register.php');
    exit;
}

// === 5. KIRIM EMAIL (TEMPLATE BAGUS) ===
$subject = "Kode Verifikasi - Rush Badminton Academy";

// Template Email HTML
$body = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333333; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; background-color: #f4f6f8; padding: 20px; }
        .email-card { background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .header { background-color: #0b63d6; padding: 20px; text-align: center; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; }
        .content { padding: 30px 25px; }
        .otp-box { background-color: #e7f0ff; border: 2px dashed #0b63d6; border-radius: 8px; text-align: center; padding: 15px; margin: 25px 0; }
        .otp-code { font-size: 32px; font-weight: bold; color: #0b63d6; letter-spacing: 5px; display: block; }
        .footer { text-align: center; font-size: 12px; color: #888888; padding: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="email-card">
            <div class="header">
                <h1>Rush Badminton</h1>
            </div>
            <div class="content">
                <p>Halo <strong>' . esc($nama) . '</strong>,</p>
                <p>Terima kasih telah mendaftar. Untuk mengaktifkan akun Anda dan mulai melakukan booking lapangan, silakan masukkan kode verifikasi berikut:</p>
                
                <div class="otp-box">
                    <span class="otp-code">' . $otp . '</span>
                    <div style="font-size: 12px; color: #666; margin-top: 5px;">Kode berlaku selama 10 menit</div>
                </div>
                
                <p>Jika Anda tidak merasa melakukan pendaftaran di Rush Badminton Academy, mohon abaikan email ini.</p>
                <p>Terima kasih,<br>Tim Rush Badminton</p>
            </div>
        </div>
        <div class="footer">
            &copy; ' . date("Y") . ' Rush Badminton Academy. All rights reserved.
        </div>
    </div>
</body>
</html>';

$sent = send_email($email, $subject, $body);

if (!$sent['ok']) {
    $_SESSION['info'] = "Registrasi berhasil. Namun email verifikasi gagal dikirim. Silakan coba login dan minta kirim ulang.";
} else {
$_SESSION['success'] = "Registrasi berhasil! Kode verifikasi telah dikirim ke " . esc($email);
}

header('Location: ../verify.php?email='.urlencode($email));
exit;
?>