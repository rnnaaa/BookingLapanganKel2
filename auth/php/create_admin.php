<?php
// php/create_admin.php
require 'db.php';
$username = 'admin';
$nama = 'Administrator';
$email = 'admin@example.com';
$phone = '081234567890';
$password_plain = 'admin123'; // GANTI sebelum menjalankan

$hash = password_hash($password_plain, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (username, nama_lengkap, email, phone, password, role, is_verified) VALUES (?, ?, ?, ?, ?, 'admin', 1)");
$stmt->bind_param('sssss', $username, $nama, $email, $phone, $hash);
if ($stmt->execute()) {
    echo "Admin dibuat. username: $username password: $password_plain";
} else {
    echo "Gagal: " . $conn->error;
}
