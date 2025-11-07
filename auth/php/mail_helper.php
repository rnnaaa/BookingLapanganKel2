<?php
// php/mail_helper.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../vendor/autoload.php'; // pastikan composer sudah dijalankan
require __DIR__ . '/db.php';

function send_email($to, $subject, $html_body) {
    // konfigurasi - ganti dengan email & app-password Gmail mu
    $MAIL_USER = 'EMAIL_KAMU@gmail.com';       // <--- ganti
    $MAIL_PASS = 'APP_PASSWORD_GMAILMU';       // <--- ganti (App Password)

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = $MAIL_USER;
        $mail->Password = $MAIL_PASS;
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom($MAIL_USER, 'BookingLapangan');
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_body;

        $mail->send();
        return ['ok'=>true];
    } catch (Exception $e) {
        return ['ok'=>false, 'error'=>$mail->ErrorInfo];
    }
}
?>
