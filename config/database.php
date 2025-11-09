<?php
// Strict error reporting for MySQLi harus diletakkan sebelum koneksi dibuat
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // <<< PASTIKAN INI ADA

// Konfigurasi koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "booking_badmintoon";

// Membuat koneksi menggunakan metode Obyek
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi (menggunakan metode objek)
if ($conn->connect_error) {
 // Fatal error jika koneksi gagal
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Atur charset agar tidak error pada karakter UTF-8
$conn->set_charset("utf8mb4");

// Fungsi bantu format ke mata uang Rupiah
function rupiah($angka) {
   return "Rp " . number_format($angka, 0, ',', '.');
}

// Fungsi bantu format tanggal Indonesia
function tanggal_indo($tanggal) {
   return date('d-m-Y', strtotime($tanggal));
}
?>