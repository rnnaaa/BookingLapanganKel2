<?php
// Konfigurasi koneksi database
$host = "localhost";
$user = "root";
$pass = "";
$db   = "booking_badmintoon"; // ✅ disesuaikan dengan nama database kamu

// Membuat koneksi
$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Atur charset agar tidak error pada karakter UTF-8 (misal nama pelanggan)
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
