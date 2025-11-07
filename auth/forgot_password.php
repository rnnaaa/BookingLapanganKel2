include 'includes/koneksi.php';
include 'send_email.php';

if (isset($_POST['email'])) {
    $email = $_POST['email'];

    // Cek apakah email terdaftar
    $cek = $conn->query("SELECT * FROM users WHERE email='$email'");
    if ($cek->num_rows > 0) {
        $token = bin2hex(random_bytes(4)); // token acak
        $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

        $conn->query("INSERT INTO reset_password (email, token, expires_at) VALUES ('$email','$token','$expires')");

        if (sendResetEmail($email, $token)) {
            echo "<script>alert('Kode reset telah dikirim ke email Anda.');</script>";
        } else {
            echo "<script>alert('Gagal mengirim email. Coba lagi.');</script>";
        }
    } else {
        echo "<script>alert('Email tidak terdaftar.');</script>";
    }
}
