<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Composer autoload

function sendResetEmail($email, $token) {
    $mail = new PHPMailer(true);

    try {
        // Server setting
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'emailkamu@gmail.com'; // ganti dengan email kamu
        $mail->Password   = 'kode_app_password_gmail'; // ganti dengan app password gmail
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Penerima
        $mail->setFrom('emailkamu@gmail.com', 'Booking Lapangan');
        $mail->addAddress($email);

        // Konten email
        $mail->isHTML(true);
        $mail->Subject = 'Reset Password - Booking Lapangan';
        $mail->Body    = "
            <h3>Permintaan Reset Password</h3>
            <p>Kode verifikasi Anda: <b>$token</b></p>
            <p>Kode ini hanya berlaku selama <b>10 menit</b>.</p>
            <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
        ";

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}
?>
