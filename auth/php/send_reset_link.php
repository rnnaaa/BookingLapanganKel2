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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    header('Location: ../forgot.php'); 
    exit; 
}
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
    $subject = "Reset Password - Rush Badminton Academy";

    // === TEMPLATE EMAIL (Updated Style) ===
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
            .btn-box { text-align: center; margin: 30px 0; }
            .btn { background-color: #0b63d6; color: #ffffff !important; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; display: inline-block; font-size: 16px; }
            .footer { text-align: center; font-size: 12px; color: #888888; padding: 20px; }
            .note { font-size: 13px; color: #666; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="email-card">
                <div class="header">
                    <h1>Rush Badminton</h1>
                </div>
                <div class="content">
                    <p>Halo <strong>' . esc($row['nama']) . '</strong>,</p>
                    <p>Kami menerima permintaan untuk mereset password akun Anda. Silakan klik tombol di bawah ini untuk membuat password baru:</p>
                    
                    <div class="btn-box">
                        <a href="' . $link . '" class="btn">Reset Password</a>
                    </div>
                    
                    <div class="note">
                        <p>Link ini hanya berlaku selama 10 menit. Jika Anda tidak merasa meminta reset password, silakan abaikan email ini.</p>
                    </div>

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
?>